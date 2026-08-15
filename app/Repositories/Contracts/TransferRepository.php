<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Transfer;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransferRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;

    public function findByIdOrFail(int $id): Transfer;

    public function create(array $data): Transfer;
}
