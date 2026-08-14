<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\CashDrawer;
use Illuminate\Database\Eloquent\Collection;

interface CashDrawerRepository
{
    /** @return Collection<int, CashDrawer> */
    public function all(): Collection;

    public function findByCode(string $code): ?CashDrawer;

    public function findByCodeOrFail(string $code): CashDrawer;

    public function findOrCreate(string $code): CashDrawer;

    /**
     * Acquire a row lock for the given currency's drawer. Must be
     * called from inside a DB transaction.
     */
    public function lockByCode(string $code): CashDrawer;

    public function incrementBalance(string $code, string $delta): void;

    public function create(array $data): CashDrawer;
}
