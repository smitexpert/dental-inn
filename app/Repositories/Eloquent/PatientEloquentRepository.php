<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Patient;
use App\Repositories\Contracts\PatientRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PatientEloquentRepository implements PatientRepository
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $q = Patient::query();

        if (! empty($filters['q'])) {
            $q->search($filters['q']);
        }
        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $q->where('is_active', (bool) $filters['is_active']);
        }

        return $q->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function create(array $data): Patient
    {
        $patient = new Patient;
        $patient->fill(array_diff_key($data, array_flip(['patient_code'])));
        $patient->save();

        return $patient;
    }

    public function update(Patient $patient, array $data): Patient
    {
        $patient->fill(array_diff_key($data, array_flip(['patient_code'])));
        $patient->save();

        return $patient;
    }

    public function delete(Patient $patient): void
    {
        $patient->delete();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $term, int $limit = 10): array
    {
        $q = Patient::query()->orderBy('name')->limit(max(1, min(50, $limit)));

        if ($term !== '') {
            $q->search($term);
        }

        return $q->get()
            ->map(fn (Patient $p): array => [
                'id' => $p->id,
                'patient_code' => $p->patient_code,
                'name' => $p->name,
                'phone' => $p->phone,
            ])
            ->all();
    }

    /**
     * @return Collection<int, Patient>
     */
    public function findDuplicates(?string $phone, ?string $name, ?string $email, ?int $ignoreId = null): Collection
    {
        if (! $phone && (! $name || ! $email)) {
            return new Collection;
        }

        return Patient::query()
            ->duplicatesOf($phone, $name, $email, $ignoreId)
            ->orderBy('name')
            ->limit(10)
            ->get();
    }

    public function billingSummary(Patient $patient): array
    {
        /** @var object{total_invoiced?:string|null,total_paid?:string|null,balance_due?:string|null,open_invoices?:int|string|null,invoice_count?:int|string|null}|null $row */
        $row = DB::table('invoices')
            ->where('patient_id', $patient->id)
            ->selectRaw(
                'COALESCE(SUM(CAST(total AS DECIMAL(18,2))), 0) AS total_invoiced, '
                .'COALESCE(SUM(CAST(paid_amount AS DECIMAL(18,2))), 0) AS total_paid'
            )
            ->first();

        $openRow = DB::table('invoices')
            ->where('patient_id', $patient->id)
            ->whereRaw('CAST(total AS DECIMAL(18,2)) > CAST(paid_amount AS DECIMAL(18,2))')
            ->selectRaw(
                'COALESCE(SUM(CAST(total AS DECIMAL(18,2)) - CAST(paid_amount AS DECIMAL(18,2))), 0) AS balance_due, '
                .'COUNT(*) AS open_invoices, '
                .'(SELECT COUNT(*) FROM invoices WHERE patient_id = ?) AS invoice_count'
            )
            ->addBinding($patient->id, 'select')
            ->first();

        $totalInvoiced = (float) ($row->total_invoiced ?? 0);
        $totalPaid = (float) ($row->total_paid ?? 0);
        $balanceDue = (float) ($openRow->balance_due ?? 0);

        return [
            'total_invoiced' => number_format($totalInvoiced, 2, '.', ''),
            'total_paid' => number_format($totalPaid, 2, '.', ''),
            'balance_due' => number_format($balanceDue, 2, '.', ''),
            'open_invoices' => (int) ($openRow->open_invoices ?? 0),
            'invoice_count' => (int) ($openRow->invoice_count ?? 0),
        ];
    }
}
