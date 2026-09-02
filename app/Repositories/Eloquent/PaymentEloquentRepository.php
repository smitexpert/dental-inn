<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Events\PaymentReceived;
use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Event;

class PaymentEloquentRepository implements PaymentRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $q = $this->baseQuery($filters);

        return $q->orderByDesc('payment_date')->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    /**
     * Compute payment-scoped aggregates (count, total, average, distinct
     * invoices) under the same filter set as paginate(). Includes zero-result
     * filter combinations so the controller can render the stat strip without
     * having to handle absent keys.
     *
     * @param  array<string, mixed>  $filters
     * @return array{count:int,total_collected:string,avg_payment:string,distinct_invoices:int}
     */
    public function aggregatesFiltered(array $filters = []): array
    {
        /** @var object{cnt?:int|string|null,inv_cnt?:int|string|null,sum_amount?:string|float|int|null,avg_amount?:string|float|int|null}|null $row */
        $row = $this->baseQuery($filters)
            ->selectRaw('COUNT(*) AS cnt, COUNT(DISTINCT invoice_id) AS inv_cnt, '
                .'COALESCE(SUM(amount), 0) AS sum_amount, COALESCE(AVG(amount), 0) AS avg_amount')
            ->first();

        $count = (int) ($row->cnt ?? 0);
        $sum = (float) ($row->sum_amount ?? 0);
        $avg = (float) ($row->avg_amount ?? 0);

        return [
            'count' => $count,
            'total_collected' => number_format($sum, 2, '.', ''),
            'avg_payment' => $count > 0 ? number_format($avg, 2, '.', '') : '0.00',
            'distinct_invoices' => (int) ($row->inv_cnt ?? 0),
        ];
    }

    /**
     * Distinct invoice IDs covered by the current filter set — used by the
     * controller to compute the outstanding-balance figure without dragging
     * the entire invoice table into the request.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, int>
     */
    public function distinctInvoiceIds(array $filters = []): array
    {
        return $this->baseQuery($filters)
            ->select('invoice_id')
            ->distinct()
            ->orderBy('invoice_id')
            ->pluck('invoice_id')
            ->map(fn ($v): int => (int) $v)
            ->all();
    }

    public function findById(int $id): ?Payment
    {
        return Payment::query()->find($id);
    }

    public function findByIdOrFail(int $id): Payment
    {
        return Payment::query()->findOrFail($id);
    }

    public function create(array $data): Payment
    {
        $payment = new Payment;
        $payment->fill($data);
        $payment->save();

        Event::dispatch(new PaymentReceived($payment));

        return $payment;
    }

    /**
     * Shared filter applicator so paginate() and aggregatesFiltered() see the
     * exact same row set. Modelled as a separate method rather than a free
     * helper so the repo encapsulates which columns the search hits.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Payment>
     */
    private function baseQuery(array $filters): Builder
    {
        $q = Payment::query()->with(['invoice.patient', 'receiver']);

        if (! empty($filters['invoice_id'])) {
            $q->where('invoice_id', (int) $filters['invoice_id']);
        }

        if (! empty($filters['method'])) {
            $q->where('method', $filters['method']);
        }

        if (! empty($filters['date_from'])) {
            $q->whereDate('payment_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $q->whereDate('payment_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $q->where(function (Builder $w) use ($term): void {
                $w->where('reference', 'like', $term)
                    ->orWhereHas('invoice', function (Builder $i) use ($term): void {
                        $i->where('invoice_number', 'like', $term);
                    });
            });
        }

        return $q;
    }
}
