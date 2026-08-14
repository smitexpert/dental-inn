<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill guard: this migration must run only after patient_id is
        // populated on every row.
        $unlinked = (int) DB::table('appointments')->whereNull('patient_id')->count();
        if ($unlinked > 0) {
            throw new \RuntimeException(
                "Cannot drop legacy patient fields: {$unlinked} appointment(s) still ".
                'have NULL patient_id. Run `php artisan patients:backfill-from-appointments` first.',
            );
        }

        // Drop the two legacy columns. The patient_id column stays as a real
        // FK. We use Schema::table + dropColumn — supported on MySQL and
        // SQLite (SQLite ≥ 3.35).
        Schema::table('appointments', function ($table): void {
            if (Schema::hasColumn('appointments', 'patient_name')) {
                $table->dropColumn('patient_name');
            }
            if (Schema::hasColumn('appointments', 'patient_phone')) {
                $table->dropColumn('patient_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function ($table): void {
            if (! Schema::hasColumn('appointments', 'patient_name')) {
                $table->string('patient_name', 255)->nullable()->after('patient_id');
            }
            if (! Schema::hasColumn('appointments', 'patient_phone')) {
                $table->string('patient_phone', 32)->nullable()->after('patient_name');
            }
        });
    }
};
