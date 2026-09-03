<?php

declare(strict_types=1);

namespace App\Http\Controllers\Bank;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\StoreTransferRequest;
use App\Models\Transfer;
use App\Repositories\Contracts\BankAccountRepository;
use App\Repositories\Contracts\TransferRepository;
use App\Services\TransferService;
use App\Settings\ClinicSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class TransferController extends Controller
{
    public function __construct(
        private readonly TransferRepository $transfers,
        private readonly BankAccountRepository $accounts,
        private readonly TransferService $service,
    ) {}

    public function index(): Response
    {
        $kpis = [
            ['id' => 'today', 'label' => 'Transfers today',
                'value' => (string) Transfer::whereDate('occurred_at', today())->count(), 'format' => 'number'],
            ['id' => 'volume', 'label' => 'Total volume today',
                'value' => (string) Transfer::whereDate('occurred_at', today())->sum('amount'), 'format' => 'currency'],
            ['id' => 'largest', 'label' => 'Largest',
                'value' => (string) (Transfer::max('amount') ?? '0'), 'format' => 'currency'],
        ];

        return Inertia::render('Bank/Transfers/Index', [
            'transfers' => $this->transfers->paginate(
                request()->integer('per_page', 25),
                request()->only(['date_from', 'date_to', 'from_account_id', 'to_account_id', 'q']),
            ),
            'filters' => request()->only(['date_from', 'date_to', 'from_account_id', 'to_account_id', 'q']),
            'accounts' => $this->accounts->allActive()->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'type' => $a->type,
            ])->values(),
            'kpis' => $kpis,
        ]);
    }

    public function create(): Response
    {
        $currency = app(ClinicSettings::class)->currency_code ?? 'BDT';

        return Inertia::render('Bank/Transfers/Create', [
            'accounts' => $this->accounts->allActive()->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'type' => $a->type,
                'balance' => (string) $a->balance,
            ])->values(),
            'currency' => $currency,
        ]);
    }

    public function store(StoreTransferRequest $request): RedirectResponse
    {
        try {
            $this->service->execute(
                (int) $request->validated('from_account_id'),
                (int) $request->validated('to_account_id'),
                (string) $request->validated('amount'),
                $request->validated('occurred_at'),
                $request->validated('reference'),
                $request->validated('note'),
                $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()->route('bank.transfers.index')->with('success', 'Transfer recorded.');
    }

    public function show(Transfer $transfer): Response
    {
        $transfer = $this->transfers->findByIdOrFail($transfer->id);

        return Inertia::render('Bank/Transfers/Show', [
            'transfer' => $transfer,
            'movements' => $transfer->movements()->with('account')->get(),
        ]);
    }
}
