<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Type of cash movement recorded against a CashDrawer.
 *
 * Sign convention:
 *   - `*_in` / `sale` / `refund` → amount is positive.
 *   - `*_out` / `expense`       → amount is stored as a negative number.
 *   - `adjustment`              → sign reflects direction (positive for
 *     cash found, negative for cash missing).
 */
enum CashMovementType: string
{
    case CashIn = 'cash_in';
    case CashOut = 'cash_out';
    case ExchangeIn = 'exchange_in';
    case ExchangeOut = 'exchange_out';
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    case Expense = 'expense';
    case Refund = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::CashIn => 'Cash in',
            self::CashOut => 'Cash out',
            self::ExchangeIn => 'Exchange in',
            self::ExchangeOut => 'Exchange out',
            self::Adjustment => 'Adjustment',
            self::Sale => 'Sale',
            self::Expense => 'Expense',
            self::Refund => 'Refund',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CashIn, self::ExchangeIn, self::Sale, self::Refund => 'success',
            self::CashOut, self::ExchangeOut, self::Expense => 'danger',
            self::Adjustment => 'warning',
        };
    }

    public function isInflow(): bool
    {
        return in_array($this, [
            self::CashIn,
            self::ExchangeIn,
            self::Sale,
            self::Refund,
        ], true);
    }

    public function isOutflow(): bool
    {
        return in_array($this, [
            self::CashOut,
            self::ExchangeOut,
            self::Expense,
        ], true);
    }

    public function isExchange(): bool
    {
        return $this === self::ExchangeIn || $this === self::ExchangeOut;
    }
}
