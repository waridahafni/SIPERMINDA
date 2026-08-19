<?php

namespace Tests\Feature;

use App\Contracts\PengirimOtp;
use App\Models\OtpVerification;
use App\Models\Pemohon;
use App\Models\PermintaanData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PengirimOtpPalsu implements PengirimOtp
{
    /** @var array<int, array{nomor_hp: string, kode: string}> */
    public array $pengiriman = [];

    public function kirim(string $nomorHp, string $kode): string
    {
        $this->pengiriman[] = [
            'nomor_hp' => $nomorHp,
            'kode' => $kode,
        ];

        return 'pesan-palsu-'.count($this->pengiriman);
    }

    public function kodeTerakhirUntuk(string $nomorHp): string
    {
        for ($index = count($this->pengiriman) - 1; $index >= 0; $index--) {
            if ($this->pengiriman[$index]['nomor_hp'] === $nomorHp) {
                return $this->pengiriman[$index]['kode'];
            }
        }

        throw new \RuntimeException("Tidak ada OTP palsu untuk nomor {$nomorHp}.");
    }
}

class AutentikasiPemohonTest extends TestCase
{
    use RefreshDatabase;

    private PengirimOtpPalsu $pengirimOtp;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('otp.driver', 'log');
        Storage::fake('local');

