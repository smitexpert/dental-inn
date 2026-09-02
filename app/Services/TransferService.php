<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CashMovementType;
use App\Models\CashMovement;
use App\Models\Transfer;
use App\Repositories\Contracts\BankAccountRepository;
use App\Repositories\Contracts\CashMovementRepository;
use App\Repositories\Contracts\TransferRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class TransferService
{
    public function __construct(
        private readonly BankAccountRepository $accounts,
        private readonly TransferRepository $transfers,
        private readonly CashMovementRepository $movements,
    ) {}

    /**
     * @return array{0: Transfer, 1: CashMovement, 2: CashMovement}
     */
    public function execute(int $fromId, int $toId, string $amount, ?string $occurredAt, ?string $reference, ?string $note, int $userId): array
    {
        if ($fromId === $toId) {
            throw new RuntimeException('Source and destination accounts must be different.');
        }

        if ((float) $amount <= 0) {
            throw new RuntimeException('Transfer amount must be positive.');
        }

        return DB::transaction(function () use ($fromId, $toId, $amount, $occurredAt, $reference, $note, $userId): array {
            // Lock both rows in a deterministic (lexical id) order to avoid deadlocks.
            [$first, $second] = $fromId < $toId ? [$fromId, $toId] : [$toId, $fromId];
            $this->accounts->lockById($first);
            $this->accounts->lockById($second);

            $from = $this->accounts->lockById($fromId);
            $to = $this->accounts->lockById($toId);

            if (! $from->is_active || ! $to->is_active) {
                throw new RuntimeException('Both accounts must be active to transfer.');
            }

            if (! $from->is_system && (float) $from->balance < (float) $amount) {
                throw new RuntimeException("Insufficient balance on account {$from->name}.");
            }

            $groupId = (string) Str::uuid();
            $signed = number_format((float) $amount, 4, '.', '');

            $transfer = $this->transfers->create([
                'from_account_id' => $from->id,
                'to_account_id' => $to->id,
                'amount' => $signed,
                'occurred_at' => $occurredAt ? Carbon::parse($occurredAt) : now(),
                'reference' => $reference,
                'note' => $note,
                'transfer_group_id' => $groupId,
                'performed_by' => $userId,
            ]);

            $out = $this->movements->create([
                'account_id' => $from->id,
                'type' => CashMovementType::TransferOut,
                'amount' => '-'.$signed,
                'counter_account_id' => $to->id,
                'counter_amount' => $signed,
                'transfer_group_id' => $groupId,
                'reason' => 'Transfer to '.$to->name,
                'performed_by' => $userId,
            ]);

            $in = $this->movements->create([
                'account_id' => $to->id,
                'type' => CashMovementType::TransferIn,
                'amount' => $signed,
                'counter_account_id' => $from->id,
                'counter_amount' => $signed,
                'transfer_group_id' => $groupId,
                'reason' => 'Transfer from '.$from->name,
                'performed_by' => $userId,
            ]);

            $this->accounts->incrementBalance($from->id, '-'.$signed);
            $this->accounts->incrementBalance($to->id, $signed);

            return [$transfer, $out, $in];
        });
    }
}
