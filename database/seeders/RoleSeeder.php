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
            'view-patients',
            'manage-patients',
            'view-treatment-records',
            'manage-treatment-records',
            'view-prescriptions',
            'manage-prescriptions',
            'view-products',
            'manage-products',
            'view-suppliers',
            'manage-suppliers',
            'view-stock-movements',
            'record-stock-movements',
            'manage-invoices',
            'view-invoices',
            'manage-payments',
            'view-payments',
            'manage-expenses',
            'view-expenses',
            'approve-expenses',
            'view-reports',
            'manage-chart-of-accounts',
            'manage-accounts',
            'view-accounts',
            'record-transfers',
            'view-transfers',
            'reconcile-accounts',
            'manage-notification-templates',
            'view-notification-logs',
            'send-notifications',
            'manage-notification-settings',
            'manage-roles',
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
            'view-patients',
            'view-treatment-records',
            'manage-treatment-records',
            'view-prescriptions',
            'manage-prescriptions',
            'view-products',
            'view-suppliers',
            'view-stock-movements',
            'view-invoices',
            'view-payments',
            'view-expenses',
            'view-reports',
            'view-accounts',
            'view-transfers',
            'view-notification-logs',
        ]);

        $receptionist = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        $receptionist->syncPermissions([
            'view-dashboard',
            'view-appointments',
            'view-doctors',
            'manage-appointments',
            'cancel-appointments',
            'check-in-appointments',
            'view-patients',
            'view-treatment-records',
            'view-prescriptions',
            'manage-patients',
            'view-products',
            'view-suppliers',
            'view-stock-movements',
            'record-stock-movements',
            'manage-invoices',
            'view-invoices',
            'manage-payments',
            'view-payments',
            'manage-expenses',
            'view-expenses',
            'view-accounts',
            'record-transfers',
            'view-transfers',
            'view-notification-logs',
            'send-notifications',
        ]);
    }
}
