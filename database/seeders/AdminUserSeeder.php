<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@luvion.ai'],
            [
                'name' => 'Luvion Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'ign.thierry@gmail.com'],
            [
                'name' => 'NAJIH (Klien)',
                'password' => Hash::make('password123'),
                'role' => 'customer',
                'status' => 'active',
            ]
        );
    }
}
