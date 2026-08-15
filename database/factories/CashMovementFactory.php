<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CashMovementType;
use App\Models\BankAccount;
use App\Models\CashMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CashMovement>
 */
class CashMovementFactory extends Factory
{
    protected $model = CashMovement::class;

    public function definition(): array
    {
        return [
            'account_id' => BankAccount::factory(),
            'type' => CashMovementType::CashIn,
            'amount' => 100,
            'reason' => 'Factory row',
            'performed_by' => User::factory(),
            'created_at' => now(),
        ];
    }

    public function transfer(string $groupId, int $fromId, int $toId, string $amount, CashMovementType $type): self
    {
        $counter = $type === CashMovementType::TransferIn ? $fromId : $toId;

        return $this->state(fn () => [
            'type' => $type,
            'amount' => $type === CashMovementType::TransferIn ? $amount : '-'.$amount,
            'counter_account_id' => $counter,
            'counter_amount' => $amount,
            'transfer_group_id' => $groupId,
            'reason' => null,
        ]);
    }
}
