<?php

namespace Tests\Feature;

use Tests\TestCase;

class HalamanAlurTest extends TestCase
{
    public function test_halaman_alur_dapat_dibuka_dan_menampilkan_seluruh_langkah(): void
    {
        $this->get(route('alur'))
            ->assertOk()
            ->assertSeeInOrder([
                'Daftar atau Masuk',
                'Ajukan Permintaan Data',
                'Permintaan Diverifikasi',
                'Data Disiapkan',
                'Data Diterima & Diunduh',
            ]);
    }
}
