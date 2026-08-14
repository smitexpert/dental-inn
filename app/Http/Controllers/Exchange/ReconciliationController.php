<?php

declare(strict_types=1);

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exchange\ReconcileDrawerRequest;
use App\Repositories\Contracts\CashDrawerRepository;
use App\Repositories\Contracts\CurrencyRepository;
use App\Services\CashDrawerReconciliationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ReconciliationController extends Controller
{
    public function __construct(
        private readonly CurrencyRepository $currencies,
        private readonly CashDrawerRepository $drawers,
        private readonly CashDrawerReconciliationService $service,
    ) {}

    public function index(): Response
    {
        $drawers = $this->drawers->all()->map(function ($drawer) {
            $snapshot = $this->service->snapshot((string) $drawer->currency_code);

            return [
                'currency_code' => $drawer->currency_code,
                'currency' => $drawer->currency ? [
                    'name' => $drawer->currency->name,
                    'symbol' => $drawer->currency->symbol,
                ] : null,
                'balance' => $snapshot['balance'],
                'last_reconciled_at' => $snapshot['last_reconciled_at']?->toIso8601String(),
                'last_reconciled_balance' => $snapshot['last_reconciled_balance'],
                'recent_movements' => $snapshot['recent_movements'],
            ];
        })->values();

        return Inertia::render('Exchange/Reconcile', [
            'drawers' => $drawers,
            'currencies' => $this->currencies->active()->map(fn ($c) => [
                'code' => $c->code,
                'name' => $c->name,
                'symbol' => $c->symbol,
            ])->values(),
        ]);
    }

    public function run(ReconcileDrawerRequest $request): RedirectResponse
    {
        try {
            $this->service->run(
                (string) $request->validated('currency_code'),
                (string) $request->validated('counted_balance'),
                $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['counted_balance' => $e->getMessage()]);
        }

        return redirect()->route('exchange.reconcile.index')->with('success', 'Drawer reconciled.');
    }
}
