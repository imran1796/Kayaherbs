<?php

namespace App\Modules\Inventory\Services;

use App\Models\InventoryStock;
use App\Models\User;
use App\Modules\Inventory\Repositories\InventoryStockRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryStockService
{
    public function __construct(
        private readonly InventoryStockRepository $stocks
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        $this->stocks->createMissingForVariants();

        return $this->stocks->paginateWithRelations($perPage);
    }

    public function paginateHistory(int $perPage = 15): LengthAwarePaginator
    {
        return $this->stocks->paginateTransactionsWithRelations($perPage);
    }

    public function paginateLowStock(int $perPage = 10): LengthAwarePaginator
    {
        $this->stocks->createMissingForVariants();

        return $this->stocks->paginateLowStockWithRelations($perPage);
    }

    public function adjust(int $variantId, int $quantityDelta, ?User $actor = null, ?string $note = null, array $metadata = []): InventoryStock
    {
        return DB::transaction(function () use ($variantId, $quantityDelta, $actor, $note, $metadata): InventoryStock {
            $stock = $this->lockedStockForVariant($variantId);
            $nextOnHand = $stock->quantity_on_hand + $quantityDelta;

            if ($nextOnHand < 0) {
                throw ValidationException::withMessages([
                    'quantity_delta' => ['Stock quantity cannot go below zero.'],
                ]);
            }

            if ($nextOnHand < $stock->quantity_reserved && ! $stock->allow_backorder) {
                throw ValidationException::withMessages([
                    'quantity_delta' => ['Stock quantity cannot be less than reserved stock.'],
                ]);
            }

            $stock = $this->stocks->update($stock, [
                'quantity_on_hand' => $nextOnHand,
            ]);

            $this->recordTransaction($stock, 'adjustment', $quantityDelta, $actor, $note, $metadata);

            return $stock;
        }, 3);
    }

    public function reserve(int $variantId, int $quantity, ?User $actor = null, ?string $note = null, array $metadata = []): InventoryStock
    {
        return DB::transaction(function () use ($variantId, $quantity, $actor, $note, $metadata): InventoryStock {
            $stock = $this->lockedStockForVariant($variantId);

            if ($stock->track_inventory && ! $stock->allow_backorder && $stock->available_quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Not enough stock is available to reserve.'],
                ]);
            }

            $stock = $this->stocks->update($stock, [
                'quantity_reserved' => $stock->quantity_reserved + $quantity,
            ]);

            $this->recordTransaction($stock, 'reservation', -$quantity, $actor, $note, $metadata);

            return $stock;
        }, 3);
    }

    public function release(int $variantId, int $quantity, ?User $actor = null, ?string $note = null, array $metadata = []): InventoryStock
    {
        return DB::transaction(function () use ($variantId, $quantity, $actor, $note, $metadata): InventoryStock {
            $stock = $this->lockedStockForVariant($variantId);

            if ($stock->quantity_reserved < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Release quantity cannot be greater than reserved stock.'],
                ]);
            }

            $stock = $this->stocks->update($stock, [
                'quantity_reserved' => $stock->quantity_reserved - $quantity,
            ]);

            $this->recordTransaction($stock, 'release', $quantity, $actor, $note, $metadata);

            return $stock;
        }, 3);
    }

    public function updateLowStockThreshold(int $variantId, ?int $threshold): InventoryStock
    {
        return DB::transaction(function () use ($variantId, $threshold): InventoryStock {
            $stock = $this->lockedStockForVariant($variantId);

            return $this->stocks->update($stock, [
                'low_stock_threshold' => $threshold,
            ]);
        }, 3);
    }

    private function lockedStockForVariant(int $variantId): InventoryStock
    {
        $this->stocks->lockVariantOrFail($variantId);

        $stock = $this->stocks->lockStockForVariant($variantId);

        if ($stock !== null) {
            return $stock;
        }

        return $this->stocks->createForVariant($variantId);
    }

    private function recordTransaction(
        InventoryStock $stock,
        string $type,
        int $quantityDelta,
        ?User $actor,
        ?string $note,
        array $metadata
    ): void {
        $this->stocks->createTransaction($stock, $type, $quantityDelta, $actor, $note, $metadata);
    }
}
