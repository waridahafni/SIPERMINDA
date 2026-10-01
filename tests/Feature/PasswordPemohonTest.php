<?php

namespace Tests\Feature;

use App\Contracts\PengirimOtp;
use App\Models\Pemohon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordPemohonTest extends TestCase
{
    use RefreshDatabase;

    private array $kode = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(PengirimOtp::class, function ($mock) {
            $mock->shouldReceive('kirim')->andReturnUsing(function ($nomor, $kode) {
                $this->kode[] = $kode;

                return 'test';
            });
        });
    }

    private function pemohon(?string $password = 'PasswordUji123'): Pemohon
    {
        return Pemohon::create(['nama' => 'Pemohon', 'no_hp' => '081234567890', 'jenis_pemohon' => 'publik', 'no_hp_verified_at' => now(), 'password' => $password]);
    }

    private function verifikasi(): void
    {
        $this->post(route('pemohon.masuk.kirim-otp'), ['no_hp' => '081234567890'])->assertRedirect(route('otp.form'));
        $this->post(route('otp.verifikasi'), ['kode_otp' => end($this->kode)])->assertRedirect(route('pemohon.password'));
        $this->get(route('pemohon.password'))->assertOk();
    }

    public function test_login_password_tanpa_otp_dan_nomor_varian_diterima(): void
    {
        $pemohon = $this->pemohon();
        $this->withSession(['url.intended' => '/akun/profil'])->post(route('pemohon.masuk.password'), ['no_hp' => '+6281234567890', 'password' => 'PasswordUji123'])
            ->assertRedirect('/akun/profil')->assertSessionHas('pemohon_id', $pemohon->id)->assertSessionMissing('izin_password');
        $this->assertCount(0, $this->kode);
        $this->assertNotSame('PasswordUji123', $pemohon->password);
        $this->assertArrayNotHasKey('password', $pemohon->toArray());
        $this->get(route('pemohon.profil'))->assertOk();
    }

    public function test_password_salah_dan_nomor_tidak_terdaftar_tidak_membuat_sesi(): void
    {
        $this->pemohon();
        foreach (['081234567890', '081234567899'] as $noHp) {
            $this->post(route('pemohon.masuk.password'), ['no_hp' => $noHp, 'password' => 'Salah123'])
                ->assertSessionHasErrors('no_hp')->assertSessionMissing('pemohon_id');
            $this->assertNull(session('_old_input.password'));
        }
        $this->assertCount(0, $this->kode);
    }

    public function test_akun_lama_membuat_password_setelah_otp_lalu_login_tanpa_otp(): void
    {
        $pemohon = $this->pemohon(null);
        $this->verifikasi();
        $this->post(route('pemohon.password.simpan'), ['password' => 'PasswordBaru123', 'password_confirmation' => 'PasswordBaru123'])
            ->assertRedirect(route('pemohon.permintaan.index'))->assertSessionMissing('izin_password');
        $this->assertTrue(Hash::check('PasswordBaru123', $pemohon->fresh()->password));
        $this->assertSame(1, $pemohon->fresh()->auth_version);
        $this->post(route('pemohon.keluar'))->assertRedirect();
        $this->post(route('pemohon.masuk.password'), ['no_hp' => '081234567890', 'password' => 'PasswordBaru123'])
            ->assertRedirect(route('pemohon.permintaan.index'));
        $this->assertCount(1, $this->kode);
    }

    public function test_reset_memerlukan_otp_baru_dan_membatalkan_sesi_lama(): void
    {
        $pemohon = $this->pemohon();
        $this->withSession(['pemohon_id' => $pemohon->id, 'pemohon_auth_version' => 0]);
        $this->post(route('pemohon.password.simpan'), ['password' => 'PasswordBaru123', 'password_confirmation' => 'PasswordBaru123'])->assertRedirect(route('pemohon.pemulihan'));
        $this->verifikasi();
        $this->post(route('pemohon.password.simpan'), ['password' => 'PasswordBaru123', 'password_confirmation' => 'PasswordBaru123'])->assertRedirect();
        $this->post(route('pemohon.password.simpan'), ['password' => 'PasswordLain123', 'password_confirmation' => 'PasswordLain123'])->assertRedirect(route('pemohon.pemulihan'));
        $this->withSession(['pemohon_id' => $pemohon->id, 'pemohon_auth_version' => 0])->get(route('pemohon.profil'))->assertRedirect(route('pemohon.masuk'));
        $this->post(route('pemohon.masuk.password'), ['no_hp' => '081234567890', 'password' => 'PasswordUji123'])->assertSessionHasErrors('no_hp');
    }

    public function test_izin_kedaluwarsa_dan_validasi_password(): void
    {
        $this->pemohon(null);
        $this->verifikasi();
        $this->post(route('pemohon.password.simpan'), ['password' => 'pendek', 'password_confirmation' => 'beda'])->assertSessionHasErrors('password');
        $this->assertNull(session('_old_input.password'));
        $this->travel(11)->minutes();
        $this->post(route('pemohon.password.simpan'), ['password' => 'PasswordBaru123', 'password_confirmation' => 'PasswordBaru123'])->assertRedirect(route('pemohon.pemulihan'));
        $this->assertNull(Pemohon::first()->password);
    }

    public function test_login_dibatasi_per_nomor_dan_menolak_nomor_belum_terverifikasi(): void
    {
        $pemohon = $this->pemohon();
        $pemohon->update(['no_hp_verified_at' => null]);
        $this->post(route('pemohon.masuk.password'), ['no_hp' => '081234567890', 'password' => 'PasswordUji123'])->assertSessionHasErrors('no_hp')->assertSessionMissing('pemohon_id');
        $pemohon->update(['no_hp_verified_at' => now()]);
        for ($i = 0; $i < 4; $i++) {
            $this->post(route('pemohon.masuk.password'), ['no_hp' => '081234567890', 'password' => 'salah'])->assertSessionHasErrors('no_hp');
        }
        $this->post(route('pemohon.masuk.password'), ['no_hp' => '+6281234567890', 'password' => 'PasswordUji123'])
            ->assertSessionHasErrors('no_hp')->assertSessionMissing('pemohon_id');
    }
}
