<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Akun Admin Utama
        User::updateOrCreate(
            ['email' => 'admin@minimarket.com'],
            [
                'name' => 'Administrator POS',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // 2. Akun Kasir Utama
        User::updateOrCreate(
            ['email' => 'kasir@minimarket.com'],
            [
                'name' => 'Kasir Minimarket',
                'password' => Hash::make('password'),
                'role' => 'kasir',
            ]
        );
    }
}
