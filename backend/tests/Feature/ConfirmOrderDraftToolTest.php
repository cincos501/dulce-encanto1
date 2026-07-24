<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\AI\Tools\Orders\ConfirmOrderDraftTool;
use App\AI\Orders\OrderDraftManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Extra;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemExtra;
use App\Repositories\WhatsAppSessionRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class ConfirmOrderDraftToolTest extends TestCase
{
    use RefreshDatabase;

    protected OrderDraftManager $draftManager;
    protected string $phone = '59170012345';

    protected function setUp(): void
    {
        parent::setUp();

        // Bind an in-memory session repository to isolate test from Redis dependencies
        $this->app->singleton(WhatsAppSessionRepositoryInterface::class, function () {
            return new class implements WhatsAppSessionRepositoryInterface {
                protected array $store = [];

                public function get(string $phone): ?array
                {
                    return $this->store[$phone] ?? null;
                }

                public function set(string $phone, array $data, int $ttl = 3600): void
                {
                    $this->store[$phone] = $data;
                }

                public function delete(string $phone): void
                {
                    unset($this->store[$phone]);
                }
            };
        });

        $this->draftManager = $this->app->make(OrderDraftManager::class);
        $this->draftManager->clearDraft($this->phone);
    }

    public function test_confirm_order_draft_tool_creates_order_in_mysql_and_clears_redis(): void
    {
        // 1. Seed catalog data
        $category = Category::create([
            'name' => 'Pasteles',
            'is_active' => true
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Torta Tres Leches',
            'is_active' => true
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Mediana',
            'price' => 120.00,
            'sku' => 'MED-TL',
            'serves_people' => 10,
            'is_active' => true
        ]);

        $extra = Extra::create([
            'name' => 'Crema Extra',
            'is_active' => true
        ]);
        $variant->extras()->attach($extra->id, ['price' => 15.00]);

        // 2. Prepare order draft in memory
        $this->draftManager->addItem(
            phone: $this->phone,
            productId: $product->id,
            productName: $product->name,
            variantId: $variant->id,
            variantName: $variant->name,
            quantity: 2,
            unitPrice: 120.00
        );

        $this->draftManager->addExtra(
            phone: $this->phone,
            variantId: $variant->id,
            extraId: $extra->id,
            extraName: $extra->name,
            extraPrice: 15.00
        );

        // 3. Instantiate tool and confirm draft
        $tool = $this->app->make(ConfirmOrderDraftTool::class);
        
        $deliveryDate = Carbon::now()->addDays(2)->format('Y-m-d'); // 48 hours later (valid)
        $deliveryTime = '15:30';

        $response = $tool->execute([
            'customer_name' => 'Juan Pérez',
            'delivery_type' => 'Delivery',
            'address' => 'Av. San Martín #456',
            'delivery_date' => $deliveryDate,
            'delivery_time' => $deliveryTime,
            'observations' => 'Escribir Feliz Cumpleaños'
        ], ['phone' => $this->phone]);

        // 4. Assert order creation in MySQL
        $this->assertStringContainsString('registrado correctamente', $response);
        $this->assertStringContainsString('Número de pedido: #', $response);

        // Parse order ID from response
        preg_match('/#(\d+)/', $response, $matches);
        $orderId = (int) $matches[1];

        $order = Order::find($orderId);
        $this->assertNotNull($order);
        $this->assertEquals('Pendiente', $order->status);
        $this->assertEquals(270.00, (float) $order->total); // (120 + 15) * 2 = 270

        // Assert items
        $this->assertCount(1, $order->items);
        $orderItem = $order->items->first();
        $this->assertEquals($variant->id, $orderItem->product_variant_id);
        $this->assertEquals(2, $orderItem->quantity);
        $this->assertEquals(135.00, (float) $orderItem->price); // 120 + 15

        // Assert extras
        $this->assertCount(1, $orderItem->extras);
        $orderItemExtra = $orderItem->extras->first();
        $this->assertEquals($extra->id, $orderItemExtra->extra_id);
        $this->assertEquals(15.00, (float) $orderItemExtra->price);

        // Assert Redis is cleared
        $this->assertFalse($this->draftManager->exists($this->phone));
    }

    public function test_confirm_order_draft_tool_enforces_24_hours_advance_notice_for_cakes(): void
    {
        // 1. Seed catalog data
        $category = Category::create([
            'name' => 'Pasteles',
            'is_active' => true
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Torta de Chocolate',
            'is_active' => true
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Pequeña',
            'price' => 80.00,
            'sku' => 'PEQ-CHOC',
            'serves_people' => 5,
            'is_active' => true
        ]);

        // 2. Prepare draft
        $this->draftManager->addItem(
            phone: $this->phone,
            productId: $product->id,
            productName: $product->name,
            variantId: $variant->id,
            variantName: $variant->name,
            quantity: 1,
            unitPrice: 80.00
        );

        $tool = $this->app->make(ConfirmOrderDraftTool::class);

        // Set date to today + 5 hours (less than 24 hours)
        $deliveryDate = Carbon::now()->format('Y-m-d');
        $deliveryTime = Carbon::now()->addHours(5)->format('H:i');

        $response = $tool->execute([
            'customer_name' => 'Juan Pérez',
            'delivery_type' => 'Retiro en tienda',
            'delivery_date' => $deliveryDate,
            'delivery_time' => $deliveryTime,
        ], ['phone' => $this->phone]);

        // 3. Assert rejection
        $this->assertStringContainsString('nuestras tortas requieren mínimo 24 horas de anticipación', $response);
        
        // Assert order was NOT created and Redis still exists
        $this->assertEquals(0, Order::count());
        $this->assertTrue($this->draftManager->exists($this->phone));
    }

    public function test_confirm_order_draft_tool_validates_required_delivery_address(): void
    {
        // 1. Seed catalog data
        $category = Category::create([
            'name' => 'Otros',
            'is_active' => true
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Galletas de Avena',
            'is_active' => true
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Paquete',
            'price' => 15.00,
            'sku' => 'GAL-AV',
            'serves_people' => 1,
            'is_active' => true
        ]);

        // 2. Prepare draft
        $this->draftManager->addItem(
            phone: $this->phone,
            productId: $product->id,
            productName: $product->name,
            variantId: $variant->id,
            variantName: $variant->name,
            quantity: 1,
            unitPrice: 15.00
        );

        $tool = $this->app->make(ConfirmOrderDraftTool::class);

        $deliveryDate = Carbon::now()->addDays(2)->format('Y-m-d');
        $deliveryTime = '12:00';

        // Execute without address when delivery_type is Delivery
        $response = $tool->execute([
            'customer_name' => 'Juan Pérez',
            'delivery_type' => 'Delivery',
            'delivery_date' => $deliveryDate,
            'delivery_time' => $deliveryTime,
        ], ['phone' => $this->phone]);

        // 3. Assert rejection
        $this->assertStringContainsString('La dirección es requerida', $response);
        $this->assertEquals(0, Order::count());
    }
}
