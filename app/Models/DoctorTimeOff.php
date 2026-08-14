<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DoctorTimeOffFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $doctor_id
 * @property Carbon $start_at
 * @property Carbon $end_at
 * @property ?string $reason
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Doctor $doctor
 */
class DoctorTimeOff extends Model
{
    /** @use HasFactory<DoctorTimeOffFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'doctor_id',
        'start_at',
        'end_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['start_at', 'end_at', 'reason'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
