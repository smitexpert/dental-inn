<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillPatientsFromAppointments extends Command
{
    protected $signature = 'patients:backfill-from-appointments
        {--dry-run : Report counts without writing}';

    protected $description = 'Create Patient rows from legacy patient_name/patient_phone on appointments and link them.';

    /**
     * @var array<string, int>
     */
    private array $report = [
        'patients_created' => 0,
        'patients_reused' => 0,
        'appointments_linked' => 0,
        'ambiguous_groups' => 0,
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        if ($dry) {
            $this->warn('--dry-run: no writes will be performed.');
        }

        DB::transaction(function () use ($dry): void {
            $this->processByPhone($dry);
            $this->processByName($dry);
            $this->processRemaining($dry);
        });

        $this->info('Backfill complete:');
        foreach ($this->report as $k => $v) {
            $this->line(sprintf('  %-22s %d', $k, $v));
        }

        return self::SUCCESS;
    }

    private function processByPhone(bool $dry): void
    {
        $rows = DB::table('appointments')
            ->select('patient_phone')
            ->whereNotNull('patient_phone')
            ->where('patient_phone', '!=', '')
            ->groupBy('patient_phone')
            ->get();

        foreach ($rows as $row) {
            $phone = (string) $row->patient_phone;
            $existing = Patient::withTrashed()->where('phone', $phone)->first();

            $patientId = $existing?->id;

            if (! $patientId) {
                $names = DB::table('appointments')
                    ->where('patient_phone', $phone)
                    ->pluck('patient_name')
                    ->filter()
                    ->all();

                $uniqueNames = array_values(array_unique($names));
                $name = $uniqueNames[0] ?? 'Unknown';
                if (count($uniqueNames) > 1) {
                    $this->report['ambiguous_groups']++;
                    $this->warn("Phone {$phone} has multiple names: ".implode(', ', $uniqueNames));
                }

                if ($dry) {
                    $this->report['patients_created']++;
                    $patientId = -1;
                } else {
                    $patient = Patient::create([
                        'name' => $name,
                        'phone' => $phone,
                        'is_active' => true,
                    ]);
                    $patientId = $patient->id;
                    $this->report['patients_created']++;
                }
            } else {
                $this->report['patients_reused']++;
            }

            $this->linkAppointments(['patient_phone' => $phone], $patientId, $dry);
        }
    }

    private function processByName(bool $dry): void
    {
        $rows = DB::table('appointments')
            ->whereNull('patient_id')
            ->select('patient_name')
            ->whereNotNull('patient_name')
            ->where('patient_name', '!=', '')
            ->groupBy('patient_name')
            ->get();

        foreach ($rows as $row) {
            $name = (string) $row->patient_name;
            $existing = Patient::withTrashed()->where('name', $name)->whereNull('phone')->first();

            $patientId = $existing?->id;

            if (! $patientId) {
                if ($dry) {
                    $this->report['patients_created']++;
                    $patientId = -1;
                } else {
                    $patient = Patient::create([
                        'name' => $name,
                        'is_active' => true,
                    ]);
                    $patientId = $patient->id;
                    $this->report['patients_created']++;
                }
            } else {
                $this->report['patients_reused']++;
            }

            $this->linkAppointments(['patient_name' => $name, 'patient_phone' => null], $patientId, $dry);
        }
    }

    private function processRemaining(bool $dry): void
    {
        $rows = DB::table('appointments')
            ->whereNull('patient_id')
            ->get();

        foreach ($rows as $row) {
            $name = (string) ($row->patient_name ?? 'Unknown');

            if ($dry) {
                $this->report['patients_created']++;
                $this->linkAppointments(['id' => $row->id], -1, $dry);

                continue;
            }

            $patient = Patient::create([
                'name' => $name,
                'is_active' => true,
            ]);
            $this->report['patients_created']++;
            $this->linkAppointments(['id' => $row->id], $patient->id, $dry);
        }
    }

    /**
     * @param  array<string, mixed>  $where
     */
    private function linkAppointments(array $where, int $patientId, bool $dry): void
    {
        $q = DB::table('appointments')->whereNull('patient_id');
        foreach ($where as $col => $val) {
            if ($val === null) {
                $q->whereNull($col);
            } else {
                $q->where($col, $val);
            }
        }

        if ($dry) {
            $count = $q->count();
            $this->report['appointments_linked'] += $count;

            return;
        }

        $count = $q->update(['patient_id' => $patientId]);
        $this->report['appointments_linked'] += $count;
    }
}
