<?php

namespace Tests\Feature;

use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryStockModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_stock_table_and_relationship_exist(): void
    {
        $this->assertTrue(Schema::hasTable('inventory_stocks'));
        $this->assertTrue(Schema::hasColumn('inventory_stocks', 'product_variant_id'));
        $this->assertTrue(Schema::hasColumn('inventory_stocks', 'quantity_on_hand'));
        $this->assertTrue(Schema::hasColumn('inventory_stocks', 'quantity_reserved'));
        $this->assertTrue(Schema::hasColumn('inventory_stocks', 'low_stock_threshold'));
        $this->assertTrue(Schema::hasColumn('inventory_stocks', 'track_inventory'));
        $this->assertTrue(Schema::hasColumn('inventory_stocks', 'allow_backorder'));

        $variant = $this->variant();
        $stock = InventoryStock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 25,
            'quantity_reserved' => 4,
            'low_stock_threshold' => 10,
            'track_inventory' => true,
            'allow_backorder' => false,
        ]);

        $this->assertTrue($stock->variant->is($variant));
        $this->assertTrue($variant->stock->is($stock));
        $this->assertSame(21, $stock->available_quantity);
        $this->assertFalse($stock->is_low_stock);
    }

    public function test_inventory_stock_can_detect_low_stock(): void
    {
        $stock = InventoryStock::query()->create([
            'product_variant_id' => $this->variant()->id,
            'quantity_on_hand' => 12,
            'quantity_reserved' => 5,
            'low_stock_threshold' => 7,
        ]);

        $this->assertSame(7, $stock->available_quantity);
        $this->assertTrue($stock->is_low_stock);
    }

    public function test_inventory_stock_is_one_row_per_variant(): void
    {
        $variant = $this->variant();

        InventoryStock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 10,
        ]);

        $this->expectException(QueryException::class);

        InventoryStock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 20,
        ]);
    }

    private function variant(): ProductVariant
    {
        $product = Product::factory()->create();

        return ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Default',
            'sku' => 'SKU-'.fake()->unique()->numberBetween(1000, 9999),
            'price' => 100,
            'is_default' => true,
            'status' => 'active',
        ]);
    }
}
