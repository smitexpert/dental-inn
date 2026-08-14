<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DoctorFactory;
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
 * @property int $user_id
 * @property ?string $specialization
 * @property ?string $license_number
 * @property ?string $bio
 * @property ?int $years_of_experience
 * @property ?string $consultation_fee
 * @property string $color
 * @property bool $is_available
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read User $user
 * @property-read Collection<int, DoctorSchedule> $schedules
 * @property-read Collection<int, DoctorTimeOff> $timeOffs
 * @property-read Collection<int, Appointment> $appointments
 */
class Doctor extends Model
{
    /** @use HasFactory<DoctorFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'specialization',
        'license_number',
        'bio',
        'years_of_experience',
        'consultation_fee',
        'color',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'years_of_experience' => 'integer',
            'consultation_fee' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class)->orderBy('day_of_week')->orderBy('start_time');
    }

    public function timeOffs(): HasMany
    {
        return $this->hasMany(DoctorTimeOff::class)->orderBy('start_at');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['specialization', 'license_number', 'is_available', 'consultation_fee', 'color'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
