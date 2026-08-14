<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ExchangeRate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

interface ExchangeRateRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;

    public function findByIdOrFail(int $id): ExchangeRate;

    /**
     * Latest rate for the given pair whose effective_at <= $at. Returns null
     * if no rate exists (caller decides whether to throw).
     */
    public function latestForPair(string $from, string $to, ?Carbon $at = null): ?ExchangeRate;

    public function create(array $data): ExchangeRate;

    public function update(ExchangeRate $rate, array $data): ExchangeRate;

    public function delete(ExchangeRate $rate): void;
}
