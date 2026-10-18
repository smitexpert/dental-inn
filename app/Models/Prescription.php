<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PrescriptionStatus;
use Database\Factories\PrescriptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $prescription_number
 * @property int $patient_id
 * @property ?int $doctor_id
 * @property ?int $appointment_id
 * @property ?int $treatment_record_id
 * @property ?Carbon $issued_at
 * @property PrescriptionStatus $status
 * @property ?string $general_advice
 * @property ?string $diagnosis_text
 * @property int $created_by
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Patient $patient
 * @property-read ?Doctor $doctor
 * @property-read ?Appointment $appointment
 * @property-read ?TreatmentRecord $treatmentRecord
 * @property-read User $creator
 * @property-read Collection<int, PrescriptionItem> $items
 */
class Prescription extends Model
{
    /** @use HasFactory<PrescriptionFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'prescription_number',
        'patient_id',
        'doctor_id',
        'appointment_id',
        'treatment_record_id',
        'issued_at',
        'status',
        'general_advice',
        'diagnosis_text',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'status' => PrescriptionStatus::class,
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

    public function treatmentRecord(): BelongsTo
    {
        return $this->belongsTo(TreatmentRecord::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<PrescriptionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class)->orderBy('sort_order');
    }

    public function isEditable(): bool
    {
        return $this->status === PrescriptionStatus::Draft;
    }

    /**
     * @param  Builder<Prescription>  $query
     * @return Builder<Prescription>
     */
    public function scopeForPatient(Builder $query, Patient $patient): Builder
    {
        return $query->where('patient_id', $patient->id);
    }

    /**
     * @param  Builder<Prescription>  $query
     * @return Builder<Prescription>
     */
    public function scopeForAppointment(Builder $query, int $appointmentId): Builder
    {
        return $query->where('appointment_id', $appointmentId);
    }

    public static function generateUniqueNumber(): string
    {
        $year = (string) now()->year;
        $attempts = 0;

        while (true) {
            $count = (int) static::query()
                ->whereYear('created_at', $year)
                ->count();
            $serial = str_pad((string) intval($count + 1), 4, '0', STR_PAD_LEFT);
            $number = "RX-{$year}-{$serial}";

            $exists = static::query()->where('prescription_number', $number)->exists();
            if (! $exists) {
                return $number;
            }

            $attempts++;
            if ($attempts > 3) {
                return 'RX-'.$year.'-'.str_pad((string) intval((microtime(true) * 100) % 10000), 4, '0', STR_PAD_LEFT);
            }
        }
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'doctor_id', 'issued_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
