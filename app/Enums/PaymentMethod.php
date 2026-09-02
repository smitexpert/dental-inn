<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';
    case MobileWallet = 'mobile_wallet';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Card => 'Card',
            self::BankTransfer => 'Bank transfer',
            self::MobileWallet => 'Mobile wallet',
            self::Other => 'Other',
        };
    }

    /**
     * Whether this method maps to the bank account (vs. cash).
     */
    public function isCash(): bool
    {
        return $this === self::Cash;
    }
}
