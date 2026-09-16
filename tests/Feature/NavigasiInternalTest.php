<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigasiInternalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_menu_staf_hanya_menampilkan_fitur_yang_dapat_diakses(): void
    {
        $staf = User::factory()->create();
        $staf->assignRole('staf');

        $this->actingAs($staf)
            ->get(route('internal.dashboard'))
            ->assertOk()
            ->assertDontSee('Katalog Data')
            ->assertDontSee('Laporan')
            ->assertDontSee('Manajemen User');
    }

    public function test_menu_kasi_tidak_menawarkan_katalog_yang_akan_403(): void
    {
        $kasi = User::factory()->create();
        $kasi->assignRole('kasi');

        $this->actingAs($kasi)
            ->get(route('internal.dashboard'))
            ->assertOk()
            ->assertSee('Laporan')
            ->assertDontSee('Katalog Data')
            ->assertDontSee('Manajemen User');
    }

    public function test_layout_internal_memiliki_kontrol_drawer_mobile_yang_aksesibel(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('internal.dashboard'))
            ->assertOk()
            ->assertSee('id="navigasi-internal"', false)
            ->assertSee('aria-controls="navigasi-internal"', false)
            ->assertSee('@keydown.escape.window="closeMobileSidebar(true)"', false)
            ->assertSee('class="fixed inset-0 z-40 bg-gray-900/60 md:hidden"', false);
    }

    public function test_link_dashboard_dan_permintaan_juga_mengikuti_izin_individual(): void
    {
        $hanyaDashboard = User::factory()->create();
        $hanyaDashboard->givePermissionTo('lihat-dashboard');

        $this->actingAs($hanyaDashboard)
            ->get(route('internal.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertDontSee('Permintaan Masuk');

        $hanyaPermintaan = User::factory()->create();
        $hanyaPermintaan->givePermissionTo('lihat-permintaan');

        $this->actingAs($hanyaPermintaan)
            ->get(route('internal.permintaan.index'))
            ->assertOk()
            ->assertSee('Permintaan Masuk')
            ->assertDontSee('href="'.route('internal.dashboard').'"', false);
    }
}
