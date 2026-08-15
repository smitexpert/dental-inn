<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;

interface PaymentRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?Payment;

    public function findByIdOrFail(int $id): Payment;

    public function create(array $data): Payment;
}
