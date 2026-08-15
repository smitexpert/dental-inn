<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserEloquentRepository implements UserRepository
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $q = User::query()->with('roles');

        if (! empty($filters['q'])) {
            $q->where(function ($w) use ($filters) {
                $w->where('name', 'like', "%{$filters['q']}%")
                    ->orWhere('email', 'like', "%{$filters['q']}%");
            });
        }

        if (! empty($filters['role'])) {
            $q->whereHas('roles', fn ($w) => $w->where('name', $filters['role']));
        }

        return $q->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function create(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => $data['is_active'] ?? true,
        ]);
        if (! empty($data['role'])) {
            $user->assignRole($data['role']);
        }

        return $user;
    }

    public function update(User $user, array $data): User
    {
        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['is_active'] ?? $user->is_active,
        ];
        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }
        $user->update($payload);
        if (! empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return $user->fresh('roles');
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function toggleActive(User $user): User
    {
        $user->update(['is_active' => ! $user->is_active]);

        return $user;
    }
}
