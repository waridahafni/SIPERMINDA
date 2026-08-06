<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            'Sosial', 'Ekonomi', 'Kependudukan', 'Pertanian',
            'Industri', 'Keuangan', 'Pendidikan', 'Kesehatan',
            'Pariwisata', 'Infrastruktur',
        ];

        foreach ($kategori as $nama) {
            DB::table('kategori_data')->insert(['nama' => $nama]);
        }
    }
}
