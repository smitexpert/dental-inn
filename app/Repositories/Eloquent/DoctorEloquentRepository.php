<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Doctor;
use App\Models\User;
use App\Repositories\Contracts\DoctorRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DoctorEloquentRepository implements DoctorRepository
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $q = Doctor::query()->with('user');

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $q->where(function ($w) use ($term): void {
                $w->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%"))
                    ->orWhere('specialization', 'like', "%{$term}%");
            });
        }

        if (! empty($filters['specialization'])) {
            $q->where('specialization', $filters['specialization']);
        }

        return $q->orderBy(
            User::query()->select('name')->whereColumn('users.id', 'doctors.user_id'),
        )->paginate($perPage)->withQueryString();
    }

    public function create(array $data): Doctor
    {
        return DB::transaction(function () use ($data): Doctor {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password'] ?? 'password'),
                'is_active' => $data['is_active'] ?? true,
            ]);
            $user->assignRole('doctor');

            return Doctor::create([
                'user_id' => $user->id,
                'specialization' => $data['specialization'] ?? null,
                'license_number' => $data['license_number'] ?? null,
                'bio' => $data['bio'] ?? null,
                'years_of_experience' => $data['years_of_experience'] ?? null,
                'consultation_fee' => $data['consultation_fee'] ?? null,
                'color' => $data['color'] ?? '#0EA5E9',
                'is_available' => $data['is_available'] ?? true,
            ]);
        });
    }

    public function update(Doctor $doctor, array $data): Doctor
    {
        return DB::transaction(function () use ($doctor, $data): Doctor {
            $user = $doctor->user;
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ]);
            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }
            if (array_key_exists('is_active', $data)) {
                $user->is_active = (bool) $data['is_active'];
            }
            $user->save();

            $doctor->fill([
                'specialization' => $data['specialization'] ?? null,
                'license_number' => $data['license_number'] ?? null,
                'bio' => $data['bio'] ?? null,
                'years_of_experience' => $data['years_of_experience'] ?? null,
                'consultation_fee' => $data['consultation_fee'] ?? null,
                'color' => $data['color'] ?? $doctor->color,
                'is_available' => $data['is_available'] ?? $doctor->is_available,
            ]);
            $doctor->save();

            return $doctor->fresh('user');
        });
    }

    public function toggleAvailability(Doctor $doctor): Doctor
    {
        $doctor->update(['is_available' => ! $doctor->is_available]);

        return $doctor;
    }
}
