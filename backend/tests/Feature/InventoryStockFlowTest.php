<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Recipe;
use App\Models\Supplier;
use App\Models\Supply;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryStockFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Supplier $supplier;
    protected Supply $supply1;
    protected Supply $supply2;
    protected ProductVariant $variant;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        // Create Admin User and authenticate
        $this->adminUser = User::factory()->create([
            'email' => 'admin@test.com',
            'is_active' => true,
        ]);
        $this->adminUser->assignRole('Administrador');

        // Create Supplier
        $this->supplier = Supplier::create([
            'business_name' => 'Distribuidor Test',
            'phone' => '+56900000000',
            'email' => 'supplier@test.com',
            'is_active' => true,
        ]);

        // Create Supplies
        $this->supply1 = Supply::create([
            'name' => 'Harina',
            'unit' => 'kg',
            'stock' => 10.00,
            'minimum_stock' => 2.00,
            'average_cost' => 1.50,
            'is_active' => true,
        ]);

        $this->supply2 = Supply::create([
            'name' => 'Azúcar',
            'unit' => 'kg',
            'stock' => 5.00,
            'minimum_stock' => 1.00,
            'average_cost' => 1.20,
            'is_active' => true,
        ]);

        // Link Supplies to Supplier
        $this->supply1->suppliers()->attach($this->supplier->id, ['purchase_price' => 1.40]);
        $this->supply2->suppliers()->attach($this->supplier->id, ['purchase_price' => 1.10]);

        // Create Product & Variant
        $product = Product::create([
            'name' => 'Torta de Prueba',
            'description' => 'Descripción',
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Normal',
            'sku' => 'TEST-NORMAL',
            'price' => 15.00,
            'is_active' => true,
        ]);

        // Create Recipe for Variant
        Recipe::create([
            'product_variant_id' => $this->variant->id,
            'supply_id' => $this->supply1->id,
            'quantity' => 1.5000,
            'unit' => 'kg',
        ]);

        Recipe::create([
            'product_variant_id' => $this->variant->id,
            'supply_id' => $this->supply2->id,
            'quantity' => 0.5000,
            'unit' => 'kg',
        ]);

        // Create Customer
        $this->customer = Customer::create([
            'full_name' => 'Cliente Test',
            'phone' => '+56999999999',
            'email' => 'client@test.com',
        ]);
    }

    /**
     * Test purchasing supplies increments stock and updates purchase_price pivot.
     */
    public function test_purchase_increments_stock_and_updates_pivot_price(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $payload = [
            'supplier_id' => $this->supplier->id,
            'items' => [
                [
                    'supply_id' => $this->supply1->id,
                    'quantity' => 5.0,
                    'purchase_price' => 1.45,
                ],
                [
                    'supply_id' => $this->supply2->id,
                    'quantity' => 10.0,
                    'purchase_price' => 1.05,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/supplies/purchase', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Compra de insumos registrada con éxito.',
            ]);

        // Verify stock is incremented
        $this->assertEquals(15.0, $this->supply1->fresh()->stock);
        $this->assertEquals(15.0, $this->supply2->fresh()->stock);

        // Verify pivot price is updated
        $this->assertEquals(1.45, $this->supply1->suppliers()->first()->pivot->purchase_price);
        $this->assertEquals(1.05, $this->supply2->suppliers()->first()->pivot->purchase_price);
    }

    public function test_transitioning_to_en_preparacion_deducts_stock_when_sufficient(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        // Create Order (needs 3.0kg Harina, 1.0kg Azúcar)
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'status' => 'Pendiente',
            'total' => 30.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 2,
            'price' => 15.00,
        ]);

        // 1. Transition Pendiente -> Confirmado (Valid)
        $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => 'Confirmado',
        ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'Confirmado');

        // 2. Transition Confirmado -> En preparación (Valid, deducts stock)
        $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => 'En preparación',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'En preparación');

        // Verify stock was deducted:
        // Harina: 10.0 - (1.5 * 2) = 7.0
        // Azúcar: 5.0 - (0.5 * 2) = 4.0
        $this->assertEquals(7.0, $this->supply1->fresh()->stock);
        $this->assertEquals(4.0, $this->supply2->fresh()->stock);
    }

    /**
     * Test order status update to 'En preparación' fails when there is insufficient stock of any ingredient.
     */
    public function test_transitioning_to_en_preparacion_fails_and_rolls_back_when_insufficient(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        // Create Order (needs 15.0kg Harina, 5.0kg Azúcar - Harina stock is 10.0, insufficient!)
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'status' => 'Pendiente',
            'total' => 150.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 10,
            'price' => 15.00,
        ]);

        // 1. Transition Pendiente -> Confirmado (Valid)
        $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => 'Confirmado',
        ]);
        $response->assertStatus(200);

        // 2. Transition Confirmado -> En preparación (Fails due to stock)
        $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => 'En preparación',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $errorMsg = $response->json('errors.status.0');
        $this->assertStringContainsString('Stock insuficiente para el insumo Harina', $errorMsg);

        // Verify no stock was deducted (transaction rolled back)
        $this->assertEquals(10.00, $this->supply1->fresh()->stock);
        $this->assertEquals(5.00, $this->supply2->fresh()->stock);

        // Verify order status remains 'Confirmado'
        $this->assertEquals('Confirmado', $order->fresh()->status);
    }

    /**
     * Test that invalid status transitions are rejected by the state machine.
     */
    public function test_invalid_status_transitions_are_rejected(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'status' => 'Pendiente',
            'total' => 30.00,
        ]);

        // Pendiente -> En preparación directly is invalid
        $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => 'En preparación',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $errorMsg = $response->json('errors.status.0');
        $this->assertStringContainsString('Transición de estado no válida', $errorMsg);
    }

    /**
     * Test that public checkout registers order correctly.
     */
    public function test_public_checkout_registers_order_correctly(): void
    {
        $payload = [
            'customer_name' => 'John Doe',
            'customer_phone' => '+56999999999',
            'delivery_type' => 'Delivery',
            'address' => 'Av. Providencia 1234',
            'observations' => 'Ring bell twice',
            'delivery_date' => now()->addDays(2)->format('Y-m-d'),
            'delivery_time' => '15:30',
            'items' => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity' => 2,
                    'extras' => [],
                ]
            ]
        ];

        // Public checkout does NOT require authentication!
        $response = $this->postJson('/api/v1/checkout', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'status',
                    'total',
                    'delivery_date',
                    'customer' => [
                        'id',
                        'full_name',
                        'email',
                        'phone',
                    ],
                    'items'
                ]
            ]);

        // Verify order is created in database
        $this->assertDatabaseHas('orders', [
            'status' => 'Pendiente',
            'total' => 30.00, // 15.00 * 2
        ]);

        // Verify customer has delivery details JSON encoded in email column
        $customer = \App\Models\Customer::where('phone', '+56999999999')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('John Doe', $customer->full_name);
        
        $delivery = json_decode($customer->email, true);
        $this->assertEquals('Delivery', $delivery['delivery_type']);
        $this->assertEquals('Av. Providencia 1234', $delivery['address']);
        $this->assertEquals('Ring bell twice', $delivery['observations']);
    }
}
