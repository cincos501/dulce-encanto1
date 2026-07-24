<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Customer;
use App\Models\Order;
use App\Services\ChatwootService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create([
            'name' => 'Pasteles',
            'is_active' => true
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Torta de Vainilla',
            'is_active' => true
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Pequeña',
            'price' => 50.00,
            'sku' => 'PEQ-VAIN',
            'serves_people' => 5,
            'is_active' => true
        ]);

        $this->customer = Customer::create([
            'full_name' => 'Juan Pérez',
            'phone' => '59170012345',
            'chatwoot_conversation_id' => 12345
        ]);

        $this->order = Order::create([
            'customer_id' => $this->customer->id,
            'status' => 'En preparación',
            'total' => 50.00,
            'delivery_date' => now()->addDays(2)
        ]);

        // Create order item
        $this->order->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'price' => 50.00
        ]);
    }

    public function test_change_status_from_in_preparation_to_ready_triggers_chatwoot_message(): void
    {
        // 1. Mock ChatwootService to intercept sendMessage method
        $this->mock(ChatwootService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with(12345, \Mockery::on(function ($text) {
                    return str_contains($text, 'Juan Pérez') && 
                           str_contains($text, 'ya está listo') && 
                           str_contains($text, '#' . $this->order->id);
                }))
                ->andReturn(['id' => 999]);
        });

        // 2. Resolve OrderService and update status to "Listo"
        $orderService = $this->app->make(OrderService::class);
        $updatedOrder = $orderService->updateStatus($this->order->id, 'Listo');

        // 3. Assert status is updated
        $this->assertEquals('Listo', $updatedOrder->status);
    }

    public function test_change_status_to_non_ready_does_not_trigger_chatwoot_message(): void
    {
        // Reset order status to "Pendiente" to perform a clean update
        $this->order->status = 'Pendiente';
        $this->order->save();

        // 1. Mock ChatwootService to verify sendMessage is never called
        $this->mock(ChatwootService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('sendMessage');
        });

        // 2. Resolve OrderService and update status to "Confirmado"
        $orderService = $this->app->make(OrderService::class);
        $updatedOrder = $orderService->updateStatus($this->order->id, 'Confirmado');

        // 3. Assert status is updated
        $this->assertEquals('Confirmado', $updatedOrder->status);
    }

    public function test_order_without_conversation_id_updates_status_silently(): void
    {
        // Remove conversation ID from customer
        $this->customer->chatwoot_conversation_id = null;
        $this->customer->save();

        // 1. Mock ChatwootService to verify sendMessage is never called
        $this->mock(ChatwootService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('sendMessage');
        });

        // 2. Resolve OrderService and update status to "Listo"
        $orderService = $this->app->make(OrderService::class);
        $updatedOrder = $orderService->updateStatus($this->order->id, 'Listo');

        // 3. Assert status is updated successfully without exceptions
        $this->assertEquals('Listo', $updatedOrder->status);
    }

    public function test_chatwoot_api_error_does_not_block_order_update(): void
    {
        // 1. Mock ChatwootService to throw an exception
        $this->mock(ChatwootService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andThrow(new \Exception('Chatwoot connection timed out'));
        });

        // 2. Spy Log to verify the exception gets logged as an error
        Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::on(function ($message) {
                return str_contains($message, 'Failed to send status notification');
            }), \Mockery::any());
        
        Log::shouldReceive('warning')->byDefault();
        Log::shouldReceive('info')->byDefault();
        Log::shouldReceive('debug')->byDefault();

        // 3. Resolve OrderService and update status to "Listo"
        $orderService = $this->app->make(OrderService::class);
        $updatedOrder = $orderService->updateStatus($this->order->id, 'Listo');

        // 4. Assert status is updated successfully despite the API failure
        $this->assertEquals('Listo', $updatedOrder->status);
    }
}
