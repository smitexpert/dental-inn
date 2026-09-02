<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;

interface PaymentRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $filters
     * @return array{count:int,total_collected:string,avg_payment:string,distinct_invoices:int}
     */
    public function aggregatesFiltered(array $filters = []): array;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, int>
     */
    public function distinctInvoiceIds(array $filters = []): array;

    public function findById(int $id): ?Payment;

    public function findByIdOrFail(int $id): Payment;

    public function create(array $data): Payment;
}
