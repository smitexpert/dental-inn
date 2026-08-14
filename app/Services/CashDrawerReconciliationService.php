<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CashMovementType;
use App\Models\CashDrawer;
use App\Models\CashMovement;
use App\Repositories\Contracts\CashDrawerRepository;
use App\Repositories\Contracts\CashMovementRepository;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Pins the drawer's running balance to a physical count. If the counted
 * balance differs from the system balance, a single adjustment movement
 * records the variance and updates the drawer. The drawer's
 * `last_reconciled_at` / `last_reconciled_balance` columns are stamped
 * either way so we have an audit trail of when reconciliation happened.
 */
class CashDrawerReconciliationService
{
    public function __construct(
        private readonly CashDrawerRepository $drawers,
        private readonly CashMovementRepository $movements,
        private readonly CashDrawerService $drawerService,
    ) {}

    /**
     * Compute the snapshot shown on the reconciliation page for a given currency.
     *
     * @return array{balance: string, last_reconciled_at: ?Carbon, last_reconciled_balance: ?string, recent_movements: int}
     */
    public function snapshot(string $currencyCode): array
    {
        $drawer = $this->drawers->findByCodeOrFail($currencyCode);
        $recent = $this->movements->countByType(CashMovementType::CashIn, $currencyCode)
            + $this->movements->countByType(CashMovementType::CashOut, $currencyCode)
            + $this->movements->countByType(CashMovementType::ExchangeIn, $currencyCode)
            + $this->movements->countByType(CashMovementType::ExchangeOut, $currencyCode);

        return [
            'balance' => (string) $drawer->balance,
            'last_reconciled_at' => $drawer->last_reconciled_at,
            'last_reconciled_balance' => $drawer->last_reconciled_balance !== null
                ? (string) $drawer->last_reconciled_balance
                : null,
            'recent_movements' => $recent,
        ];
    }

    public function run(string $currencyCode, string $countedBalance, int $userId): CashMovement
    {
        if ((float) $countedBalance < 0) {
            throw new RuntimeException('Counted balance cannot be negative.');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($currencyCode, $countedBalance, $userId): CashMovement {
            $drawer = $this->drawers->lockByCode($currencyCode);
            $expected = (float) $drawer->balance;
            $counted = (float) $countedBalance;
            $delta = $counted - $expected;

            $movement = null;
            if (abs($delta) > 0.0001) {
                $movement = $this->drawerService->adjust(
                    $currencyCode,
                    number_format($delta, 4, '.', ''),
                    sprintf('Reconciliation variance: expected %.4f, counted %.4f', $expected, $counted),
                    $userId,
                );
            }

            $drawer->last_reconciled_at = now();
            $drawer->last_reconciled_balance = number_format($counted, 4, '.', '');
            $drawer->save();

            return $movement ?? $this->movements->create([
                'currency_code' => $currencyCode,
                'type' => CashMovementType::Adjustment,
                'amount' => '0.0000',
                'reason' => 'Reconciled with no variance',
                'performed_by' => $userId,
            ]);
        });
    }
}
