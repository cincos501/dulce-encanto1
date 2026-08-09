<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\AI\Tools\Orders\ConfirmOrderDraftTool;
use App\AI\Memory\RedisConversationMemory;

class ProductVariantControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->assignRole('Administrador');

        $this->product = Product::create([
            'name' => 'Torta de Prueba',
            'description' => 'Descripción',
            'is_active' => true,
        ]);
    }

    /**
     * Test creating a READY_STOCK variant.
     */
    public function test_can_create_ready_stock_variant(): void
    {
        Sanctum::actingAs($this->adminUser);

        $payload = [
            'product_id' => $this->product->id,
            'name' => 'Grande READY',
            'price' => 150.50,
            'serves_people' => 12,
            'sale_type' => 'READY_STOCK',
            'stock' => 10,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/product-variants', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('product_variants', [
            'name' => 'Grande READY',
            'sale_type' => 'READY_STOCK',
            'stock' => 10,
        ]);
    }

    /**
     * Test creating a MADE_TO_ORDER variant forces stock to 0.
     */
    public function test_creating_made_to_order_variant_forces_stock_to_zero(): void
    {
        Sanctum::actingAs($this->adminUser);

        $payload = [
            'product_id' => $this->product->id,
            'name' => 'Grande MADE',
            'price' => 150.50,
            'serves_people' => 12,
            'sale_type' => 'MADE_TO_ORDER',
            'stock' => 10,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/product-variants', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('product_variants', [
            'name' => 'Grande MADE',
            'sale_type' => 'MADE_TO_ORDER',
            'stock' => 0,
        ]);
    }

    /**
     * Test updating variant from MADE_TO_ORDER to READY_STOCK.
     */
    public function test_can_update_variant_modality_to_ready_stock(): void
    {
        Sanctum::actingAs($this->adminUser);

        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'Mediana',
            'sku' => 'TEST-SKU-1',
            'price' => 80.00,
            'sale_type' => 'MADE_TO_ORDER',
            'stock' => 0,
            'is_active' => true,
        ]);

        $payload = [
            'product_id' => $this->product->id,
            'name' => 'Mediana',
            'price' => 85.00,
            'sale_type' => 'READY_STOCK',
            'stock' => 5,
            'is_active' => true,
        ];

        $response = $this->putJson("/api/v1/product-variants/{$variant->id}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'sale_type' => 'READY_STOCK',
            'stock' => 5,
        ]);
    }

    /**
     * Test updating variant from READY_STOCK to MADE_TO_ORDER forces stock to 0.
     */
    public function test_updating_variant_to_made_to_order_forces_stock_to_zero(): void
    {
        Sanctum::actingAs($this->adminUser);

        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'Mediana',
            'sku' => 'TEST-SKU-2',
            'price' => 80.00,
            'sale_type' => 'READY_STOCK',
            'stock' => 5,
            'is_active' => true,
        ]);

        $payload = [
            'product_id' => $this->product->id,
            'name' => 'Mediana',
            'price' => 80.00,
            'sale_type' => 'MADE_TO_ORDER',
            'stock' => 5,
            'is_active' => true,
        ];

        $response = $this->putJson("/api/v1/product-variants/{$variant->id}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'sale_type' => 'MADE_TO_ORDER',
            'stock' => 0,
        ]);
    }

    /**
     * Test 24-hours advance preparation notice rule applies only to MADE_TO_ORDER tortas.
     */
    public function test_24_hours_notice_applies_only_to_made_to_order_tortas(): void
    {
        $madeToOrderVariant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'Torta MADE',
            'sku' => 'TORTA-MADE',
            'price' => 120.00,
            'sale_type' => 'MADE_TO_ORDER',
            'stock' => 0,
            'is_active' => true,
        ]);

        $readyStockVariant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'Torta READY',
            'sku' => 'TORTA-READY',
            'price' => 120.00,
            'sale_type' => 'READY_STOCK',
            'stock' => 5,
            'is_active' => true,
        ]);

        $phone = '59170012345';
        $memory = $this->app->make(RedisConversationMemory::class);
        $session = $memory->loadSession($phone, 'Juan');

        // Create tool instance
        $tool = $this->app->make(ConfirmOrderDraftTool::class);

        // Case A: Cart contains MADE_TO_ORDER torta, and requested delivery date is < 24 hours.
        // It should return the warning message.
        $draftManager = $this->app->make(\App\AI\Memory\OrderDraftManager::class);
        $draftManager->clearDraft($phone);
        $draftManager->addItem($phone, $this->product->id, 'Torta de Prueba', $madeToOrderVariant->id, 'Torta MADE', 1, 120.00);

        $context = ['phone' => $phone, 'senderName' => 'Juan'];
        $deliveryDate = now()->addHours(5)->format('Y-m-d');
        $deliveryTime = now()->addHours(5)->format('H:i');

        $result = $tool->execute([
            'deliveryDate' => $deliveryDate,
            'deliveryTime' => $deliveryTime,
            'customerName' => 'Juan Perez',
            'deliveryType' => 'Retiro en tienda',
            'address' => 'Tienda',
        ], $context);

        $this->assertStringContainsString('los productos bajo pedido requieren mínimo 24 horas', $result);

        // Case B: Cart contains only READY_STOCK torta, and requested delivery date is < 24 hours.
        // It should proceed and not return the 24-hours warning message.
        $draftManager->clearDraft($phone);
        $draftManager->addItem($phone, $this->product->id, 'Torta de Prueba', $readyStockVariant->id, 'Torta READY', 1, 120.00);

        $result2 = $tool->execute([
            'deliveryDate' => $deliveryDate,
            'deliveryTime' => $deliveryTime,
            'customerName' => 'Juan Perez',
            'deliveryType' => 'Retiro en tienda',
            'address' => 'Tienda',
        ], $context);

        $this->assertStringNotContainsString('los productos bajo pedido requieren mínimo 24 horas', $result2);
    }

    /**
     * Test checkout throws validation exception if stock is insufficient.
     */
    public function test_checkout_throws_validation_exception_if_stock_insufficient(): void
    {
        $readyStockVariant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'Torta READY Limit',
            'sku' => 'T-READY-LIMIT',
            'price' => 120.00,
            'sale_type' => 'READY_STOCK',
            'stock' => 2,
            'is_active' => true,
        ]);

        $payload = [
            'customer_name' => 'Juan Perez',
            'customer_phone' => '59170012345',
            'delivery_type' => 'Retiro en tienda',
            'delivery_date' => now()->addDays(2)->format('Y-m-d'),
            'delivery_time' => '15:30',
            'items' => [
                [
                    'product_variant_id' => $readyStockVariant->id,
                    'quantity' => 5, // Exceeds available stock (2)
                    'extras' => []
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/checkout', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['stock']);
    }
}
