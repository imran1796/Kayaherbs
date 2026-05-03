<?php

namespace App\Modules\Inventory\Repositories;

use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as LaravelLengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class InventoryStockRepository
{
    public function createMissingForVariants(): void
    {
        ProductVariant::query()
            ->whereDoesntHave('stock')
            ->select('id')
            ->chunkById(100, function ($variants): void {
                foreach ($variants as $variant) {
                    InventoryStock::query()->firstOrCreate([
                        'product_variant_id' => $variant->id,
                    ]);
                }
            });
    }

    public function paginateWithRelations(int $perPage = 15): LengthAwarePaginator
    {
        return InventoryStock::query()
            ->with(['variant.product'])
            ->latest('id')
            ->paginate($perPage);
    }

    public function paginateTransactionsWithRelations(int $perPage = 15): LengthAwarePaginator
    {
        return InventoryTransaction::query()
            ->with(['variant.product', 'actor'])
            ->latest('id')
            ->paginate($perPage);
    }

    public function paginateLowStockWithRelations(int $perPage = 10): LengthAwarePaginator
    {
        $stocks = InventoryStock::query()
            ->with(['variant.product'])
            ->latest('id')
            ->get()
            ->filter(fn (InventoryStock $stock): bool => $stock->is_low_stock)
            ->values();

        return new LaravelLengthAwarePaginator(
            $stocks->take($perPage),
            $stocks->count(),
            $perPage,
            1,
            ['path' => Paginator::resolveCurrentPath()]
        );
    }

    public function lockVariantOrFail(int $variantId): ProductVariant
    {
        return ProductVariant::query()
            ->whereKey($variantId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function lockStockForVariant(int $variantId): ?InventoryStock
    {
        return InventoryStock::query()
            ->where('product_variant_id', $variantId)
            ->lockForUpdate()
            ->first();
    }

    public function createForVariant(int $variantId): InventoryStock
    {
        return InventoryStock::query()->create([
            'product_variant_id' => $variantId,
        ]);
    }

    public function update(InventoryStock $stock, array $data): InventoryStock
    {
        $stock->update($data);

        return $stock->refresh();
    }

    public function createTransaction(
        InventoryStock $stock,
        string $type,
        int $quantityDelta,
        ?User $actor,
        ?string $note,
        array $metadata
    ): void {
        $stock->transactions()->create([
            'product_variant_id' => $stock->product_variant_id,
            'actor_id' => $actor?->id,
            'type' => $type,
            'quantity_delta' => $quantityDelta,
            'quantity_on_hand_after' => $stock->quantity_on_hand,
            'quantity_reserved_after' => $stock->quantity_reserved,
            'note' => $note,
            'metadata' => $metadata ?: null,
        ]);
    }
}
