<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class InvoiceEloquentRepository implements InvoiceRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $q = Invoice::query()->with(['patient', 'creator']);

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $q->where(function (Builder $w) use ($term): void {
                $w->where('invoice_number', 'like', $term)
                    ->orWhereHas('patient', function (Builder $p) use ($term): void {
                        $p->where('name', 'like', $term)->orWhere('patient_code', 'like', $term);
                    });
            });
        }

        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        if (! empty($filters['patient_id'])) {
            $q->where('patient_id', (int) $filters['patient_id']);
        }

        if (! empty($filters['date_from'])) {
            $q->whereDate('invoice_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $q->whereDate('invoice_date', '<=', $filters['date_to']);
        }

        return $q->orderByDesc('invoice_date')->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function findById(int $id): ?Invoice
    {
        return Invoice::query()->find($id);
    }

    public function findByIdOrFail(int $id): Invoice
    {
        return Invoice::query()->findOrFail($id);
    }

    public function create(array $data): Invoice
    {
        $invoice = new Invoice;
        $invoice->fill($data);
        $invoice->save();

        return $invoice;
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->fill($data);
        $invoice->save();

        return $invoice;
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardStats(): array
    {
        return [
            'todays_appointments' => Invoice::query()
                ->whereDate('created_at', now()->toDateString())
                ->count(),
            'pending_payments' => Invoice::query()->open()->count(),
            'month_revenue' => (float) Invoice::query()
                ->whereIn('status', [InvoiceStatus::Paid->value, InvoiceStatus::PartiallyPaid->value])
                ->whereMonth('invoice_date', now()->month)
                ->whereYear('invoice_date', now()->year)
                ->sum('paid_amount'),
            'outstanding_balance' => (float) Invoice::query()->open()->sum('total') - (float) Invoice::query()->open()->sum('paid_amount'),
        ];
    }
}
