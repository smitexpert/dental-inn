<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementType: string
{
    case Purchase = 'purchase';
    case Sale = 'sale';
    case Consumption = 'consumption';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Adjustment = 'adjustment';
    case Return = 'return';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Purchase',
            self::Sale => 'Sale',
            self::Consumption => 'Consumption',
            self::TransferIn => 'Transfer in',
            self::TransferOut => 'Transfer out',
            self::Adjustment => 'Adjustment',
            self::Return => 'Return',
        };
    }

    /**
     * Sign applied to the StockLevel when this movement is recorded.
     * +1 means stock comes IN, -1 means stock goes OUT, 0 means no level
     * change (used for global adjustments whose absolute quantity is
     * captured in the movement's quantity field).
     */
    public function sign(): int
    {
        return match ($this) {
            self::Purchase, self::TransferIn, self::Return => 1,
            self::Sale, self::Consumption, self::TransferOut => -1,
            self::Adjustment => 0,
        };
    }

    public function affectsLocation(): bool
    {
        return $this !== self::Adjustment;
    }
}
