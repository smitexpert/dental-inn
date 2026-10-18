<?php

declare(strict_types=1);

namespace App\Http\Controllers\Prescriptions;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicineAutocompleteController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:25'],
        ]);

        $term = trim((string) ($validated['q'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 8);

        $query = Medicine::query()->active()->orderBy('name');
        if ($term !== '') {
            $query->search($term);
        }

        $rows = $query->limit($limit)->get();

        return response()->json($rows->map(fn (Medicine $m): array => [
            'id' => $m->id,
            'name' => $m->name,
            'strength' => $m->strength,
            'form' => $m->form,
            'name_bn' => $m->name_bn,
            'display' => $m->display_label,
        ])->values());
    }
}
