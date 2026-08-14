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

    public function sumForCurrencySince(string $code, Carbon $since): string;

    /**
     * @return array<string, string> ISO date => signed total
     */
    public function dailyNetForCurrency(string $code, int $days = 7): array;

    public function create(array $data): CashMovement;

    public function countByType(CashMovementType $type, ?string $currency = null): int;
}
