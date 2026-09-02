<?php

declare(strict_types=1);

namespace App\Http\Controllers\Bank;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\StoreBankAccountRequest;
use App\Http\Requests\Bank\UpdateBankAccountRequest;
use App\Models\BankAccount;
use App\Repositories\Contracts\BankAccountRepository;
use App\Services\BankAccountService;
use App\Settings\ClinicSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class AccountController extends Controller
{
    public function __construct(
        private readonly BankAccountRepository $accounts,
        private readonly BankAccountService $service,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Bank/Accounts/Index', [
            'accounts' => $this->accounts->paginate(
                request()->integer('per_page', 25),
                request()->only(['q', 'type']),
            ),
            'filters' => request()->only(['q', 'type']),
        ]);
    }

    public function create(): Response
    {
        $currency = app(ClinicSettings::class)->currency_code ?? 'BDT';

        return Inertia::render('Bank/Accounts/Create', [
            'currency' => $currency,
        ]);
    }

    public function store(StoreBankAccountRequest $request): RedirectResponse
    {
        try {
            $this->service->create($request->validated(), $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['opening_balance' => $e->getMessage()])->withInput();
        }

        return redirect()->route('bank.accounts.index')->with('success', 'Account created.');
    }

    public function edit(BankAccount $account): Response
    {
        $currency = app(ClinicSettings::class)->currency_code ?? 'BDT';

        return Inertia::render('Bank/Accounts/Edit', [
            'account' => $account,
            'currency' => $currency,
        ]);
    }

    public function update(UpdateBankAccountRequest $request, BankAccount $account): RedirectResponse
    {
        try {
            $this->service->update($account, $request->validated());
        } catch (RuntimeException $e) {
            return back()->withErrors(['is_active' => $e->getMessage()])->withInput();
        }

        return redirect()->route('bank.accounts.index')->with('success', 'Account updated.');
    }
}
