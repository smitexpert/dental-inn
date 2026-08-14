<?php

declare(strict_types=1);

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exchange\StoreExchangeRateRequest;
use App\Http\Requests\Exchange\UpdateExchangeRateRequest;
use App\Models\ExchangeRate;
use App\Repositories\Contracts\CurrencyRepository;
use App\Repositories\Contracts\ExchangeRateRepository;
use App\Services\ExchangeRateService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExchangeRateController extends Controller
{
    public function __construct(
        private readonly ExchangeRateRepository $rates,
        private readonly CurrencyRepository $currencies,
        private readonly ExchangeRateService $service,
    ) {}

    public function index(): Response
    {
        $page = $this->rates->paginate(
            request()->integer('per_page', 25),
            request()->only(['from', 'to', 'q', 'effective_at']),
        );

        return Inertia::render('Exchange/Rates/Index', [
            'rates' => $page,
            'filters' => request()->only(['from', 'to', 'q', 'effective_at']),
            'currencies' => $this->currencies->active()->map(fn ($c) => [
                'code' => $c->code,
                'name' => $c->name,
                'symbol' => $c->symbol,
            ])->values(),
        ]);
    }

    public function store(StoreExchangeRateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->service->setRate(
            (string) $data['from_currency_code'],
            (string) $data['to_currency_code'],
            (string) $data['rate'],
            \Illuminate\Support\Carbon::parse((string) $data['effective_at']),
            $request->user()->id,
        );

        return redirect()->route('exchange.rates.index')->with('success', 'Exchange rate saved.');
    }

    public function update(UpdateExchangeRateRequest $request, ExchangeRate $rate): RedirectResponse
    {
        $data = $request->validated();
        $this->rates->update($rate, $data);

        return redirect()->route('exchange.rates.index')->with('success', 'Exchange rate updated.');
    }

    public function destroy(ExchangeRate $rate): RedirectResponse
    {
        abort_unless(request()->user()?->can('manage-exchange-rates'), 403);
        $this->rates->delete($rate);

        return redirect()->route('exchange.rates.index')->with('success', 'Exchange rate deleted.');
    }
}
