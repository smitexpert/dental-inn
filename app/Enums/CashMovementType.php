<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Type of cash movement recorded against a bank_account.
 *
 * Sign convention:
 *   - Inflows (`cash_in`, `transfer_in`, `sale`, `refund`) → amount is positive.
 *   - Outflows (`cash_out`, `transfer_out`, `expense`)    → amount is negative.
 *   - `adjustment`                                        → sign reflects direction.
 */
enum CashMovementType: string
{
    case CashIn = 'cash_in';
    case CashOut = 'cash_out';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    case Expense = 'expense';
    case Refund = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::CashIn => 'Cash in',
            self::CashOut => 'Cash out',
            self::TransferIn => 'Transfer in',
            self::TransferOut => 'Transfer out',
            self::Adjustment => 'Adjustment',
            self::Sale => 'Sale',
            self::Expense => 'Expense',
            self::Refund => 'Refund',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CashIn, self::TransferIn, self::Sale, self::Refund => 'success',
            self::CashOut, self::TransferOut, self::Expense => 'danger',
            self::Adjustment => 'warning',
        };
    }

    public function isInflow(): bool
    {
        return in_array($this, [
            self::CashIn,
            self::TransferIn,
            self::Sale,
            self::Refund,
        ], true);
    }

    public function isOutflow(): bool
    {
        return in_array($this, [
            self::CashOut,
            self::TransferOut,
            self::Expense,
        ], true);
    }

    public function isTransfer(): bool
    {
        return $this === self::TransferIn || $this === self::TransferOut;
    }
}
