<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supply;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderService;
use App\Services\ProductionService;
use App\DTO\StoreProductionDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $repostero;
    protected ProductVariant $readyVariant;
    protected ProductVariant $madeToOrderVariant;
    protected Supply $sugar;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);

        // Create users
        $this->admin = User::create([
            'full_name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'phone' => '59170011111',
            'is_active' => true
        ]);
        $this->admin->assignRole('Administrador');

        $this->repostero = User::create([
            'full_name' => 'Repostero Test',
            'email' => 'repostero@test.com',
            'password' => bcrypt('password'),
            'phone' => '59170022222',
            'is_active' => true
        ]);
        $this->repostero->assignRole('Repostero');

        // Create Catalog structures
        $category = Category::create([
            'name' => 'Pastelería',
            'is_active' => true
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Empanada de Queso',
            'is_active' => true
        ]);

        // 1. Ready Stock variant
        $this->readyVariant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Unidad',
            'sku' => 'EMP-UNI',
            'price' => 3.50,
            'sale_type' => 'READY_STOCK',
            'stock' => 5,
            'is_active' => true
        ]);

        // 2. Made to Order variant
        $this->madeToOrderVariant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Caja x10',
            'sku' => 'EMP-CAJ',
            'price' => 30.00,
            'sale_type' => 'MADE_TO_ORDER',
            'stock' => 0,
            'is_active' => true
        ]);

        // 3. Supply
        $this->sugar = Supply::create([
            'name' => 'Azúcar',
            'unit' => 'kg',
            'stock' => 10.0,
            'minimum_stock' => 2.0,
            'is_active' => true
        ]);

        // Attach recipe (0.1kg sugar per item) to readyVariant
        $this->readyVariant->recipes()->create([
            'supply_id' => $this->sugar->id,
            'quantity' => 0.1,
            'unit' => 'kg'
        ]);

        // Attach recipe (1.0kg sugar per item) to madeToOrderVariant
        $this->madeToOrderVariant->recipes()->create([
            'supply_id' => $this->sugar->id,
            'quantity' => 1.0,
            'unit' => 'kg'
        ]);
    }

    public function test_repostero_can_record_manual_production_of_ready_stock_variant(): void
    {
        $dto = new StoreProductionDTO(
            productVariantId: $this->readyVariant->id,
            quantity: 20,
            notes: 'Lote de empanadas del jueves'
        );

        $service = app(ProductionService::class);
        $batch = $service->recordProduction($dto);

        $this->assertNotNull($batch);
        $this->assertEquals(20, $batch->quantity);

        // Variant stock should be increased: 5 + 20 = 25
        $this->readyVariant->refresh();
        $this->assertEquals(25, $this->readyVariant->stock);

        // Supplies should be deducted: 10.0 - (0.1 * 20) = 8.0
        $this->sugar->refresh();
        $this->assertEquals(8.0, (float) $this->sugar->stock);
    }

    public function test_cannot_record_production_for_made_to_order_variant(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $dto = new StoreProductionDTO(
            productVariantId: $this->madeToOrderVariant->id,
            quantity: 5
        );

        $service = app(ProductionService::class);
        $service->recordProduction($dto);
    }

    public function test_cannot_record_production_with_insufficient_supplies(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        // Requires 0.1 * 200 = 20kg sugar (only 10kg available)
        $dto = new StoreProductionDTO(
            productVariantId: $this->readyVariant->id,
            quantity: 200
        );

        $service = app(ProductionService::class);
        $service->recordProduction($dto);
    }

    public function test_order_preparation_deducts_differently_for_ready_stock_and_made_to_order(): void
    {
        $customer = \App\Models\Customer::create([
            'full_name' => 'Cliente Test',
            'phone' => '59170099999'
        ]);

        // Create Order with both items
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'Confirmado',
            'total' => 37.00
        ]);

        // 1. Ready Stock item (2 units)
        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $this->readyVariant->id,
            'quantity' => 2,
            'price' => 3.50
        ]);

        // 2. Made to Order item (1 unit)
        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $this->madeToOrderVariant->id,
            'quantity' => 1,
            'price' => 30.00
        ]);

        $service = app(OrderService::class);
        $service->updateStatus($order->id, 'En preparación');

        // Ready variant stock: 5 - 2 = 3
        $this->readyVariant->refresh();
        $this->assertEquals(3, $this->readyVariant->stock);

        // Sugar supply: 10.0 - (1.0 * 1) = 9.0 (ready_stock variant recipes should NOT be processed during order sale!)
        $this->sugar->refresh();
        $this->assertEquals(9.0, (float) $this->sugar->stock);
    }

    public function test_order_preparation_fails_if_ready_stock_insufficient(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $customer = \App\Models\Customer::create([
            'full_name' => 'Cliente Test',
            'phone' => '59170099999'
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'Confirmado',
            'total' => 35.00
        ]);

        // Ready Stock item (10 units, but only 5 available!)
        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $this->readyVariant->id,
            'quantity' => 10,
            'price' => 3.50
        ]);

        $service = app(OrderService::class);
        $service->updateStatus($order->id, 'En preparación');
    }
}
