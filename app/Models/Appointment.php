<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $patient_name
 * @property ?string $patient_phone
 * @property ?int $patient_id
 * @property int $doctor_id
 * @property Carbon $scheduled_at
 * @property int $duration_minutes
 * @property AppointmentStatus $status
 * @property ?string $notes
 * @property int $created_by
 * @property ?Carbon $cancelled_at
 * @property ?string $cancelled_reason
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Doctor $doctor
 * @property-read User $creator
 */
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'patient_name',
        'patient_phone',
        'patient_id',
        'doctor_id',
        'scheduled_at',
        'duration_minutes',
        'status',
        'notes',
        'created_by',
        'cancelled_at',
        'cancelled_reason',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'duration_minutes' => 'integer',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function endAt(): Carbon
    {
        return $this->scheduled_at->copy()->addMinutes($this->duration_minutes);
    }

    /**
     * @param  Builder<Appointment>  $query
     * @return Builder<Appointment>
     */
    public function scopeBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->where('scheduled_at', '<', $end)
            ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?', [$start]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'scheduled_at', 'doctor_id', 'patient_name'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
