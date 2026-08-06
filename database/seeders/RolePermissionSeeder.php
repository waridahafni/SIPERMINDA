<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'approve-level-1', 'approve-level-2', 'upload-dataset',
            'manage-users', 'verifikasi-permintaan', 'upload-hasil', 'lihat-laporan',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $staf = Role::firstOrCreate(['name' => 'staf', 'guard_name' => 'web']);
        $staf->syncPermissions(['verifikasi-permintaan', 'upload-dataset', 'upload-hasil']);

        $kasi = Role::firstOrCreate(['name' => 'kasi', 'guard_name' => 'web']);
        $kasi->syncPermissions(['approve-level-1', 'lihat-laporan']);

        $kabid = Role::firstOrCreate(['name' => 'kabid', 'guard_name' => 'web']);
        $kabid->syncPermissions(['approve-level-2', 'lihat-laporan']);

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
            'manage-users', 'upload-dataset', 'lihat-laporan',
            'verifikasi-permintaan', 'upload-hasil',
            'approve-level-1', 'approve-level-2',
        ]);
    }
}
