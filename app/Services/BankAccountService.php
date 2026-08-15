<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BankAccountType;
use App\Enums\CashMovementType;
use App\Models\BankAccount;
use App\Models\CashMovement;
use App\Repositories\Contracts\BankAccountRepository;
use App\Repositories\Contracts\CashMovementRepository;
use App\Repositories\Contracts\TransferRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class BankAccountService
{
    public function __construct(
        private readonly BankAccountRepository $accounts,
        private readonly CashMovementRepository $movements,
        private readonly TransferRepository $transfers,
    ) {}

    public function create(array $data, int $userId): BankAccount
    {
        $type = $data['type'];

        if ($type === BankAccountType::Bank->value) {
            foreach (['bank_name', 'account_name', 'account_number'] as $req) {
                if (empty($data[$req])) {
                    throw new RuntimeException("Field {$req} is required for bank accounts.");
                }
            }
        } else {
            // cash / external — clear bank-only fields
            $data['bank_name'] = null;
            $data['account_name'] = null;
            $data['branch'] = null;
            $data['account_number'] = null;
        }

        $data['currency_code'] = $data['currency_code']
            ?? (string) settings('clinic.currency_code', 'BDT');
        $data['opening_balance'] = (string) ($data['opening_balance'] ?? 0);
        $data['balance'] = $data['opening_balance'];
        $data['is_system'] = false;
        $data['is_active'] = $data['is_active'] ?? true;
        $data['created_by'] = $userId;

        $opening = (float) $data['opening_balance'];
        $groupId = $opening > 0 ? (string) Str::uuid() : null;

        return DB::transaction(function () use ($data, $userId, $opening, $groupId): BankAccount {
            $account = $this->accounts->create($data);

            if ($opening > 0) {
                $external = $this->accounts->findSystemByName('External Adjustment');
                if (! $external) {
                    throw new RuntimeException('External Adjustment system account is missing. Run db:seed.');
                }

                $amount = number_format($opening, 4, '.', '');

                $this->transfers->create([
                    'from_account_id' => $external->id,
                    'to_account_id' => $account->id,
                    'amount' => $amount,
                    'occurred_at' => now(),
                    'reference' => 'Opening balance',
                    'note' => null,
                    'transfer_group_id' => $groupId,
                    'performed_by' => $userId,
                ]);

                $this->movements->create([
                    'account_id' => $external->id,
                    'type' => CashMovementType::TransferOut,
                    'amount' => '-'.$amount,
                    'counter_account_id' => $account->id,
                    'counter_amount' => $amount,
                    'transfer_group_id' => $groupId,
                    'reason' => 'Opening balance: '.$account->name,
                    'performed_by' => $userId,
                ]);

                $this->movements->create([
                    'account_id' => $account->id,
                    'type' => CashMovementType::TransferIn,
                    'amount' => $amount,
                    'counter_account_id' => $external->id,
                    'counter_amount' => $amount,
                    'transfer_group_id' => $groupId,
                    'reason' => 'Opening balance',
                    'performed_by' => $userId,
                ]);

                // account balance already set to opening_balance on create; external needs to drop
                $this->accounts->incrementBalance($external->id, '-'.$amount);
            }

            return $account;
        });
    }

    public function update(BankAccount $account, array $data): BankAccount
    {
        if ($account->is_system) {
            throw new RuntimeException('System accounts cannot be modified.');
        }

        if (isset($data['is_active']) && ! $data['is_active']) {
            if ($account->balance != 0) {
                throw new RuntimeException('Cannot deactivate an account with a non-zero balance.');
            }
        }

        return $this->accounts->update($account, $data);
    }

    /**
     * Record a non-transfer cash_movement against a bank account
     * (used by PaymentService for cash payments).
     */
    public function recordMovement(
        BankAccount $account,
        CashMovementType $type,
        string $amount,
        string $reason,
        ?Model $reference,
        int $userId,
    ): CashMovement {
        return DB::transaction(function () use ($account, $type, $amount, $reason, $reference, $userId): CashMovement {
            $locked = $this->accounts->lockById($account->id);

            if (! $locked->is_active) {
                throw new RuntimeException("Account {$account->name} is inactive.");
            }

            $signed = $type->isInflow() ? $amount : '-'.$amount;
            if ($type->isOutflow() && (float) $locked->balance < (float) $amount) {
                throw new RuntimeException("Insufficient balance on account {$account->name}.");
            }

            $data = [
                'account_id' => $account->id,
                'type' => $type,
                'amount' => $signed,
                'reason' => $reason,
                'performed_by' => $userId,
            ];

            if ($reference) {
                $data['reference_type'] = $reference->getMorphClass();
                $data['reference_id'] = (int) $reference->getKey();
            }

            $movement = $this->movements->create($data);

            $delta = $type->isInflow() ? (float) $amount : -(float) $amount;
            $this->accounts->incrementBalance($account->id, number_format($delta, 4, '.', ''));

            return $movement;
        });
    }

    public function findMainCash(): BankAccount
    {
        $cash = $this->accounts->findSystemByName('Main Cash');
        if (! $cash) {
            throw new RuntimeException('Main Cash system account is missing. Run db:seed.');
        }

        return $cash;
    }
}
