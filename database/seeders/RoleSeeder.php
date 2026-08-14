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
            'manage-exchange-rates',
            'view-exchange-rates',
            'record-cash-movements',
            'view-cash-movements',
            'reconcile-cash-drawer',
            'manage-notification-templates',
            'view-notification-logs',
            'send-notifications',
            'manage-notification-settings',
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
            'view-products',
            'view-suppliers',
            'view-stock-movements',
            'view-invoices',
            'view-payments',
            'view-expenses',
            'view-reports',
            'view-exchange-rates',
            'view-cash-movements',
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
            'view-exchange-rates',
            'record-cash-movements',
            'view-cash-movements',
            'view-notification-logs',
            'send-notifications',
        ]);
    }
}
