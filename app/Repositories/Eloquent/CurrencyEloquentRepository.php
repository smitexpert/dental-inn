<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Currency;
use App\Repositories\Contracts\CurrencyRepository;
use Illuminate\Database\Eloquent\Collection;

class CurrencyEloquentRepository implements CurrencyRepository
{
    /** @return Collection<int, Currency> */
    public function all(): Collection
    {
        return Currency::query()->orderBy('code')->get();
    }

    /** @return Collection<int, Currency> */
    public function active(): Collection
    {
        return Currency::query()->active()->orderBy('code')->get();
    }

    public function findByCode(string $code): ?Currency
    {
        return Currency::query()->find(strtoupper($code));
    }

    public function findByCodeOrFail(string $code): Currency
    {
        return Currency::query()->findOrFail(strtoupper($code));
    }

    public function create(array $data): Currency
    {
        $data['code'] = strtoupper((string) $data['code']);
        $currency = new Currency;
        $currency->fill($data);
        $currency->save();

        return $currency;
    }

    public function update(Currency $currency, array $data): Currency
    {
        $currency->fill($data);
        $currency->save();

        return $currency;
    }
}
