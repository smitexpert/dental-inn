<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TreatmentStatus;
use Database\Factories\TreatmentRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $patient_id
 * @property ?int $doctor_id
 * @property ?int $appointment_id
 * @property Carbon $visited_at
 * @property ?string $chief_complaint
 * @property ?string $diagnosis
 * @property ?string $treatment_notes
 * @property ?array<string, mixed> $tooth_chart
 * @property ?Carbon $follow_up_date
 * @property TreatmentStatus $status
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Patient $patient
 * @property-read ?Doctor $doctor
 * @property-read ?Appointment $appointment
 */
class TreatmentRecord extends Model
{
    /** @use HasFactory<TreatmentRecordFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_id',
        'visited_at',
        'chief_complaint',
        'diagnosis',
        'treatment_notes',
        'tooth_chart',
        'follow_up_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'follow_up_date' => 'date',
            'tooth_chart' => 'array',
            'status' => TreatmentStatus::class,
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function isEditable(): bool
    {
        return $this->status === TreatmentStatus::InProgress;
    }

    /**
     * @param  Builder<TreatmentRecord>  $query
     * @return Builder<TreatmentRecord>
     */
    public function scopeForPatient(Builder $query, Patient $patient): Builder
    {
        return $query->where('patient_id', $patient->id);
    }

    /**
     * @param  Builder<TreatmentRecord>  $query
     * @return Builder<TreatmentRecord>
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', TreatmentStatus::InProgress->value);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'doctor_id', 'visited_at', 'follow_up_date'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
