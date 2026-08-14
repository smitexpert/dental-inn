<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\CashDrawer;
use App\Repositories\Contracts\CashDrawerRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CashDrawerEloquentRepository implements CashDrawerRepository
{
    public function all(): Collection
    {
        return CashDrawer::query()->with('currency')->orderBy('currency_code')->get();
    }

    public function findByCode(string $code): ?CashDrawer
    {
        return CashDrawer::query()->where('currency_code', strtoupper($code))->first();
    }

    public function findByCodeOrFail(string $code): CashDrawer
    {
        return CashDrawer::query()->where('currency_code', strtoupper($code))->firstOrFail();
    }

    /**
     * Lock the drawer row for update. MUST be called inside a DB transaction.
     */
    public function lockByCode(string $code): CashDrawer
    {
        $drawer = CashDrawer::query()
            ->where('currency_code', strtoupper($code))
            ->lockForUpdate()
            ->first();

        if (! $drawer) {
            throw new \RuntimeException("Cash drawer for currency {$code} not found.");
        }

        return $drawer;
    }

    public function incrementBalance(string $code, string $delta): void
    {
        DB::table('cash_drawers')
            ->where('currency_code', strtoupper($code))
            ->update([
                'balance' => DB::raw('balance + '.((float) $delta)),
                'updated_at' => now(),
            ]);
    }

    public function create(array $data): CashDrawer
    {
        $data['currency_code'] = strtoupper((string) $data['currency_code']);
        $drawer = new CashDrawer;
        $drawer->fill($data);
        $drawer->save();

        return $drawer;
    }

    public function findOrCreate(string $code): CashDrawer
    {
        $code = strtoupper($code);

        $drawer = $this->findByCode($code);
        if ($drawer) {
            return $drawer;
        }

        return $this->create(['currency_code' => $code, 'balance' => '0.0000']);
    }

    /**
     * @param  Builder<CashDrawer>  $query
     * @return Builder<CashDrawer>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('currency', fn (Builder $c) => $c->where('is_active', true));
    }
}
