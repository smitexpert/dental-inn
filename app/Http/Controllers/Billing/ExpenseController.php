<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\StoreExpenseRequest;
use App\Http\Requests\Billing\UpdateExpenseRequest;
use App\Models\Expense;
use App\Repositories\Contracts\ExpenseCategoryRepository;
use App\Repositories\Contracts\ExpenseRepository;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseRepository $expenses,
        private readonly ExpenseCategoryRepository $categories,
        private readonly ExpenseService $service,
    ) {}

    public function index(): Response
    {
        $page = $this->expenses->paginate(
            request()->integer('per_page', 25),
            request()->only(['q', 'status', 'category_id', 'date_from', 'date_to']),
        );

        return Inertia::render('Billing/Expenses/Index', [
            'expenses' => $page,
            'filters' => request()->only(['q', 'status', 'category_id', 'date_from', 'date_to']),
            'statuses' => collect(ExpenseStatus::cases())->map(fn (ExpenseStatus $s): array => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ])->all(),
            'categories' => $this->categories->allActive()->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Billing/Expenses/Create', [
            'categories' => $this->categories->allActive()->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])->all(),
            'methods' => collect(PaymentMethod::cases())->map(fn (PaymentMethod $m): array => [
                'value' => $m->value,
                'label' => $m->label(),
            ])->all(),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = ExpenseStatus::Pending->value;
        $data['created_by'] = $request->user()->id;
        $data['currency_code'] = $data['currency_code'] ?? 'BDT';
        $data['exchange_rate'] = $data['exchange_rate'] ?? 1.0;

        $expense = $this->expenses->create($data);

        if ($request->hasFile('receipt')) {
            $expense->addMediaFromRequest('receipt')->toMediaCollection('receipt');
        }

        return redirect()->route('billing.expenses.show', $expense)->with('success', 'Expense submitted.');
    }

    public function show(Expense $expense): Response
    {
        $expense->load(['category', 'creator', 'approver']);

        return Inertia::render('Billing/Expenses/Show', [
            'expense' => $this->serialize($expense),
        ]);
    }

    public function edit(Expense $expense): RedirectResponse|Response
    {
        if ($expense->status !== ExpenseStatus::Pending) {
            return redirect()->route('billing.expenses.show', $expense)
                ->with('error', 'Only pending expenses can be edited.');
        }

        return Inertia::render('Billing/Expenses/Edit', [
            'expense' => $this->serialize($expense),
            'categories' => $this->categories->allActive()->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])->all(),
            'methods' => collect(PaymentMethod::cases())->map(fn (PaymentMethod $m): array => [
                'value' => $m->value,
                'label' => $m->label(),
            ])->all(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        if ($expense->status !== ExpenseStatus::Pending) {
            return redirect()->route('billing.expenses.show', $expense)
                ->with('error', 'Only pending expenses can be updated.');
        }

        $data = $request->validated();
        $this->expenses->update($expense, $data);

        if ($request->hasFile('receipt')) {
            $expense->clearMediaCollection('receipt');
            $expense->addMediaFromRequest('receipt')->toMediaCollection('receipt');
        }

        return redirect()->route('billing.expenses.show', $expense)->with('success', 'Expense updated.');
    }

    public function approve(Request $request, Expense $expense): RedirectResponse
    {
        try {
            $this->service->approve($expense, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['expense' => $e->getMessage()]);
        }

        return redirect()->route('billing.expenses.show', $expense)->with('success', 'Expense approved.');
    }

    public function reject(Request $request, Expense $expense): RedirectResponse
    {
        try {
            $this->service->reject($expense, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['expense' => $e->getMessage()]);
        }

        return redirect()->route('billing.expenses.show', $expense)->with('success', 'Expense rejected.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'expense_date' => $expense->expense_date->toDateString(),
            'category' => [
                'id' => $expense->category->id,
                'name' => $expense->category->name,
            ],
            'amount' => (string) $expense->amount,
            'currency_code' => $expense->currency_code,
            'exchange_rate' => (string) $expense->exchange_rate,
            'payment_method' => $expense->payment_method->value,
            'payment_method_label' => $expense->payment_method->label(),
            'vendor' => $expense->vendor,
            'description' => $expense->description,
            'receipt_url' => $expense->getFirstMediaUrl('receipt'),
            'status' => $expense->status->value,
            'status_label' => $expense->status->label(),
            'status_color' => $expense->status->color(),
            'creator' => $expense->creator?->name,
            'approver' => $expense->approver?->name,
            'approved_at' => $expense->approved_at?->toIso8601String(),
        ];
    }
}
