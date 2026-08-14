<?php

declare(strict_types=1);

namespace App\Http\Controllers\Exchange;

use App\Enums\CashMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exchange\StoreCashMovementRequest;
use App\Models\CashMovement;
use App\Repositories\Contracts\CashDrawerRepository;
use App\Repositories\Contracts\CashMovementRepository;
use App\Repositories\Contracts\CurrencyRepository;
use App\Services\CashDrawerService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class CashMovementController extends Controller
{
    public function __construct(
        private readonly CashMovementRepository $movements,
        private readonly CashDrawerRepository $drawers,
        private readonly CurrencyRepository $currencies,
        private readonly CashDrawerService $service,
    ) {}

    public function index(): Response
    {
        $page = $this->movements->paginate(
            request()->integer('per_page', 25),
            request()->only(['q', 'type', 'currency_code', 'date_from', 'date_to']),
        );

        return Inertia::render('Exchange/Movements/Index', [
            'movements' => $page,
            'filters' => request()->only(['q', 'type', 'currency_code', 'date_from', 'date_to']),
            'types' => collect(CashMovementType::cases())->map(fn (CashMovementType $t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ])->all(),
            'currencies' => $this->currencies->active()->map(fn ($c) => [
                'code' => $c->code,
                'name' => $c->name,
                'symbol' => $c->symbol,
            ])->values(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Exchange/Movements/Create', [
            'currencies' => $this->currencies->active()->map(fn ($c) => [
                'code' => $c->code,
                'name' => $c->name,
                'symbol' => $c->symbol,
                'decimals' => $c->decimals,
            ])->values(),
            'drawers' => $this->drawers->all()->map(fn ($d) => [
                'currency_code' => $d->currency_code,
                'balance' => (string) $d->balance,
            ])->values(),
        ]);
    }

    public function store(StoreCashMovementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = CashMovementType::from((string) $data['type']);

        try {
            if ($type === CashMovementType::CashIn) {
                $this->service->cashIn(
                    (string) $data['currency_code'],
                    (string) $data['amount'],
                    (string) $data['reason'],
                    null,
                    $request->user()->id,
                );
            } elseif ($type === CashMovementType::CashOut) {
                $this->service->cashOut(
                    (string) $data['currency_code'],
                    (string) $data['amount'],
                    (string) $data['reason'],
                    null,
                    $request->user()->id,
                );
            } elseif ($type === CashMovementType::Adjustment) {
                $this->service->adjust(
                    (string) $data['currency_code'],
                    (string) $data['amount'],
                    (string) $data['reason'],
                    $request->user()->id,
                );
            } elseif ($type === CashMovementType::ExchangeIn || $type === CashMovementType::ExchangeOut) {
                $this->service->exchange(
                    (string) $data['currency_code'],
                    (string) $data['to_currency_code'],
                    (string) $data['amount'],
                    $request->user()->id,
                );
            } else {
                throw new RuntimeException('Movement type not supported via this form yet.');
            }
        } catch (RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('exchange.movements.index')->with('success', 'Cash movement recorded.');
    }

    public function show(CashMovement $movement): Response
    {
        $movement->load(['currency', 'counterCurrency', 'performer', 'exchangeRate']);

        return Inertia::render('Exchange/Movements/Show', [
            'movement' => [
                'id' => $movement->id,
                'currency_code' => $movement->currency_code,
                'currency' => $movement->currency ? [
                    'name' => $movement->currency->name,
                    'symbol' => $movement->currency->symbol,
                ] : null,
                'type' => $movement->type->value,
                'type_label' => $movement->type->label(),
                'type_color' => $movement->type->color(),
                'amount' => (string) $movement->amount,
                'counter_currency_code' => $movement->counter_currency_code,
                'counter_amount' => $movement->counter_amount !== null ? (string) $movement->counter_amount : null,
                'exchange_group_id' => $movement->exchange_group_id,
                'reason' => $movement->reason,
                'performer' => $movement->performer ? $movement->performer->name : null,
                'created_at' => $movement->created_at?->toIso8601String(),
            ],
        ]);
    }
}
