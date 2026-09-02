<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

interface RoleRepository
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function findByIdOrFail(int|string $id): Role;

    public function create(array $data): Role;

    public function update(Role $role, array $data): Role;

    public function delete(Role $role): void;

    public function userCount(Role $role): int;
}
