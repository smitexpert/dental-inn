<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\AdjustStockRequest;
use App\Http\Requests\Inventory\ConsumeStockRequest;
use App\Http\Requests\Inventory\PurchaseStockRequest;
use App\Http\Requests\Inventory\TransferStockRequest;
use App\Models\Patient;
use App\Models\Product;
use App\Models\StockLocation;
use App\Services\StockMovementService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class StockOperationController extends Controller
{
    public function __construct(private readonly StockMovementService $movements) {}

    public function purchase(PurchaseStockRequest $request, Product $product): RedirectResponse
    {
        $location = StockLocation::findOrFail((int) $request->integer('location_id'));

        try {
            $this->movements->purchase(
                $product,
                $location,
                (int) $request->integer('quantity'),
                $request->user(),
                $request->input('unit_cost'),
                $request->input('reason'),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        return back()->with('success', sprintf('Recorded purchase of %d units.', (int) $request->integer('quantity')));
    }

    public function consume(ConsumeStockRequest $request, Product $product): RedirectResponse
    {
        $location = StockLocation::findOrFail((int) $request->integer('location_id'));
        $patient = $request->filled('patient_id') ? Patient::find((int) $request->integer('patient_id')) : null;

        try {
            $this->movements->consume(
                $product,
                $location,
                (int) $request->integer('quantity'),
                $request->user(),
                $request->input('reason'),
                $patient,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        return back()->with('success', sprintf('Recorded consumption of %d units.', (int) $request->integer('quantity')));
    }

    public function transfer(TransferStockRequest $request, Product $product): RedirectResponse
    {
        $from = StockLocation::findOrFail((int) $request->integer('from_location_id'));
        $to = StockLocation::findOrFail((int) $request->integer('to_location_id'));

        try {
            $this->movements->transfer(
                $product,
                $from,
                $to,
                (int) $request->integer('quantity'),
                $request->user(),
                $request->input('notes'),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        return back()->with('success', sprintf('Transferred %d units.', (int) $request->integer('quantity')));
    }

    public function adjust(AdjustStockRequest $request, Product $product): RedirectResponse
    {
        $location = StockLocation::findOrFail((int) $request->integer('location_id'));

        try {
            $this->movements->adjust(
                $product,
                $location,
                (int) $request->integer('quantity'),
                $request->user(),
                $request->input('reason'),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        return back()->with('success', 'Stock adjusted.');
    }
}
