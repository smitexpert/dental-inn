<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

interface CurrencyRepository
{
    /** @return Collection<int, Currency> */
    public function all(): Collection;

    /** @return Collection<int, Currency> */
    public function active(): Collection;

    public function findByCode(string $code): ?Currency;

    public function findByCodeOrFail(string $code): Currency;

    public function create(array $data): Currency;

    public function update(Currency $currency, array $data): Currency;
}
