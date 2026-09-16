<?php

namespace Tests\Feature;

use App\Models\DatasetTerbuka;
use App\Models\KategoriData;
use App\Models\Pemohon;
use App\Models\PermintaanData;
use App\Models\User;
use App\Services\UnggahDokumen;
use Aws\CommandInterface;
use Aws\Result;
use Database\Seeders\RolePermissionSeeder;
use GuzzleHttp\Promise\Create;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

class UnggahDokumenTest extends TestCase
{
    use RefreshDatabase;

    private array $objects = [];

    private array $commands = [];

    private bool $ubahSebelumCopy = false;

    private ?int $batalkanSaatCopy = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Mail::fake();
        config()->set('filesystems.documents', 's3');
        config()->set('filesystems.direct_upload', true);
        config()->set('filesystems.disks.s3', array_replace(config('filesystems.disks.s3'), [
            'key' => 'testing-key', 'secret' => 'testing-secret', 'region' => 'auto',
            'bucket' => 'siperminda-test', 'endpoint' => 'https://account-test.r2.cloudflarestorage.com',
            'url' => null, 'use_path_style_endpoint' => false,
            // SDK, adapter, serializer dan signer asli; hanya transport object storage diganti.
            'handler' => function (CommandInterface $command, RequestInterface $request) {
                $this->assertFalse($request->hasHeader('x-amz-acl'));
                $this->assertFalse($request->hasHeader('x-amz-sdk-checksum-algorithm'));
                $this->assertStringContainsString('/'.config('filesystems.disks.s3.region').'/s3/aws4_request', $request->getHeaderLine('Authorization'));
                $this->commands[] = [$command->getName(), $command->toArray(), $request];
                $key = $command['Key'];
                $isi = $this->objects[$key] ?? null;
                switch ($command->getName()) {
                    case 'HeadObject':
                        if ($isi === null) {
                            throw new \RuntimeException('Object tidak ditemukan.');
                        }
                        $hasil = ['ContentLength' => strlen($isi), 'ETag' => '"'.md5($isi).'"'];
                        break;
                    case 'GetObject':
                        $this->assertSame('"'.md5($isi).'"', $command['IfMatch']);
                        file_put_contents($command['@http']['sink'], $isi);
                        $hasil = ['Body' => $isi, 'ETag' => $command['IfMatch']];
                        break;
                    case 'CopyObject':
                        if ($this->ubahSebelumCopy) {
                            throw new \RuntimeException('Precondition failed: rahasia-provider');
                        }
                        $source = rawurldecode(explode('/', $command['CopySource'], 2)[1]);
                        $this->assertSame('"'.md5($this->objects[$source]).'"', $command['CopySourceIfMatch']);
                        $this->assertArrayNotHasKey('ACL', $command->toArray());
                        $this->objects[$key] = $this->objects[$source];
                        if ($this->batalkanSaatCopy !== null) {
                            PermintaanData::whereKey($this->batalkanSaatCopy)->update(['status' => 'ditolak']);
                        }
                        $hasil = ['CopyObjectResult' => ['ETag' => $command['CopySourceIfMatch']]];
                        break;
                    case 'DeleteObject':
                        unset($this->objects[$key]);
                        $hasil = [];
                        break;
                    case 'PutObject':
                        $this->objects[$key] = (string) $command['Body'];
                        $hasil = ['ETag' => '"'.md5($this->objects[$key]).'"'];
                        break;
                    default:
                        throw new \LogicException('Operasi S3 tidak diharapkan: '.$command->getName());
                }

                return Create::promiseFor(new Result($hasil));
            },
        ]));
        Storage::forgetDisk('s3');
    }

    public function test_supabase_path_style_mempertahankan_prefix_endpoint_dan_otorisasi_unduhan(): void
    {
        // Endpoint/region sintetis; tidak menghubungi akun Supabase pengguna.
        config()->set('filesystems.disks.s3.endpoint', 'https://project-test.storage.supabase.co/storage/v1/s3');
        config()->set('filesystems.disks.s3.region', 'ap-southeast-1');
        config()->set('filesystems.disks.s3.use_path_style_endpoint', true);
        Storage::forgetDisk('s3');
        $permintaan = $this->permintaan();
        $this->actingAs($this->staf());
        $isi = "%PDF-1.7\n".str_repeat('0', UnggahDokumen::MAKSIMAL_BYTE - 9);
        $izin = $this->izin('hasil-permintaan', $permintaan->id, $isi);
        $base = 'https://project-test.storage.supabase.co/storage/v1/s3/siperminda-test/';
        $this->assertStringStartsWith($base.$izin['key'].'?', $izin['upload_url']);
        $this->assertSame('PUT', $izin['method']);
        parse_str(parse_url($izin['upload_url'], PHP_URL_QUERY), $query);
        $this->assertStringContainsString('/ap-southeast-1/s3/aws4_request', $query['X-Amz-Credential']);
        $this->assertContains('content-type', explode(';', $query['X-Amz-SignedHeaders']));
        $this->post(route('internal.permintaan.upload', $permintaan), ['upload_token' => $izin['finalize_token']])
            ->assertSessionHasNoErrors();
        $permintaan->refresh();
        $this->assertSame('data_siap', $permintaan->status);
        $this->assertSame($isi, $this->objects[$permintaan->file_hasil_path]);
        $this->assertSame(['HeadObject', 'GetObject', 'CopyObject', 'DeleteObject'], array_column($this->commands, 0));
        foreach ($this->commands as [$operation, $arguments, $request]) {
            $this->assertStringStartsWith($base, (string) $request->getUri());
        }

        $lain = Pemohon::create(['nama' => 'Lain', 'no_hp' => '6281234567891', 'jenis_pemohon' => 'publik', 'no_hp_verified_at' => now()]);
        $this->withSession(['pemohon_id' => $lain->id])->get(route('permintaan.unduh', $permintaan))->assertNotFound();
        $response = $this->withSession(['pemohon_id' => $permintaan->pemohon_id])->get(route('permintaan.unduh', $permintaan))
            ->assertRedirect()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringStartsWith($base.$permintaan->file_hasil_path.'?', $response->headers->get('Location'));
    }

    private function staf(): User
    {
        $user = User::factory()->create();
        $user->assignRole('staf');

        return $user;
    }

    private function permintaan(): PermintaanData
    {
        $pemohon = Pemohon::create([
            'nama' => 'Pemohon Upload', 'no_hp' => '6281234567890',
            'jenis_pemohon' => 'publik', 'no_hp_verified_at' => now(),
        ]);

        return PermintaanData::create([
            'pemohon_id' => $pemohon->id, 'nomor_tiket' => 'BPS/PD/2026/00001',
            'jenis_data' => 'Statistik', 'tujuan_penggunaan' => 'Penelitian', 'status' => 'disetujui_petugas',
        ]);
    }

    private function izin(string $tujuan, ?int $target, string $isi): array
    {
        $izin = $this->postJson(route('internal.dokumen.unggah'), [
            'tujuan' => $tujuan, 'target_id' => $target, 'ukuran' => strlen($isi), 'content_type' => 'application/pdf',
        ])->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json();
        $this->objects[$izin['key']] = $isi;

        return $izin;
    }

    public function test_presigned_put_mengikat_key_content_type_dan_berlaku_lima_menit(): void
    {
        $this->actingAs($this->staf());
        $izin = $this->postJson(route('internal.dokumen.unggah'), [
            'tujuan' => 'katalog-baru', 'ukuran' => UnggahDokumen::MAKSIMAL_BYTE, 'content_type' => 'application/pdf',
        ])->assertOk()->json();
        $this->assertSame(['upload_url', 'method', 'key', 'headers', 'finalize_token'], array_keys($izin));
        $this->assertSame('PUT', $izin['method']);
        $this->assertSame(['Content-Type' => 'application/pdf'], $izin['headers']);
        $this->assertStringStartsWith('https://siperminda-test.account-test.r2.cloudflarestorage.com/', $izin['upload_url']);
        $this->assertSame('/'.$izin['key'], parse_url($izin['upload_url'], PHP_URL_PATH));
        parse_str(parse_url($izin['upload_url'], PHP_URL_QUERY), $query);
        $this->assertSame('300', $query['X-Amz-Expires']);
        $this->assertContains('content-type', explode(';', $query['X-Amz-SignedHeaders']));
        $this->assertStringContainsString('/auto/s3/aws4_request', $query['X-Amz-Credential']);
        $this->assertArrayNotHasKey('x-amz-acl', array_change_key_case($query));
        $this->assertArrayNotHasKey('x-amz-checksum-crc32', array_change_key_case($query));
        $this->assertStringNotContainsString('testing-secret', json_encode($izin));
        $this->assertStringStartsWith('pending_uploads/', $izin['key']);
        $this->assertSame([], $this->commands);
        $this->postJson(route('internal.dokumen.unggah'), [
            'tujuan' => 'katalog-baru', 'ukuran' => UnggahDokumen::MAKSIMAL_BYTE + 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('ukuran');
    }

    public function test_izin_upload_memerlukan_login_permission_dan_status_yang_sesuai(): void
    {
        $this->postJson(route('internal.dokumen.unggah'), [])->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson(route('internal.dokumen.unggah'), [
            'tujuan' => 'katalog-baru', 'ukuran' => 100,
        ])->assertForbidden();
        $permintaan = $this->permintaan();
        $permintaan->update(['status' => 'diajukan']);
        $this->actingAs($this->staf())->postJson(route('internal.dokumen.unggah'), [
            'tujuan' => 'hasil-permintaan', 'target_id' => $permintaan->id, 'ukuran' => 100,
        ])->assertConflict();
        $this->assertSame([], $this->commands);
    }

    public function test_file_50_mb_difinalisasi_tanpa_multipart_dan_unduhan_tetap_memeriksa_pemilik(): void
    {
        $permintaan = $this->permintaan();
        $this->actingAs($this->staf());
        $isi = "%PDF-1.7\n".str_repeat('0', UnggahDokumen::MAKSIMAL_BYTE - 9);
        $this->get(route('internal.permintaan.show', $permintaan))->assertOk()
            ->assertSee('data-tujuan="hasil-permintaan"', false)->assertDontSee('name="file_hasil"', false);
        $izin = $this->izin('hasil-permintaan', $permintaan->id, $isi);
        $this->post(route('internal.permintaan.upload', $permintaan), ['upload_token' => $izin['finalize_token']])
            ->assertRedirect(route('internal.permintaan.index'))->assertSessionHasNoErrors();
        $permintaan->refresh();
        $this->assertSame('data_siap', $permintaan->status);
        $this->assertSame($isi, $this->objects[$permintaan->file_hasil_path]);
        $this->assertArrayNotHasKey($izin['key'], $this->objects);

        $lain = Pemohon::create(['nama' => 'Lain', 'no_hp' => '6281234567891', 'jenis_pemohon' => 'publik', 'no_hp_verified_at' => now()]);
        $this->withSession(['pemohon_id' => $lain->id])->get(route('permintaan.unduh', $permintaan))->assertNotFound();
        $response = $this->withSession(['pemohon_id' => $permintaan->pemohon_id])->get(route('permintaan.unduh', $permintaan))
            ->assertRedirect()->assertHeader('Cache-Control', 'no-store, private');
        $url = $response->headers->get('Location');
        $this->assertStringStartsWith('https://siperminda-test.account-test.r2.cloudflarestorage.com/', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('300', $query['X-Amz-Expires']);
        $this->assertStringContainsString('attachment;', $query['response-content-disposition']);
    }

    public function test_token_terikat_user_tujuan_target_dan_tidak_dapat_dipakai_ulang(): void
    {
        config()->set('cache.default', 'database');
        $pemilik = $this->staf();
        $this->actingAs($pemilik);
        $izin = $this->izin('katalog-baru', null, "%PDF-1.7\nisi");
        $data = [
            'judul' => 'Statistik', 'kategori_id' => KategoriData::create(['nama' => 'Statistik', 'slug' => 'statistik'])->id,
            'periode' => '2026', 'deskripsi' => 'Data statistik', 'upload_token' => $izin['finalize_token'],
        ];
        $this->actingAs($this->staf())->post(route('internal.katalog.store'), $data)->assertSessionHasErrors('file');
        $this->actingAs($pemilik)->post(route('internal.permintaan.upload', $this->permintaan()), [
            'upload_token' => $izin['finalize_token'],
        ])->assertSessionHasErrors('file_hasil');
        $this->assertSame([], $this->commands);
        $this->post(route('internal.katalog.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('dataset_terbuka', 1);
        $this->post(route('internal.katalog.store'), $data)->assertSessionHasErrors('file');
        $this->assertDatabaseCount('dataset_terbuka', 1);
    }

    public function test_mime_dan_ukuran_object_diperiksa_sebelum_status_berubah(): void
    {
        $permintaan = $this->permintaan();
        $this->actingAs($this->staf());
        foreach (['mime', 'ukuran', 'hilang'] as $kasus) {
            $izin = $this->izin('hasil-permintaan', $permintaan->id, '<?php echo "bukan PDF";');
            if ($kasus === 'ukuran') {
                $this->objects[$izin['key']] .= 'tambahan';
            } elseif ($kasus === 'hilang') {
                unset($this->objects[$izin['key']]);
            }
            $this->post(route('internal.permintaan.upload', $permintaan), ['upload_token' => $izin['finalize_token']])
                ->assertSessionHasErrors('file_hasil');
            $this->assertNull($permintaan->fresh()->file_hasil_path);
            $this->assertSame('disetujui_petugas', $permintaan->fresh()->status);
        }
        $this->assertNotContains('CopyObject', array_column($this->commands, 0));
    }

    public function test_token_kedaluwarsa_dan_perubahan_object_tidak_mengaktifkan_unduhan(): void
    {
        $permintaan = $this->permintaan();
        $this->actingAs($this->staf());
        $izin = $this->izin('hasil-permintaan', $permintaan->id, "%PDF-1.7\nisi");
        $this->travel(16)->minutes();
        $this->post(route('internal.permintaan.upload', $permintaan), ['upload_token' => $izin['finalize_token']])
            ->assertSessionHasErrors('file_hasil');
        $this->assertSame([], $this->commands);

        $izin = $this->izin('hasil-permintaan', $permintaan->id, "%PDF-1.7\nisi");
        $this->ubahSebelumCopy = true;
        $this->post(route('internal.permintaan.upload', $permintaan), ['upload_token' => $izin['finalize_token']])
            ->assertSessionHasErrors('file_hasil');
        $this->assertStringNotContainsString('rahasia-provider', json_encode(session('errors')->getBag('default')->all()));
        $this->assertNull($permintaan->fresh()->file_hasil_path);
    }

    public function test_put_object_melalui_adapter_tidak_mengirim_acl_termasuk_config_lama(): void
    {
        config()->set('filesystems.disks.s3.options.ACL', 'bucket-owner-full-control');
        Storage::forgetDisk('s3');
        Storage::disk('s3')->put('dokumen.pdf', "%PDF-1.7\nisi");
        $this->assertSame('PutObject', $this->commands[0][0]);
        $this->assertFalse($this->commands[0][2]->hasHeader('x-amz-acl'));
        $this->assertSame("%PDF-1.7\nisi", $this->objects['dokumen.pdf']);
    }

    public function test_content_type_kosong_memakai_fallback_dan_header_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->staf());
        $this->postJson(route('internal.dokumen.unggah'), [
            'tujuan' => 'katalog-baru', 'ukuran' => 10, 'content_type' => '',
        ])->assertOk()->assertJsonPath('headers.Content-Type', 'application/octet-stream');
        $this->postJson(route('internal.dokumen.unggah'), [
            'tujuan' => 'katalog-baru', 'ukuran' => 10, 'content_type' => "application/pdf\r\nX-Test: injected",
        ])->assertUnprocessable()->assertJsonValidationErrors('content_type');
    }

    public function test_edit_dan_revisi_hanya_menerima_token_untuk_dataset_tujuan(): void
    {
        $this->actingAs($this->staf());
        $kategori = KategoriData::create(['nama' => 'Statistik', 'slug' => 'statistik']);
        $data = ['judul' => 'Dataset', 'kategori_id' => $kategori->id, 'periode' => '2026', 'deskripsi' => 'Data'];
        $dataset = DatasetTerbuka::create($data + [
            'file_path' => 'dataset_terbuka/lama.pdf', 'ukuran_file' => 3, 'status' => 'aktif', 'uploaded_by' => auth()->id(),
        ]);
        $lain = DatasetTerbuka::create($data + [
            'file_path' => 'dataset_terbuka/lain.pdf', 'ukuran_file' => 3, 'status' => 'aktif', 'uploaded_by' => auth()->id(),
        ]);
        $this->objects[$dataset->file_path] = 'lama';
        $this->get(route('internal.katalog.edit', $dataset))->assertOk()->assertSee('data-tujuan="katalog-edit"', false);
        $this->get(route('internal.katalog.edit', $dataset).'?revisi=1')->assertOk()->assertSee('data-tujuan="katalog-revisi"', false);
        $izin = $this->izin('katalog-edit', $dataset->id, "%PDF-1.7\nedit");
        $this->put(route('internal.katalog.update', $lain), $data + ['upload_token' => $izin['finalize_token']])
            ->assertSessionHasErrors('file');
        $this->assertSame([], $this->commands);
        $this->put(route('internal.katalog.update', $dataset), $data + ['upload_token' => $izin['finalize_token']])
            ->assertSessionHasNoErrors();
        $this->assertArrayNotHasKey('dataset_terbuka/lama.pdf', $this->objects);
        $this->assertSame('dataset_terbuka/lain.pdf', $lain->fresh()->file_path);

        $izin = $this->izin('katalog-revisi', $dataset->id, "%PDF-1.7\nrevisi");
        $this->post(route('internal.katalog.revisi', $dataset), ['upload_token' => $izin['finalize_token']])
            ->assertSessionHasNoErrors();
        $this->assertSame('digantikan', $dataset->fresh()->status);
        $revisi = DatasetTerbuka::where('dataset_induk_id', $dataset->id)->firstOrFail();
        $this->assertSame(2, $revisi->versi);
        $this->assertSame('aktif', $revisi->status);
        $this->assertArrayHasKey($revisi->file_path, $this->objects);
    }

    public function test_perubahan_status_saat_upload_membersihkan_file_final_yang_tidak_terpakai(): void
    {
        $permintaan = $this->permintaan();
        $this->actingAs($this->staf());
        $izin = $this->izin('hasil-permintaan', $permintaan->id, "%PDF-1.7\nisi");
        $this->batalkanSaatCopy = $permintaan->id;
        $this->post(route('internal.permintaan.upload', $permintaan), ['upload_token' => $izin['finalize_token']])
            ->assertSessionHasErrors('file_hasil');
        $this->assertNull($permintaan->fresh()->file_hasil_path);
        $this->assertSame('ditolak', $permintaan->fresh()->status);
        $this->assertSame([], $this->objects);
        $this->assertDatabaseCount('notifikasi_log', 0);
    }

    public function test_form_s3_tidak_mengirim_input_file_ke_aplikasi_dan_edit_tanpa_file_tetap_bisa(): void
    {
        $this->actingAs($this->staf());
        $this->get(route('internal.katalog.create'))->assertOk()
            ->assertSee('data-unggah-langsung', false)->assertSee('js/unggah-dokumen.js', false)
            ->assertDontSee('name="file"', false);
        $dataset = DatasetTerbuka::create([
            'judul' => 'Lama', 'kategori_id' => KategoriData::create(['nama' => 'Statistik', 'slug' => 'statistik'])->id,
            'periode' => '2026', 'deskripsi' => 'Lama', 'file_path' => 'dataset_terbuka/lama.pdf',
            'ukuran_file' => 10, 'status' => 'aktif', 'uploaded_by' => auth()->id(),
        ]);
        $this->put(route('internal.katalog.update', $dataset), [
            'judul' => 'Baru', 'kategori_id' => $dataset->kategori_id, 'periode' => '2026', 'deskripsi' => 'Baru',
        ])->assertSessionHasNoErrors();
        $this->assertSame('dataset_terbuka/lama.pdf', $dataset->fresh()->file_path);
        $this->assertSame('Baru', $dataset->fresh()->judul);
        $this->assertSame([], $this->commands);

        config()->set('filesystems.documents', 'local');
        Storage::fake('local');
        $this->get(route('internal.katalog.create'))->assertOk()->assertSee('name="file"', false);
        $this->post(route('internal.katalog.store'), [
            'judul' => 'Lokal', 'kategori_id' => $dataset->kategori_id, 'periode' => '2026', 'deskripsi' => 'Lokal',
            'file' => UploadedFile::fake()->create('data.pdf', 8, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        Storage::disk('local')->assertExists(DatasetTerbuka::where('judul', 'Lokal')->firstOrFail()->file_path);
    }
}
