<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@clinic.test'],
            [
                'name' => 'Clinic Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        )->assignRole('admin');

        User::firstOrCreate(
            ['email' => 'doctor@clinic.test'],
            [
                'name' => 'Dr. Demo',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        )->assignRole('doctor');

        User::firstOrCreate(
            ['email' => 'receptionist@clinic.test'],
            [
                'name' => 'Receptionist Demo',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        )->assignRole('receptionist');
    }
}
