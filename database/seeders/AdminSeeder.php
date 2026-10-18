<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Membuat akun admin. Segera ganti kata sandi setelah login pertama.
     * Jalankan: php artisan db:seed --class=AdminSeeder
     */
    public function run()
    {
        $admin = User::firstOrNew(['email' => 'admin@sewain.test']);
        $admin->name = 'Admin SEWA-IN';
        $admin->password = Hash::make('admin12345');
        $admin->email_verified_at = now();
        $admin->role = 'admin';
        $admin->save();
    }
}
