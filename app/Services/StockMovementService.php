<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Centralised writer for stock movements and the StockLevel they derive.
 *
 * Every operation runs inside a DB transaction: the movement row is
 * inserted first (immutable source of truth), then the matching
 * StockLevel row is bumped in the same transaction. If anything fails
 * both writes roll back together.
 */
class StockMovementService
{
    /**
     * Record a single stock movement and bump the matching StockLevel.
     *
     * @param  array<string, mixed>  $attrs
     */
    public function record(array $attrs): StockMovement
    {
        $type = $attrs['type'] instanceof StockMovementType
            ? $attrs['type']
            : StockMovementType::from((string) $attrs['type']);

        $productId = (int) $attrs['product_id'];
        $locationId = isset($attrs['location_id']) && $attrs['location_id'] !== null
            ? (int) $attrs['location_id']
            : null;
        $performedBy = (int) $attrs['performed_by'];
        $quantity = (int) $attrs['quantity'];
        $reason = $attrs['reason'] ?? null;
        $unitCost = array_key_exists('unit_cost', $attrs) ? $attrs['unit_cost'] : null;
        $reference = $attrs['reference'] ?? null;
        $createdAt = $attrs['created_at'] ?? now();

        if ($quantity === 0) {
            throw new RuntimeException('Stock movement quantity must be non-zero.');
        }

        if ($type->affectsLocation() && $locationId === null) {
            throw new RuntimeException("Stock movement of type {$type->value} requires a location.");
        }

        return DB::transaction(function () use (
            $type,
            $productId,
            $locationId,
            $performedBy,
            $quantity,
            $reason,
            $unitCost,
            $reference,
            $createdAt,
        ): StockMovement {
            $movement = new StockMovement([
                'product_id' => $productId,
                'location_id' => $locationId,
                'type' => $type,
                'quantity' => $quantity,
                'reason' => $reason,
                'performed_by' => $performedBy,
                'unit_cost' => $unitCost,
                'created_at' => $createdAt instanceof Carbon ? $createdAt : Carbon::parse((string) $createdAt),
            ]);

            if ($reference instanceof Model) {
                $movement->reference_type = $reference->getMorphClass();
                $movement->reference_id = (int) $reference->getKey();
            }

            $movement->save();

            if ($type === StockMovementType::Adjustment) {
                $this->applyAdjustment($productId, $locationId, $quantity);
            } else {
                // Apply the movement's sign: +1 for in-flows (purchase,
                // transfer_in, return), -1 for out-flows (sale,
                // consumption, transfer_out). The caller passes an
                // unsigned quantity; the service derives the sign from
                // the type so the StockMovement row stores the signed
                // value as well.
                $signedQty = $type->sign() * abs($quantity);
                $movement->quantity = $signedQty;
                $movement->save();
                $this->bumpLevel($productId, $locationId, $signedQty);
            }

            return $movement;
        });
    }

    /**
     * Purchase stock from a supplier into a single location.
     */
    public function purchase(
        Product $product,
        StockLocation $location,
        int $quantity,
        User $user,
        ?string $unitCost = null,
        ?string $reason = null,
    ): StockMovement {
        return $this->record([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => StockMovementType::Purchase,
            'quantity' => $quantity,
            'reason' => $reason,
            'performed_by' => $user->id,
            'unit_cost' => $unitCost,
        ]);
    }

    /**
     * Consume stock from a location. Throws if not enough stock available.
     */
    public function consume(
        Product $product,
        StockLocation $location,
        int $quantity,
        User $user,
        ?string $reason = null,
        ?Model $reference = null,
    ): StockMovement {
        $current = (int) (StockLevel::query()
            ->where('product_id', $product->id)
            ->where('location_id', $location->id)
            ->value('quantity') ?? 0);

        if ($current < $quantity) {
            throw new RuntimeException(sprintf(
                'Cannot consume %d units of "%s" at "%s": only %d in stock.',
                $quantity,
                $product->name,
                $location->name,
                $current,
            ));
        }

        return $this->record([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => StockMovementType::Consumption,
            'quantity' => $quantity,
            'reason' => $reason,
            'performed_by' => $user->id,
            'reference' => $reference,
        ]);
    }

    /**
     * Transfer stock between two locations. Creates paired
     * transfer_out + transfer_in movements.
     *
     * @return array<int, StockMovement>
     */
    public function transfer(
        Product $product,
        StockLocation $from,
        StockLocation $to,
        int $quantity,
        User $user,
        ?string $notes = null,
    ): array {
        $current = (int) (StockLevel::query()
            ->where('product_id', $product->id)
            ->where('location_id', $from->id)
            ->value('quantity') ?? 0);

        if ($current < $quantity) {
            throw new RuntimeException(sprintf(
                'Cannot transfer %d units of "%s" from "%s": only %d in stock.',
                $quantity,
                $product->name,
                $from->name,
                $current,
            ));
        }

        $out = $this->record([
            'product_id' => $product->id,
            'location_id' => $from->id,
            'type' => StockMovementType::TransferOut,
            'quantity' => $quantity,
            'reason' => $notes,
            'performed_by' => $user->id,
        ]);

        $in = $this->record([
            'product_id' => $product->id,
            'location_id' => $to->id,
            'type' => StockMovementType::TransferIn,
            'quantity' => $quantity,
            'reason' => $notes,
            'performed_by' => $user->id,
        ]);

        return [$out, $in];
    }

    /**
     * Manually adjust stock. The $absoluteQuantity is the new absolute
     * value at $location; the signed delta is computed against the
     * current level. $location is required.
     */
    public function adjust(
        Product $product,
        StockLocation $location,
        int $absoluteQuantity,
        User $user,
        ?string $reason = null,
    ): StockMovement {
        $current = (int) (StockLevel::query()
            ->where('product_id', $product->id)
            ->where('location_id', $location->id)
            ->value('quantity') ?? 0);

        $delta = $absoluteQuantity - $current;

        return $this->record([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => StockMovementType::Adjustment,
            'quantity' => $delta,
            'reason' => $reason,
            'performed_by' => $user->id,
        ]);
    }

    /**
     * Atomically upsert a StockLevel and apply $delta to its quantity.
     * Throws if the new value would be negative.
     */
    protected function bumpLevel(int $productId, int $locationId, int $delta): void
    {
        $level = StockLevel::query()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->first();

        if ($level === null) {
            if ($delta < 0) {
                throw new RuntimeException('Cannot consume from a location with no recorded stock level.');
            }
            StockLevel::create([
                'product_id' => $productId,
                'location_id' => $locationId,
                'quantity' => $delta,
            ]);

            return;
        }

        $newQty = (int) $level->quantity + $delta;

        if ($newQty < 0) {
            throw new RuntimeException(sprintf(
                'Stock level would become negative (%d) for product %d at location %d.',
                $newQty,
                $productId,
                $locationId,
            ));
        }

        $level->quantity = $newQty;
        $level->save();
    }

    /**
     * Adjustment handling: positive quantity = add, negative = subtract.
     * Updates last_counted_at on the affected level.
     */
    protected function applyAdjustment(int $productId, ?int $locationId, int $signedDelta): void
    {
        if ($locationId === null) {
            // Without a location we have nothing concrete to adjust.
            // Callers should pass an explicit location.
            return;
        }

        $this->bumpLevel($productId, $locationId, $signedDelta);

        StockLevel::query()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->update(['last_counted_at' => now()]);
    }
}
