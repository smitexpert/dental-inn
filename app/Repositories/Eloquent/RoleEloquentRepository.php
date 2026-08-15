<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\RoleRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;
use Spatie\Permission\Models\Role;

class RoleEloquentRepository implements RoleRepository
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $q = Role::query()->with('permissions');

        if (! empty($filters['q'])) {
            $q->where('name', 'like', "%{$filters['q']}%");
        }

        return $q->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function findByIdOrFail(int|string $id): Role
    {
        return Role::query()->with('permissions')->findOrFail($id);
    }

    public function create(array $data): Role
    {
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return $role->load('permissions');
    }

    public function update(Role $role, array $data): Role
    {
        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return $role->fresh('permissions');
    }

    public function delete(Role $role): void
    {
        $count = $this->userCount($role);

        if ($count > 0) {
            throw new RuntimeException("Cannot delete role '{$role->name}': {$count} user(s) still have this role.");
        }

        $role->delete();
    }

    public function userCount(Role $role): int
    {
        return $role->users()->count();
    }
}