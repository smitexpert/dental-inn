<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Gender;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Throwable;

/**
 * @property int $id
 * @property string $patient_code
 * @property string $name
 * @property ?string $phone
 * @property ?string $email
 * @property ?Carbon $date_of_birth
 * @property ?Gender $gender
 * @property ?string $address_line_1
 * @property ?string $address_line_2
 * @property ?string $city
 * @property ?string $state
 * @property ?string $postal_code
 * @property ?string $country
 * @property ?string $emergency_contact_name
 * @property ?string $emergency_contact_phone
 * @property ?string $medical_history
 * @property ?string $dental_history
 * @property ?string $notes
 * @property bool $is_active
 * @property ?int $created_by
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property ?Carbon $deleted_at
 * @property-read Collection<int, Appointment> $appointments
 * @property-read Collection<int, TreatmentRecord> $treatmentRecords
 * @property-read ?User $creator
 */
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_code',
        'name',
        'phone',
        'email',
        'date_of_birth',
        'gender',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'medical_history',
        'dental_history',
        'notes',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
            'gender' => Gender::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Patient $patient): void {
            if (empty($patient->patient_code)) {
                $patient->patient_code = self::generateUniqueCode();
            }
        });
    }

    public static function generateUniqueCode(): string
    {
        $year = (string) now()->year;
        $attempts = 0;

        while (true) {
            $count = (int) DB::table('patients')
                ->whereYear('created_at', $year)
                ->count();
            $serial = Str::padLeft((string) ($count + 1), 4, '0');
            $code = "P-{$year}-{$serial}";

            $exists = DB::table('patients')->where('patient_code', $code)->exists();
            if (! $exists) {
                return $code;
            }

            $attempts++;
            if ($attempts > 3) {
                // Fall back to timestamp-based suffix to guarantee uniqueness.
                return 'P-'.$year.'-'.Str::padLeft((string) (int) (microtime(true) * 100) % 10000, 4, '0');
            }
        }
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class)->orderByDesc('scheduled_at');
    }

    public function treatmentRecords(): HasMany
    {
        return $this->hasMany(TreatmentRecord::class)->orderByDesc('visited_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'phone', 'email', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function getAgeAttribute(): ?int
    {
        if (! $this->date_of_birth) {
            return null;
        }

        try {
            return $this->date_of_birth->age;
        } catch (Throwable) {
            return null;
        }
    }

    public function getFullAddressAttribute(): ?string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ], fn ($v) => is_string($v) && trim($v) !== '');

        if ($parts === []) {
            return null;
        }

        return implode(', ', $parts);
    }

    /**
     * @param  Builder<Patient>  $query
     * @return Builder<Patient>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Patient>  $query
     * @return Builder<Patient>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $w) use ($like): void {
            $w->where('name', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('patient_code', 'like', $like);
        });
    }

    /**
     * @param  Builder<Patient>  $query
     * @return Builder<Patient>
     */
    public function scopeDuplicatesOf(Builder $query, ?string $phone, ?string $name, ?string $email, ?int $ignoreId = null): Builder
    {
        $q = $query->where(function (Builder $w) use ($phone, $name, $email): void {
            if ($phone) {
                $w->orWhere('phone', $phone);
            }
            if ($name && $email) {
                $w->orWhere(function (Builder $sub) use ($name, $email): void {
                    $sub->where('name', $name)->where('email', $email);
                });
            }
        });

        if ($ignoreId !== null) {
            $q->where('id', '!=', $ignoreId);
        }

        return $q;
    }
}
