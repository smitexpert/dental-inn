<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CashMovementType;
use App\Models\CashMovement;
use App\Models\ExchangeRate;
use App\Repositories\Contracts\CashDrawerRepository;
use App\Repositories\Contracts\CashMovementRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Authoritative writer for cash drawer activity.
 *
 * Every public method opens a `DB::transaction`, locks the affected
 * drawer rows, updates their balances, and inserts one or two immutable
 * `cash_movements` rows. The drawer balance is the sum of all movement
 * amounts — never set directly to an arbitrary value except by the
 * adjustment / reconciliation path.
 */
class CashDrawerService
{
    public function __construct(
        private readonly CashDrawerRepository $drawers,
        private readonly CashMovementRepository $movements,
        private readonly ExchangeRateService $rates,
    ) {}

    public function cashIn(
        string $currencyCode,
        string $amount,
        string $reason,
        ?Model $reference,
        int $userId,
        ?string $referenceMorph = null,
    ): CashMovement {
        $this->assertPositive($amount, 'cash in');

        return $this->writeSingle(
            $currencyCode,
            CashMovementType::CashIn,
            $amount,
            $reason,
            $reference,
            $referenceMorph,
            $userId,
        );
    }

    public function cashOut(
        string $currencyCode,
        string $amount,
        string $reason,
        ?Model $reference,
        int $userId,
        ?string $referenceMorph = null,
    ): CashMovement {
        $this->assertPositive($amount, 'cash out');

        return DB::transaction(function () use (
            $currencyCode,
            $amount,
            $reason,
            $reference,
            $referenceMorph,
            $userId,
        ): CashMovement {
            $drawer = $this->drawers->lockByCode($currencyCode);
            $this->assertSufficient($drawer->balance, $amount, $currencyCode);

            $movement = $this->insertMovement(
                $currencyCode,
                CashMovementType::CashOut,
                '-'.number_format((float) $amount, 4, '.', ''),
                $reason,
                $reference,
                $referenceMorph,
                $userId,
            );

            $this->drawers->incrementBalance($currencyCode, '-'.number_format((float) $amount, 4, '.', ''));

            return $movement;
        });
    }

    /**
     * Exchange `amount` of `from` currency for the equivalent in `to`
     * currency at the most recent rate. Returns both movements in
     * canonical order: [fromOut, toIn].
     *
     * @return array{0: CashMovement, 1: CashMovement}
     */
    public function exchange(
        string $from,
        string $to,
        string $amount,
        ?int $userId,
        ?ExchangeRate $rateOverride = null,
    ): array {
        $this->assertPositive($amount, 'exchange');

        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            throw new RuntimeException('Cannot exchange a currency into itself.');
        }

        $rate = $rateOverride ?? $this->rates->getRate($from, $to);
        $counter = number_format(((float) $amount) * (float) $rate->rate, 4, '.', '');

        return DB::transaction(function () use ($from, $to, $amount, $counter, $rate, $userId): array {
            // Lock drawers in a deterministic order to avoid deadlocks.
            [$first, $second] = strcmp($from, $to) < 0 ? [$from, $to] : [$to, $from];
            $this->drawers->lockByCode($first);
            $this->drawers->lockByCode($second);

            $sourceDrawer = $this->drawers->lockByCode($from);
            $this->assertSufficient($sourceDrawer->balance, $amount, $from);

            $groupId = (string) Str::uuid();

            $out = $this->insertMovement(
                $from,
                CashMovementType::ExchangeOut,
                '-'.number_format((float) $amount, 4, '.', ''),
                sprintf('Exchanged to %s', $to),
                null,
                null,
                $userId,
                [
                    'counter_currency_code' => $to,
                    'counter_amount' => $counter,
                    'exchange_rate_id' => $rate->id > 0 ? $rate->id : null,
                    'exchange_group_id' => $groupId,
                ],
            );

            $in = $this->insertMovement(
                $to,
                CashMovementType::ExchangeIn,
                $counter,
                sprintf('Exchanged from %s', $from),
                null,
                null,
                $userId,
                [
                    'counter_currency_code' => $from,
                    'counter_amount' => number_format((float) $amount, 4, '.', ''),
                    'exchange_rate_id' => $rate->id > 0 ? $rate->id : null,
                    'exchange_group_id' => $groupId,
                ],
            );

            $this->drawers->incrementBalance($from, '-'.number_format((float) $amount, 4, '.', ''));
            $this->drawers->incrementBalance($to, $counter);

            return [$out, $in];
        });
    }

    public function adjust(
        string $currencyCode,
        string $delta,
        string $reason,
        int $userId,
    ): CashMovement {
        if ((float) $delta === 0.0) {
            throw new RuntimeException('Adjustment amount must be non-zero.');
        }

        return DB::transaction(function () use ($currencyCode, $delta, $reason, $userId): CashMovement {
            $drawer = $this->drawers->lockByCode($currencyCode);
            $newBalance = (float) $drawer->balance + (float) $delta;
            if ($newBalance < 0) {
                throw new RuntimeException("Adjustment would drive drawer {$currencyCode} below zero.");
            }

            $movement = $this->insertMovement(
                $currencyCode,
                CashMovementType::Adjustment,
                number_format((float) $delta, 4, '.', ''),
                $reason,
                null,
                null,
                $userId,
            );

            $this->drawers->incrementBalance($currencyCode, number_format((float) $delta, 4, '.', ''));

            return $movement;
        });
    }

    private function writeSingle(
        string $currencyCode,
        CashMovementType $type,
        string $amount,
        string $reason,
        ?Model $reference,
        ?string $referenceMorph,
        int $userId,
    ): CashMovement {
        return DB::transaction(function () use ($currencyCode, $type, $amount, $reason, $reference, $referenceMorph, $userId): CashMovement {
            $drawer = $this->drawers->lockByCode($currencyCode);

            if ($type === CashMovementType::CashOut && (float) $drawer->balance < (float) $amount) {
                throw new RuntimeException("Insufficient cash in drawer {$currencyCode}.");
            }

            $movement = $this->insertMovement(
                $currencyCode,
                $type,
                $type->isInflow() ? number_format((float) $amount, 4, '.', '') : '-'.number_format((float) $amount, 4, '.', ''),
                $reason,
                $reference,
                $referenceMorph,
                $userId,
            );

            $delta = $type->isInflow() ? (float) $amount : -(float) $amount;
            $this->drawers->incrementBalance($currencyCode, number_format($delta, 4, '.', ''));

            return $movement;
        });
    }

    /**
     * @param  array<string, mixed>  $extras
     */
    private function insertMovement(
        string $currencyCode,
        CashMovementType $type,
        string $amount,
        string $reason,
        ?Model $reference,
        ?string $referenceMorph,
        ?int $userId,
        array $extras = [],
    ): CashMovement {
        $data = array_merge([
            'currency_code' => $currencyCode,
            'type' => $type,
            'amount' => $amount,
            'reason' => $reason,
            'performed_by' => $userId,
            'created_at' => now(),
        ], $extras);

        if ($reference instanceof Model) {
            $data['reference_type'] = $referenceMorph ?? $reference->getMorphClass();
            $data['reference_id'] = (int) $reference->getKey();
        }

        return $this->movements->create($data);
    }

    private function assertPositive(string $amount, string $verb): void
    {
        if ((float) $amount <= 0) {
            throw new RuntimeException("Amount for {$verb} must be positive.");
        }
    }

    private function assertSufficient(string $balance, string $amount, string $currencyCode): void
    {
        if ((float) $balance < (float) $amount) {
            throw new RuntimeException("Insufficient cash in drawer {$currencyCode}.");
        }
    }
}
