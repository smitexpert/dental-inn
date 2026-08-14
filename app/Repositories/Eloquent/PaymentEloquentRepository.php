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

        return $q->orderByDesc('payment_date')->orderByDesc('id')->paginate($perPage)->withQueryString();
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
}
