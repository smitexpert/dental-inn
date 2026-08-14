<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view-dashboard',
            'manage-users',
            'manage-settings',
            'view-appointments',
            'manage-doctors',
            'view-doctors',
            'manage-appointments',
            'view-own-appointments',
            'cancel-appointments',
            'check-in-appointments',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $doctor = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        $doctor->syncPermissions([
            'view-dashboard',
            'view-appointments',
            'view-doctors',
            'view-own-appointments',
        ]);

        $receptionist = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        $receptionist->syncPermissions([
            'view-dashboard',
            'view-appointments',
            'view-doctors',
            'manage-appointments',
            'cancel-appointments',
            'check-in-appointments',
        ]);
    }
}
