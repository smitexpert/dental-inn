<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

interface InvoiceRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?Invoice;

    public function findByIdOrFail(int $id): Invoice;

    public function create(array $data): Invoice;

    public function update(Invoice $invoice, array $data): Invoice;
}
