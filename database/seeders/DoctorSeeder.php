<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the `doctors` profile rows that the UserSeeder only hints at by
 * creating a `doctor`-role user. Without these rows, /doctors renders
 * an empty list because no Doctor model exists.
 */
class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $demo = User::firstOrCreate(
            ['email' => 'doctor@clinic.test'],
            [
                'name' => 'Dr. Demo',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
        if (! $demo->hasRole('doctor')) {
            $demo->assignRole('doctor');
        }

        Doctor::firstOrCreate(
            ['user_id' => $demo->id],
            [
                'specialization' => 'General Dentistry',
                'license_number' => 'DDS-0001',
                'bio' => 'Demo doctor profile used for local development.',
                'years_of_experience' => 5,
                'consultation_fee' => 50.00,
                'color' => '#0EA5E9',
                'is_available' => true,
            ],
        );

        // A second doctor so the list view has something to render
        // and pagination/filtering can be exercised.
        $second = User::firstOrCreate(
            ['email' => 'doctor2@clinic.test'],
            [
                'name' => 'Dr. Sample',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
        if (! $second->hasRole('doctor')) {
            $second->assignRole('doctor');
        }

        Doctor::firstOrCreate(
            ['user_id' => $second->id],
            [
                'specialization' => 'Orthodontics',
                'license_number' => 'DDS-0002',
                'bio' => 'Second demo doctor.',
                'years_of_experience' => 8,
                'consultation_fee' => 75.00,
                'color' => '#22C55E',
                'is_available' => true,
            ],
        );
    }
}