        $this->pengirimOtp = new PengirimOtpPalsu;
        $this->app->instance(PengirimOtp::class, $this->pengirimOtp);
    }

    public function test_pemohon_baru_dapat_daftar_dan_masuk_setelah_verifikasi_otp(): void
    {
        $nomorKanonis = '6281234567801';

        $this->post(route('pemohon.daftar.kirim-otp'), [
            'no_hp' => '081234567801',
            'nama' => 'Pemohon Baru',
            'email' => 'baru@example.com',
            'jenis_pemohon' => 'publik',
            'nama_instansi' => null,
        ])->assertRedirect(route('otp.form'))
            ->assertSessionHas('otp_mode', 'daftar')
            ->assertSessionHas('otp_pemohon.no_hp', $nomorKanonis)
            ->assertSessionHas('otp_verification_id', fn (mixed $id): bool => is_int($id));

        $this->assertCount(1, $this->pengirimOtp->pengiriman);
        $this->assertDatabaseCount('pemohon', 0);

        $this->post(route('otp.verifikasi'), [
            'kode_otp' => $this->pengirimOtp->kodeTerakhirUntuk($nomorKanonis),
        ])->assertRedirect(route('permintaan.create'))
            ->assertSessionHas('pemohon_otp', $nomorKanonis)
            ->assertSessionMissing([
                'otp_pemohon',
                'otp_mode',
                'otp_verification_id',
                'pendaftaran_terverifikasi',
            ]);

        $pemohon = Pemohon::firstOrFail();

        $this->assertSame('Pemohon Baru', $pemohon->nama);
        $this->assertSame($nomorKanonis, $pemohon->no_hp);
        $this->assertSame('baru@example.com', $pemohon->email);
        $this->assertNotNull($pemohon->no_hp_verified_at);
        $this->assertSame($pemohon->id, session('pemohon_id'));
        $this->assertNotNull(OtpVerification::firstOrFail()->verified_at);
    }

    public function test_daftar_dengan_nomor_existing_memakai_akun_dan_riwayat_yang_sama(): void
    {
        $pemohon = $this->buatPemohon('081234567802', 'Profil Tersimpan');
        $permintaan = $this->buatPermintaan(
            $pemohon,
            'BPS/PD/2026/07802',
            'hasil_permintaan/riwayat-existing.pdf',
        );

        $this->post(route('pemohon.daftar.kirim-otp'), [
            'no_hp' => '+6281234567802',
            'nama' => 'Profil Dari Form Baru',
            'email' => 'form-baru@example.com',
            'jenis_pemohon' => 'instansi',
            'nama_instansi' => 'Instansi Form Baru',
        ])->assertRedirect(route('otp.form'));

        $this->post(route('otp.verifikasi'), [
            'kode_otp' => $this->pengirimOtp->kodeTerakhirUntuk('6281234567802'),
        ])->assertRedirect(route('permintaan.create'))
            ->assertSessionHas('pemohon_id', $pemohon->id)
            ->assertSessionHas('success', fn (string $pesan): bool => str_contains($pesan, 'sudah terdaftar'));

        $this->assertDatabaseCount('pemohon', 1);
        $this->assertDatabaseHas('pemohon', [
            'id' => $pemohon->id,
            'nama' => 'Profil Tersimpan',
            'no_hp' => '6281234567802',
        ]);
        $this->assertDatabaseHas('permintaan_data', [
            'id' => $permintaan->id,
            'pemohon_id' => $pemohon->id,
        ]);
    }

    public function test_pemohon_existing_dapat_masuk_hanya_dengan_nomor_dan_otp(): void
    {
        $pemohon = $this->buatPemohon('6281234567803', 'Pemohon Existing');

        $this->post(route('pemohon.masuk.kirim-otp'), [
            'no_hp' => '081234567803',
        ])->assertRedirect(route('otp.form'))
            ->assertSessionHas('otp_mode', 'masuk')
            ->assertSessionHas('otp_pemohon', fn (mixed $profil): bool => is_array($profil)
                && array_key_exists('nama', $profil)
                && $profil['nama'] === null);

        $this->post(route('otp.verifikasi'), [
            'kode_otp' => $this->pengirimOtp->kodeTerakhirUntuk('6281234567803'),
        ])->assertRedirect(route('pemohon.permintaan.index'))
            ->assertSessionHas('pemohon_id', $pemohon->id)
            ->assertSessionHas('pemohon_nama', 'Pemohon Existing')
            ->assertSessionMissing(['otp_pemohon', 'otp_mode', 'otp_verification_id']);

        $this->assertDatabaseCount('pemohon', 1);
    }

    public function test_login_nomor_baru_dapat_melengkapi_profil_tanpa_otp_kedua(): void
    {
        $nomorKanonis = '6281234567804';

        $this->post(route('pemohon.masuk.kirim-otp'), [
            'no_hp' => '+6281234567804',
        ])->assertRedirect(route('otp.form'));

        $this->post(route('otp.verifikasi'), [
            'kode_otp' => $this->pengirimOtp->kodeTerakhirUntuk($nomorKanonis),
        ])->assertRedirect(route('pemohon.daftar.lengkapi'))
            ->assertSessionHas('pendaftaran_terverifikasi', function (mixed $bukti) use ($nomorKanonis): bool {
                return is_array($bukti)
                    && ($bukti['no_hp'] ?? null) === $nomorKanonis
                    && ($bukti['berlaku_sampai'] ?? 0) > now()->timestamp;
            })
            ->assertSessionMissing([
                'pemohon_id',
                'otp_pemohon',
                'otp_mode',
                'otp_verification_id',
            ]);

        $this->assertDatabaseCount('pemohon', 0);
        $this->assertNotNull(OtpVerification::firstOrFail()->verified_at);
        $this->assertCount(1, $this->pengirimOtp->pengiriman);

        $this->post(route('pemohon.daftar.lengkapi.simpan'), [
            'nama' => 'Pemohon Dari Login Baru',
            'email' => 'login-baru@example.com',
            'jenis_pemohon' => 'instansi',
            'nama_instansi' => 'Instansi Baru',
        ])->assertRedirect(route('permintaan.create'))
            ->assertSessionHas('pemohon_otp', $nomorKanonis)
            ->assertSessionMissing('pendaftaran_terverifikasi');

        $pemohon = Pemohon::firstOrFail();

        $this->assertSame($pemohon->id, session('pemohon_id'));
        $this->assertSame('Pemohon Dari Login Baru', $pemohon->nama);
        $this->assertSame('Instansi Baru', $pemohon->nama_instansi);
        $this->assertNotNull($pemohon->no_hp_verified_at);
        $this->assertCount(1, $this->pengirimOtp->pengiriman);
    }

    public function test_respons_login_tidak_membocorkan_apakah_nomor_sudah_terdaftar(): void
    {
        $this->buatPemohon('6281234567805', 'Pemohon Anti Enumerasi');

        foreach (['081234567805', '081234567806'] as $nomorHp) {
            $this->post(route('pemohon.masuk.kirim-otp'), [
                'no_hp' => $nomorHp,
            ])->assertRedirect(route('otp.form'))
                ->assertSessionHas('success', 'Kode OTP telah dikirim melalui log lokal.')
                ->assertSessionHas('otp_mode', 'masuk')
                ->assertSessionHas('otp_pemohon', function (mixed $profil): bool {
                    return is_array($profil)
                        && ($profil['nama'] ?? null) === null
                        && ($profil['email'] ?? null) === null
                        && ($profil['jenis_pemohon'] ?? null) === null
                        && ($profil['nama_instansi'] ?? null) === null;
                });
        }

        $this->assertCount(2, $this->pengirimOtp->pengiriman);
        $this->assertDatabaseCount('pemohon', 1);
    }

    public function test_bukti_lengkapi_profil_yang_kedaluwarsa_tidak_dapat_dipakai(): void
    {
        $this->withSession([
            'pendaftaran_terverifikasi' => [
                'no_hp' => '6281234567807',
                'berlaku_sampai' => now()->subSecond()->timestamp,
            ],
        ])->post(route('pemohon.daftar.lengkapi.simpan'), [
            'nama' => 'Profil Kedaluwarsa',
            'email' => 'kedaluwarsa@example.com',
            'jenis_pemohon' => 'publik',
            'nama_instansi' => null,
        ])->assertRedirect(route('pemohon.daftar'))
            ->assertSessionMissing('pendaftaran_terverifikasi');

        $this->assertDatabaseCount('pemohon', 0);
        $this->assertCount(0, $this->pengirimOtp->pengiriman);
    }

    public function test_logout_hanya_post_dan_membersihkan_seluruh_sesi_pemohon(): void
    {
        $pemohon = $this->buatPemohon('6281234567808', 'Pemohon Logout');
        $kunciSesi = [
            'pemohon_otp',
            'pemohon_id',
            'pemohon_nama',
            'otp_nomor',
            'otp_pemohon',
            'otp_mode',
            'otp_verification_id',
            'pendaftaran_terverifikasi',
            'akses_status',
            'url.intended',
        ];

        $this->withSession([
            'pemohon_otp' => $pemohon->no_hp,
            'pemohon_id' => $pemohon->id,
            'pemohon_nama' => $pemohon->nama,
            'otp_nomor' => $pemohon->no_hp,
            'otp_pemohon' => ['no_hp' => $pemohon->no_hp],
            'otp_mode' => 'masuk',
            'otp_verification_id' => 123,
            'pendaftaran_terverifikasi' => ['no_hp' => $pemohon->no_hp],
            'akses_status' => ['nomor_tiket' => 'BPS/PD/2026/00001'],
            'url.intended' => '/permintaan/create',
        ]);

        $this->get(route('pemohon.keluar'))->assertMethodNotAllowed();

        $this->post(route('pemohon.keluar'))
            ->assertRedirect(route('beranda'))
            ->assertSessionMissing($kunciSesi);
    }

    public function test_intended_get_disimpan_sebagai_uri_relatif_dan_dipakai_setelah_login(): void
    {
        $pemohon = $this->buatPemohon('6281234567809', 'Pemohon Intended');
        $tujuan = '/akun/permintaan?status=data_siap';

        $this->get($tujuan)
            ->assertRedirect(route('pemohon.masuk'))
            ->assertSessionHas('url.intended', $tujuan);

        $this->post(route('pemohon.masuk.kirim-otp'), [
            'no_hp' => '081234567809',
        ])->assertRedirect(route('otp.form'));

        $this->post(route('otp.verifikasi'), [
            'kode_otp' => $this->pengirimOtp->kodeTerakhirUntuk($pemohon->no_hp),
        ])->assertRedirect(url($tujuan))
            ->assertSessionHas('pemohon_id', $pemohon->id)
            ->assertSessionMissing('url.intended');
    }

    public function test_intended_tidak_aman_ditolak_dan_dialihkan_ke_tujuan_default(): void
    {
        $pemohon = $this->buatPemohon('6281234567815', 'Pemohon Redirect Aman');

        foreach (['https://jahat.example/path', '//jahat.example/path', '/\\jahat.example/path'] as $tujuan) {
            $this->withSession(['url.intended' => $tujuan])
                ->post(route('pemohon.masuk.kirim-otp'), [
                    'no_hp' => $pemohon->no_hp,
                ])->assertRedirect(route('otp.form'));

            $this->post(route('otp.verifikasi'), [
                'kode_otp' => $this->pengirimOtp->kodeTerakhirUntuk($pemohon->no_hp),
            ])->assertRedirect(route('pemohon.permintaan.index'))
                ->assertSessionMissing('url.intended');

            $this->post(route('pemohon.keluar'));
        }
    }

    public function test_kirim_ulang_membatalkan_challenge_lama_dan_hanya_kode_baru_yang_berlaku(): void
    {
        $pemohon = $this->buatPemohon('6281234567816', 'Pemohon Kirim Ulang');

        $this->post(route('pemohon.masuk.kirim-otp'), [
            'no_hp' => $pemohon->no_hp,
        ]);

        $otpPertamaId = session('otp_verification_id');
        $kodePertama = $this->pengirimOtp->kodeTerakhirUntuk($pemohon->no_hp);

        $this->travel(61)->seconds();

        $this->post(route('otp.kirim-ulang'))
            ->assertRedirect(route('otp.form'))
            ->assertSessionHas('success');

        $otpKeduaId = session('otp_verification_id');
        $this->assertNotSame($otpPertamaId, $otpKeduaId);
        $this->assertTrue(OtpVerification::findOrFail($otpPertamaId)->expired_at->lessThanOrEqualTo(now()));

        $kodeKedua = $kodePertama === '654321' ? '123456' : '654321';
        OtpVerification::findOrFail($otpKeduaId)->update([
            'kode_otp' => Hash::make($kodeKedua),
        ]);

        $this->from(route('otp.form'))->post(route('otp.verifikasi'), [
            'kode_otp' => $kodePertama,
        ])->assertRedirect(route('otp.form'))
            ->assertSessionHas('error');

        $this->post(route('otp.verifikasi'), [
            'kode_otp' => $kodeKedua,
        ])->assertRedirect(route('pemohon.permintaan.index'))
            ->assertSessionHas('pemohon_id', $pemohon->id);
    }

    public function test_tamu_kembali_ke_unduhan_miliknya_setelah_login_dari_detail_status(): void
    {
        $pemohon = $this->buatPemohon('6281234567817', 'Pemohon Unduhan Intended');
        $permintaan = $this->buatPermintaan(
            $pemohon,
            'BPS/PD/2026/07817',
            'hasil_permintaan/intended-download.pdf',
        );
        Storage::disk('local')->put($permintaan->file_hasil_path, 'hasil intended');

        $this->post(route('cek-status.post'), [
            'nomor_tiket' => $permintaan->nomor_tiket,
            'no_hp' => '081234567817',
        ])->assertRedirect(route('status.cek', $permintaan->nomor_tiket));

        $this->get(route('status.cek', $permintaan->nomor_tiket))->assertOk();

        $this->get(route('permintaan.unduh', $permintaan))
            ->assertRedirect(route('pemohon.masuk'))
            ->assertSessionHas('url.intended', '/permintaan/'.$permintaan->id.'/unduh');

        $this->post(route('pemohon.masuk.kirim-otp'), [
            'no_hp' => $pemohon->no_hp,
        ]);
        $this->post(route('otp.verifikasi'), [
            'kode_otp' => $this->pengirimOtp->kodeTerakhirUntuk($pemohon->no_hp),
        ])->assertRedirect(route('permintaan.unduh', $permintaan));

        $this->get(route('permintaan.unduh', $permintaan))->assertDownload();
    }

    public function test_post_yang_ditolak_tidak_disimpan_sebagai_intended(): void
    {
        $pemohon = $this->buatPemohon('6281234567810', 'Pemohon POST');

        $this->post(route('permintaan.store'), [
            'jenis_data' => 'Data Tidak Boleh Terkirim',
            'tujuan_penggunaan' => 'Menguji intended POST',
            'periode_data' => '2026',
            'kategori_id' => null,
        ])->assertRedirect(route('pemohon.masuk'))
            ->assertSessionMissing('url.intended');

        $this->post(route('pemohon.masuk.kirim-otp'), [
            'no_hp' => $pemohon->no_hp,
        ]);

        $this->post(route('otp.verifikasi'), [
            'kode_otp' => $this->pengirimOtp->kodeTerakhirUntuk($pemohon->no_hp),
        ])->assertRedirect(route('pemohon.permintaan.index'));

        $this->assertDatabaseCount('permintaan_data', 0);
    }

    public function test_sesi_pemohon_yang_sudah_tidak_ada_ditolak_dan_dibersihkan(): void
    {
        $pemohon = $this->buatPemohon('6281234567811', 'Pemohon Dihapus');
        $pemohonId = $pemohon->id;
        $pemohon->delete();

        $this->withSession([
            'pemohon_id' => $pemohonId,
            'pemohon_otp' => '6281234567811',
            'pemohon_nama' => 'Pemohon Dihapus',
            'otp_nomor' => '6281234567811',
        ])->get(route('permintaan.create'))
            ->assertRedirect(route('pemohon.masuk'))
            ->assertSessionHas('url.intended', '/permintaan/create')
            ->assertSessionMissing([
                'pemohon_id',
                'pemohon_otp',
                'pemohon_nama',
                'otp_nomor',
            ]);
    }

    public function test_pemohon_yang_sudah_masuk_tidak_dapat_membuka_form_masuk_atau_daftar(): void
    {
        $pemohon = $this->buatPemohon('6281234567812', 'Pemohon Aktif');

        $this->withSession(['pemohon_id' => $pemohon->id])
            ->get(route('pemohon.masuk'))
            ->assertRedirect(route('pemohon.permintaan.index'));

        $this->get(route('pemohon.daftar'))
            ->assertRedirect(route('pemohon.permintaan.index'));
    }

    public function test_riwayat_dan_file_hasil_hanya_dapat_diakses_oleh_pemiliknya(): void
    {
        $pemohonA = $this->buatPemohon('6281234567813', 'Pemohon A');
        $pemohonB = $this->buatPemohon('6281234567814', 'Pemohon B');
        $permintaanA = $this->buatPermintaan(
            $pemohonA,
            'BPS/PD/2026/07813',
            'hasil_permintaan/pemohon-a.pdf',
        );
        $permintaanB = $this->buatPermintaan(
            $pemohonB,
            'BPS/PD/2026/07814',
            'hasil_permintaan/pemohon-b.pdf',
        );
        Storage::disk('local')->put($permintaanA->file_hasil_path, 'hasil A');
        Storage::disk('local')->put($permintaanB->file_hasil_path, 'hasil B');

        $this->withSession(['pemohon_id' => $pemohonA->id])
            ->get(route('pemohon.permintaan.index'))
            ->assertOk()
            ->assertSee($permintaanA->nomor_tiket)
            ->assertDontSee($permintaanB->nomor_tiket);

        $this->get(route('pemohon.permintaan.show', $permintaanA))->assertOk();
        $this->get(route('pemohon.permintaan.show', $permintaanB))->assertNotFound();
        $this->get(route('permintaan.selesai', $permintaanA))->assertOk();
        $this->get(route('permintaan.selesai', $permintaanB))->assertNotFound();
        $this->get(route('permintaan.unduh', $permintaanB))->assertNotFound();
        $this->assertDatabaseCount('unduhan_log', 0);

        $this->get(route('permintaan.unduh', $permintaanA))->assertDownload();
        $this->assertDatabaseHas('unduhan_log', [
            'permintaan_data_id' => $permintaanA->id,
            'pemohon_id' => $pemohonA->id,
        ]);
    }

    private function buatPemohon(string $noHp, string $nama): Pemohon
    {
        return Pemohon::create([
            'nama' => $nama,
            'no_hp' => $noHp,
            'email' => strtolower(str_replace(' ', '.', $nama)).'@example.com',
            'jenis_pemohon' => 'publik',
            'nama_instansi' => null,
            'no_hp_verified_at' => now(),
        ]);
    }

    private function buatPermintaan(
        Pemohon $pemohon,
        string $nomorTiket,
        string $fileHasilPath,
    ): PermintaanData {
        return PermintaanData::create([
            'nomor_tiket' => $nomorTiket,
            'pemohon_id' => $pemohon->id,
            'kategori_id' => null,
            'jenis_data' => 'Data Pengujian '.$pemohon->id,
            'tujuan_penggunaan' => 'Menguji isolasi riwayat pemohon.',
            'periode_data' => '2026',
            'status' => 'data_siap',
            'file_hasil_path' => $fileHasilPath,
        ]);
    }
}
