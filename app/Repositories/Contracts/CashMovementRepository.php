<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\CashMovementType;
use App\Models\CashMovement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

interface CashMovementRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;

    public function findByIdOrFail(int $id): CashMovement;

    public function sumForAccountSince(int $accountId, Carbon $since): string;

    public function create(array $data): CashMovement;

    public function countByType(CashMovementType $type, ?int $accountId = null): int;
}
