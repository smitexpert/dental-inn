<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Repositories\Contracts\AppointmentRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class AppointmentEloquentRepository implements AppointmentRepository
{
    public function between(Carbon $start, Carbon $end, ?Doctor $doctor = null): Collection
    {
        $q = Appointment::query()
            ->with('doctor.user')
            ->where('scheduled_at', '<', $end)
            ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?', [$start]);

        if ($doctor) {
            $q->where('doctor_id', $doctor->id);
        }

        return $q->orderBy('scheduled_at')->get();
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $q = Appointment::query()->with('doctor.user');

        if (! empty($filters['doctor_id'])) {
            $q->where('doctor_id', (int) $filters['doctor_id']);
        }
        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }
        if (! empty($filters['from'])) {
            $q->where('scheduled_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $q->where('scheduled_at', '<', $filters['to']);
        }
        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $q->where(function ($w) use ($term): void {
                $w->where('patient_name', 'like', "%{$term}%")
                    ->orWhere('patient_phone', 'like', "%{$term}%");
            });
        }

        return $q->orderBy('scheduled_at', 'desc')->paginate($perPage)->withQueryString();
    }

    public function create(array $data): Appointment
    {
        return Appointment::create($data);
    }

    public function update(Appointment $appointment, array $data): Appointment
    {
        $appointment->fill($data);
        $appointment->save();

        return $appointment;
    }

    public function delete(Appointment $appointment): void
    {
        $appointment->delete();
    }

    public function updateStatus(Appointment $appointment, AppointmentStatus $new, ?string $reason = null): Appointment
    {
        $appointment->status = $new;
        if (in_array($new, [AppointmentStatus::Cancelled, AppointmentStatus::NoShow], true)) {
            $appointment->cancelled_at = now();
            $appointment->cancelled_reason = $reason;
        }
        $appointment->save();

        activity()
            ->performedOn($appointment)
            ->withProperties(['from' => $appointment->getOriginal('status'), 'to' => $new->value])
            ->log("Appointment status changed to {$new->label()}");

        return $appointment;
    }

    public function overlappingCount(int $doctorId, Carbon $start, Carbon $end, ?int $ignoreId = null): int
    {
        $q = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereNotIn('status', [
                AppointmentStatus::Cancelled->value,
                AppointmentStatus::NoShow->value,
            ])
            ->where('scheduled_at', '<', $end)
            ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?', [$start]);

        if ($ignoreId !== null) {
            $q->where('id', '!=', $ignoreId);
        }

        return $q->count();
    }
}
