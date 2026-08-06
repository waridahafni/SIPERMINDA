<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@bps.go.id',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin');

        $staf = User::create([
            'name' => 'Staf',
            'email' => 'staf@bps.go.id',
            'password' => Hash::make('password'),
        ]);
        $staf->assignRole('staf');

        $kasi = User::create([
            'name' => 'Kasi',
            'email' => 'kasi@bps.go.id',
            'password' => Hash::make('password'),
        ]);
        $kasi->assignRole('kasi');

        $kabid = User::create([
            'name' => 'Kabid',
            'email' => 'kabid@bps.go.id',
            'password' => Hash::make('password'),
        ]);
        $kabid->assignRole('kabid');
    }
}
