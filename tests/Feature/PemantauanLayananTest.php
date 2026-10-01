<?php

namespace Tests\Feature;

use App\Models\Pemohon;
use App\Models\PermintaanData;
use App\Models\PermintaanFeedback;
use App\Models\PermintaanKendala;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PemantauanLayananTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function permintaan(array $attributes = []): PermintaanData
    {
        $pemohon = Pemohon::create([
            'nama' => 'Pemohon Survei', 'no_hp' => '081234567890',
            'jenis_pemohon' => 'publik', 'no_hp_verified_at' => now(),
        ]);

        return PermintaanData::create(array_merge([
            'nomor_tiket' => 'BPS/PD/2026/00001', 'pemohon_id' => $pemohon->id,
            'jenis_data' => 'Data Sosial', 'tujuan_penggunaan' => 'Riset',
            'periode_data' => '2026', 'status' => 'data_siap',
            'file_hasil_path' => 'hasil_permintaan/uji.pdf',
        ], $attributes));
    }

    private function staf(): User
    {
        $user = User::factory()->create();
        $user->assignRole('staf');

        return $user;
    }

    public function test_staf_dapat_melihat_dan_mengekspor_survei_dengan_filter_dan_csv_aman(): void
    {
        $permintaan = $this->permintaan();
        PermintaanFeedback::create([
            'permintaan_data_id' => $permintaan->id, 'pemohon_id' => $permintaan->pemohon_id,
            'rating' => 4, 'komentar' => '=1+1', 'created_at' => '2026-09-25 10:00:00',
        ]);
        $this->actingAs($this->staf())->get(route('internal.survei.index'))
            ->assertOk()->assertSee('=1+1')->assertSee('4,00');
        $csv = $this->get(route('internal.survei.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=1+1", $csv);
        $this->assertStringContainsString($permintaan->nomor_tiket, $csv);
        $this->get(route('internal.survei.index', ['tanggal_mulai' => '2026-10-01']))
            ->assertOk()->assertDontSee('=1+1');
        $filtered = $this->get(route('internal.survei.export', ['tanggal_mulai' => '2026-10-01']))->streamedContent();
        $this->assertStringNotContainsString($permintaan->nomor_tiket, $filtered);
        $this->get(route('internal.survei.export', ['tanggal_mulai' => '2026-10-02', 'tanggal_selesai' => '2026-10-01']))
            ->assertSessionHasErrors('tanggal_selesai');
    }

    public function test_ekspor_survei_memerlukan_izin_petugas(): void
    {
        $this->get(route('internal.survei.export'))->assertRedirect();
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('internal.survei.export'))->assertForbidden();
        $this->get(route('internal.survei.index'))->assertForbidden();
    }

    public function test_kendala_dapat_dilaporkan_dan_ditanggapi_tanpa_duplikasi_aktif(): void
    {
        $permintaan = $this->permintaan();
        $this->withSession(['pemohon_id' => $permintaan->pemohon_id, 'pemohon_otp' => $permintaan->pemohon->no_hp]);
        $url = route('permintaan.kendala', $permintaan);
        $this->post($url, ['laporan' => 'File tidak terbuka'])->assertRedirect();
        $this->post($url, ['laporan' => 'Laporan duplikat'])->assertSessionHas('error');
        $this->assertDatabaseCount('permintaan_kendala', 1);
        $kendala = PermintaanKendala::firstOrFail();
        $this->get(route('pemohon.permintaan.show', $permintaan))->assertOk()->assertSee('Menunggu tindak lanjut petugas');
        $this->actingAs($this->staf())->get(route('internal.dashboard'))->assertOk()->assertSee('File tidak terbuka');
        $this->post(route('internal.permintaan.kendala.selesai', [$permintaan, $kendala]), ['tanggapan' => 'Silakan buka dengan pembaca PDF.'])->assertRedirect();
        $this->assertNotNull($kendala->fresh()->diselesaikan_at);
        $this->get(route('pemohon.permintaan.show', $permintaan))->assertOk()->assertSee('Silakan buka dengan pembaca PDF.');
        $this->post($url, ['laporan' => 'Masih tidak terbuka'])->assertSessionHas('success');
        $this->assertDatabaseCount('permintaan_kendala', 2);
    }

    public function test_kendala_dibatasi_pemilik_status_dan_izin_penanganan(): void
    {
        $permintaan = $this->permintaan(['status' => 'diajukan', 'file_hasil_path' => null]);
        $this->withSession(['pemohon_id' => $permintaan->pemohon_id, 'pemohon_otp' => $permintaan->pemohon->no_hp]);
        $this->post(route('permintaan.kendala', $permintaan), ['laporan' => 'Uji'])->assertUnprocessable();
        $orangLain = Pemohon::create(['nama' => 'Lain', 'no_hp' => '081234567899', 'jenis_pemohon' => 'publik', 'no_hp_verified_at' => now()]);
        $this->withSession(['pemohon_id' => $orangLain->id, 'pemohon_otp' => $orangLain->no_hp])
            ->post(route('permintaan.kendala', $permintaan), ['laporan' => 'Uji'])->assertNotFound();
        $kendala = $permintaan->kendala()->create(['laporan' => 'Kendala']);
        $kasi = User::factory()->create();
        $kasi->assignRole('kasi');
        $this->actingAs($kasi)->post(route('internal.permintaan.kendala.selesai', [$permintaan, $kendala]), ['tanggapan' => 'Selesai'])->assertForbidden();
    }

    public function test_dashboard_mengingatkan_semua_tahap_aktif_dan_memperhitungkan_akhir_pekan(): void
    {
        config(['layanan.sla_hari_kerja' => 2]);
        $this->travelTo(Carbon::parse('2026-10-03 12:00:00'));
        $permintaan = $this->permintaan(['status' => 'disetujui_kabid', 'created_at' => '2026-10-01 10:00:00']);
        $this->actingAs($this->staf())->get(route('internal.dashboard'))->assertOk()
            ->assertViewHas('permintaanMelewatiSla', fn ($items) => $items->total() === 0);
        $this->travelTo(Carbon::parse('2026-10-05 12:00:00'));
        foreach (PermintaanData::STATUS_AKTIF as $status) {
            $permintaan->update(['status' => $status]);
            $this->get(route('internal.dashboard'))->assertOk()
                ->assertViewHas('permintaanMelewatiSla', fn ($items) => $items->total() === 1);
        }
        $permintaan->update(['status' => 'selesai']);
        $this->get(route('internal.dashboard'))->assertOk()
            ->assertViewHas('permintaanMelewatiSla', fn ($items) => $items->total() === 0);
    }

    public function test_petugas_melihat_catatan_unduhan_pemohon(): void
    {
        $permintaan = $this->permintaan();
        $this->actingAs($this->staf())->get(route('internal.permintaan.show', $permintaan))
            ->assertOk()->assertSee('Pemohon belum mengunduh dokumen');
        $permintaan->unduhanLog()->create(['pemohon_id' => $permintaan->pemohon_id, 'downloaded_at' => now(), 'ip_address' => '127.0.0.1']);
        $this->get(route('internal.permintaan.show', $permintaan))->assertOk()->assertSee('Unduhan diminta 1 kali.');
    }

    public function test_pengingat_dan_detail_memakai_batas_setelah_libur_nasional(): void
    {
        config(['layanan.sla_hari_kerja' => 2]);
        $permintaan = $this->permintaan(['status' => 'diajukan', 'created_at' => '2026-08-14 10:00:00']);
        $this->travelTo(Carbon::parse('2026-08-18 12:00:00'));
        $this->actingAs($this->staf())->get(route('internal.dashboard'))->assertOk()
            ->assertViewHas('permintaanMelewatiSla', fn ($items) => $items->total() === 0);
        $this->get(route('internal.permintaan.show', $permintaan))->assertOk()
            ->assertSee('19 Aug 2026 10:00')->assertDontSee('Melewati batas layanan.');
        $this->travelTo(Carbon::parse('2026-08-19 10:00:00'));
        $this->get(route('internal.dashboard'))->assertOk()
            ->assertViewHas('permintaanMelewatiSla', fn ($items) => $items->total() === 1);
        $this->get(route('internal.permintaan.show', $permintaan))->assertOk()
            ->assertSee('Melewati batas layanan.');
    }
}
