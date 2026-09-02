<?php

declare(strict_types=1);

namespace Tests\Feature\TreatmentRecords;

use App\Enums\TreatmentStatus;
use App\Models\Patient;
use App\Models\TreatmentRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Round-trip coverage for the structured tooth_chart shape:
 *
 *   { teeth: [{ fdi: int, procedures: [{ code, name, surface? }] }] }
 *
 * The picker (resources/js/Components/ToothChartPicker.vue) emits this
 * shape; these tests assert the controller stores it, re-serves it on the
 * Show page (the page the user reported as "not showing any selected
 * tooth or any chart"), and rejects the legacy flat shape that used to
 * be emitted.
 */
class ToothChartShapeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-treatment-records', 'guard_name' => 'web']));
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'manage-treatment-records', 'guard_name' => 'web']));
    }

    public function test_structured_tooth_chart_round_trips_through_store_and_show(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create();

        $chart = [
            'teeth' => [
                [
                    'fdi' => 16,
                    'procedures' => [
                        ['code' => 'D2140', 'name' => 'Amalgam, one surface', 'surface' => 'occlusal'],
                        ['code' => 'D1110', 'name' => 'Adult prophylaxis (cleaning)'],
                    ],
                ],
                [
                    'fdi' => 26,
                    'procedures' => [
                        ['code' => 'D2740', 'name' => 'Crown, porcelain/ceramic'],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin)->post("/patients/{$patient->id}/treatment-records", [
            'visited_at' => now()->format('Y-m-d H:i:s'),
            'tooth_chart' => $chart,
            'status' => TreatmentStatus::InProgress->value,
        ]);

        $response->assertRedirect();
        $record = TreatmentRecord::first();
        $this->assertNotNull($record, 'Treatment record should have been created');

        // Stored JSON matches what we sent.
        $this->assertSame(16, $record->tooth_chart['teeth'][0]['fdi']);
        $this->assertSame('D2140', $record->tooth_chart['teeth'][0]['procedures'][0]['code']);
        $this->assertSame('occlusal', $record->tooth_chart['teeth'][0]['procedures'][0]['surface']);
        $this->assertSame(26, $record->tooth_chart['teeth'][1]['fdi']);

        // The Show page must render with a 200 — and pass the chart back to
        // the Vue component so the user can see what they picked.
        $show = $this->actingAs($admin)->get("/patients/{$patient->id}/treatment-records/{$record->id}");
        $show->assertOk();
        $show->assertInertia(fn ($page) => $page
            ->component('Patients/TreatmentRecords/Show')
            ->where('record.tooth_chart.teeth.0.fdi', 16)
            ->where('record.tooth_chart.teeth.0.procedures.0.code', 'D2140')
            ->where('record.tooth_chart.teeth.0.procedures.0.surface', 'occlusal')
            ->where('record.tooth_chart.teeth.1.fdi', 26)
        );
    }

    public function test_empty_chart_default_is_accepted(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create();

        $response = $this->actingAs($admin)->post("/patients/{$patient->id}/treatment-records", [
            'visited_at' => now()->format('Y-m-d H:i:s'),
            'tooth_chart' => ['teeth' => []],
            'status' => TreatmentStatus::InProgress->value,
        ]);

        $response->assertRedirect();
        $this->assertSame(['teeth' => []], TreatmentRecord::first()->tooth_chart);
    }

    public function test_missing_tooth_chart_is_accepted(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create();

        $response = $this->actingAs($admin)->post("/patients/{$patient->id}/treatment-records", [
            'visited_at' => now()->format('Y-m-d H:i:s'),
            'status' => TreatmentStatus::InProgress->value,
        ]);

        $response->assertRedirect();
        $this->assertNull(TreatmentRecord::first()->tooth_chart);
    }

    public function test_invalid_fdi_outside_universal_range_is_rejected(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create();

        $response = $this->actingAs($admin)->post("/patients/{$patient->id}/treatment-records", [
            'visited_at' => now()->format('Y-m-d H:i:s'),
            'tooth_chart' => [
                'teeth' => [
                    ['fdi' => 99, 'procedures' => []],
                ],
            ],
            'status' => TreatmentStatus::InProgress->value,
        ]);

        $response->assertSessionHasErrors('tooth_chart.teeth.0.fdi');
    }

    public function test_update_preserves_structured_shape(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create();
        $record = TreatmentRecord::factory()->forPatient($patient)->create([
            'tooth_chart' => null,
        ]);

        $response = $this->actingAs($admin)->patch(
            "/patients/{$patient->id}/treatment-records/{$record->id}",
            [
                'visited_at' => $record->visited_at->format('Y-m-d H:i:s'),
                'tooth_chart' => [
                    'teeth' => [
                        [
                            'fdi' => 36,
                            'procedures' => [
                                ['code' => 'D2391', 'name' => 'Resin, one surface (posterior)', 'surface' => 'occlusal'],
                            ],
                        ],
                    ],
                ],
                'status' => TreatmentStatus::InProgress->value,
            ],
        );

        $response->assertRedirect();
        $record->refresh();
        $this->assertSame(36, $record->tooth_chart['teeth'][0]['fdi']);
        $this->assertSame('D2391', $record->tooth_chart['teeth'][0]['procedures'][0]['code']);
        $this->assertSame('occlusal', $record->tooth_chart['teeth'][0]['procedures'][0]['surface']);
    }
}
