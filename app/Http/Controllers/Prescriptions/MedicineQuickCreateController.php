<?php

declare(strict_types=1);

namespace App\Http\Controllers\Prescriptions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Prescriptions\QuickStoreMedicineRequest;
use App\Models\Medicine;
use Illuminate\Http\JsonResponse;

class MedicineQuickCreateController extends Controller
{
    public function store(QuickStoreMedicineRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Treat (name + strength + form) as the unique key — if a doctor
        // re-submits the same medicine we surface the existing row instead
        // of raising a 409.
        $existing = Medicine::query()
            ->where('name', $data['name'])
            ->where(function ($w) use ($data): void {
                if ($data['strength'] ?? null) {
                    $w->where('strength', $data['strength']);
                } else {
                    $w->whereNull('strength');
                }
                if ($data['form'] ?? null) {
                    $w->where('form', $data['form']);
                } else {
                    $w->whereNull('form');
                }
            })
            ->first();

        $medicine = $existing ?? Medicine::query()->create([
            'name' => $data['name'],
            'strength' => $data['strength'] ?? null,
            'form' => $data['form'] ?? null,
            'name_bn' => $data['name_bn'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'id' => $medicine->id,
            'name' => $medicine->name,
            'strength' => $medicine->strength,
            'form' => $medicine->form,
            'name_bn' => $medicine->name_bn,
            'display' => $medicine->display_label,
        ], $existing ? 200 : 201);
    }
}
