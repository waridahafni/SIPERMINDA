<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $name = env('INITIAL_ADMIN_NAME');
        $email = env('INITIAL_ADMIN_EMAIL');
        $password = env('INITIAL_ADMIN_PASSWORD');

        $passwordKuat = is_string($password)
            && strlen($password) >= 12
            && preg_match('/[a-z]/', $password)
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[0-9]/', $password);

        if (! $name || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $passwordKuat) {
            throw new \RuntimeException(
                'Isi INITIAL_ADMIN_NAME, INITIAL_ADMIN_EMAIL yang valid, dan INITIAL_ADMIN_PASSWORD minimal 12 karakter dengan huruf besar, huruf kecil, serta angka sebelum menjalankan UserSeeder.'
            );
        }

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
            ]
        );

        $admin->assignRole('admin');
    }
}
