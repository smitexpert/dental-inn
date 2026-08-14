<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Settings\ClinicSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdateClinicSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'manage-settings', 'guard_name' => 'web']);
        $admin->givePermissionTo($perm);
    }

    public function test_admin_can_update_clinic_settings(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($user);

        $response = $this->patch('/settings', [
            'clinic_name' => 'New Clinic',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'currency_decimals' => 2,
            'currency_position' => 'before',
            'country' => 'US',
            'timezone' => 'America/New_York',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'fiscal_year_start' => '01-01',
            'invoice_prefix' => 'INV-',
            'next_invoice_number' => 1,
            'appointment_slot_minutes' => '30',
        ]);

        $response->assertRedirect();
        $settings = app(ClinicSettings::class);
        $this->assertSame('New Clinic', $settings->clinic_name);
        $this->assertSame('USD', $settings->currency_code);
    }

    public function test_non_admin_cannot_update_settings(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('doctor');
        $this->actingAs($user);

        $response = $this->patch('/settings', [
            'clinic_name' => 'Hax',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'currency_decimals' => 2,
            'currency_position' => 'before',
            'country' => 'US',
            'timezone' => 'America/New_York',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'fiscal_year_start' => '01-01',
            'invoice_prefix' => 'INV-',
            'next_invoice_number' => 1,
            'appointment_slot_minutes' => '30',
        ]);

        $response->assertStatus(403);
    }
}
