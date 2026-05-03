<?php

namespace Tests\Feature;

use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryTransactionLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_transaction_table_and_relationships_exist(): void
    {
        $this->assertTrue(Schema::hasTable('inventory_transactions'));
        $this->assertTrue(Schema::hasColumn('inventory_transactions', 'inventory_stock_id'));
        $this->assertTrue(Schema::hasColumn('inventory_transactions', 'product_variant_id'));
        $this->assertTrue(Schema::hasColumn('inventory_transactions', 'actor_id'));
        $this->assertTrue(Schema::hasColumn('inventory_transactions', 'type'));
        $this->assertTrue(Schema::hasColumn('inventory_transactions', 'quantity_delta'));
        $this->assertTrue(Schema::hasColumn('inventory_transactions', 'quantity_on_hand_after'));
        $this->assertTrue(Schema::hasColumn('inventory_transactions', 'quantity_reserved_after'));
        $this->assertTrue(Schema::hasColumn('inventory_transactions', 'reference_type'));
        $this->assertTrue(Schema::hasColumn('inventory_transactions', 'reference_id'));
        $this->assertTrue(Schema::hasColumn('inventory_transactions', 'metadata'));

        [$variant, $stock] = $this->stock();
        $actor = User::factory()->create();

        $transaction = InventoryTransaction::query()->create([
            'inventory_stock_id' => $stock->id,
            'product_variant_id' => $variant->id,
            'actor_id' => $actor->id,
            'type' => 'adjustment',
            'quantity_delta' => 15,
            'quantity_on_hand_after' => 25,
            'quantity_reserved_after' => 2,
            'note' => 'Opening stock correction',
            'metadata' => ['reason' => 'initial_count'],
        ]);

        $this->assertTrue($transaction->stock->is($stock));
        $this->assertTrue($transaction->variant->is($variant));
        $this->assertTrue($transaction->actor->is($actor));
        $this->assertTrue($stock->transactions->first()->is($transaction));
        $this->assertTrue($variant->inventoryTransactions->first()->is($transaction));
        $this->assertSame(['reason' => 'initial_count'], $transaction->metadata);
    }

    public function test_inventory_transaction_can_store_negative_stock_movement(): void
    {
        [$variant, $stock] = $this->stock();

        $transaction = InventoryTransaction::query()->create([
            'inventory_stock_id' => $stock->id,
            'product_variant_id' => $variant->id,
            'type' => 'sale',
            'quantity_delta' => -3,
            'quantity_on_hand_after' => 7,
            'quantity_reserved_after' => 0,
        ]);

        $this->assertSame(-3, $transaction->quantity_delta);
        $this->assertSame(7, $transaction->quantity_on_hand_after);
    }

    /**
     * @return array{0: ProductVariant, 1: InventoryStock}
     */
    private function stock(): array
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Default',
            'sku' => 'SKU-'.fake()->unique()->numberBetween(1000, 9999),
            'price' => 100,
            'is_default' => true,
            'status' => 'active',
        ]);
        $stock = InventoryStock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 10,
            'quantity_reserved' => 2,
        ]);

        return [$variant, $stock];
    }
}
