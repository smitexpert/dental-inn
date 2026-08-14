<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Run the data-migration command. The command itself is idempotent
        // (it skips appointments that already have a patient_id).
        Artisan::call('patients:backfill-from-appointments');

        // Sanity: every appointment must now have a patient_id.
        $unlinked = (int) DB::table('appointments')->whereNull('patient_id')->count();
        if ($unlinked > 0) {
            throw new RuntimeException(
                "Backfill left {$unlinked} appointment(s) without a patient_id. ".
                'Re-run `php artisan patients:backfill-from-appointments` to diagnose.',
            );
        }
    }

    public function down(): void
    {
        // Best-effort undo: detach every appointment from its patient.
        DB::table('appointments')->update(['patient_id' => null]);
    }
};
