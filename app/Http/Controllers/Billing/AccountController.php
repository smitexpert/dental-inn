<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\StoreAccountRequest;
use App\Http\Requests\Billing\UpdateAccountRequest;
use App\Models\Account;
use App\Repositories\Contracts\AccountRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function __construct(private readonly AccountRepository $accounts) {}

    public function index(): Response
    {
        $page = $this->accounts->paginate(
            request()->integer('per_page', 25),
            request()->only(['q', 'type']),
        );

        return Inertia::render('Billing/Accounts/Index', [
            'accounts' => $page,
            'filters' => request()->only(['q', 'type']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Billing/Accounts/Create', [
            'parents' => $this->accounts->allActive(),
        ]);
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $account = $this->accounts->create($request->validated());

        return redirect()->route('billing.accounts.show', $account)->with('success', 'Account created.');
    }

    public function show(Account $account): Response
    {
        $account->load('parent', 'children');

        return Inertia::render('Billing/Accounts/Show', [
            'account' => $this->serialize($account),
        ]);
    }

    public function edit(Account $account): Response
    {
        return Inertia::render('Billing/Accounts/Edit', [
            'account' => $this->serialize($account),
            'parents' => $this->accounts->allActive()->reject(fn (Account $a): bool => $a->id === $account->id)->values(),
        ]);
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $this->accounts->update($account, $request->validated());

        return redirect()->route('billing.accounts.show', $account)->with('success', 'Account updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Account $account): array
    {
        return [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'type' => $account->type->value,
            'type_label' => $account->type->label(),
            'parent_id' => $account->parent_id,
            'parent_code' => $account->parent?->code,
            'is_active' => $account->is_active,
        ];
    }
}
