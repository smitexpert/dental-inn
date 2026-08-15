<?php

declare(strict_types=1);

namespace App\Http\Controllers\Bank;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\ReconcileAccountRequest;
use App\Repositories\Contracts\BankAccountRepository;
use App\Services\ReconciliationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ReconciliationController extends Controller
{
    public function __construct(
        private readonly BankAccountRepository $accounts,
        private readonly ReconciliationService $service,
    ) {}

    public function index(): Response
    {
        $rows = $this->accounts->allActive()->map(function ($account) {
            $snap = $this->service->snapshot($account->id);

            return [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'balance' => $snap['balance'],
                'last_reconciled_at' => $account->last_reconciled_at?->toIso8601String(),
                'last_reconciled_balance' => $snap['last_reconciled_balance'],
            ];
        })->values();

        return Inertia::render('Bank/Reconcile', [
            'accounts' => $rows,
            'currency' => (string) settings('clinic.currency_code', 'BDT'),
        ]);
    }

    public function run(ReconcileAccountRequest $request): RedirectResponse
    {
        try {
            $this->service->run(
                (int) $request->validated('account_id'),
                (string) $request->validated('counted_balance'),
                $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['counted_balance' => $e->getMessage()]);
        }

        return redirect()->route('bank.reconcile.index')->with('success', 'Reconciled.');
    }
}
