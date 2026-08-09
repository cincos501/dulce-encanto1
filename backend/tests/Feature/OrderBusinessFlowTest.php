<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Recipe;
use App\Models\Supplier;
use App\Models\Supply;
use App\Models\Payment;
use App\Models\User;
use App\Services\OrderService;
use App\Services\OrderHistoryService;
use App\Jobs\AutoTransitionProductionJob;
use App\Baneco\Contracts\EncryptionServiceInterface;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderBusinessFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Supplier $supplier;
    protected Supply $supply;
    protected ProductVariant $variant;
    protected EncryptionServiceInterface $encryptionService;
    protected OrderService $orderService;
    protected OrderHistoryService $historyService;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup configurations for testing
        config(['baneco.base_url' => 'http://localhost/ApiGateway']);
        config(['baneco.username' => 'test_user']);
        config(['baneco.password' => 'test_password']);
        config(['baneco.aes_key' => 'test_key_32_bytes_long_placeholder']);
        config(['baneco.account' => 'test_account']);

        // Seed roles & permissions
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->encryptionService = $this->app->make(EncryptionServiceInterface::class);
        $this->orderService = $this->app->make(OrderService::class);
        $this->historyService = $this->app->make(OrderHistoryService::class);

        // Setup inventory supplies
        $this->supplier = Supplier::create([
            'business_name' => 'Proveedor de Insumos',
            'phone' => '59170012345',
            'email' => 'proveedor@test.com',
            'is_active' => true,
        ]);

        $this->supply = Supply::create([
            'name' => 'Harina Especial',
            'unit' => 'kg',
            'stock' => 10.00,
            'minimum_stock' => 1.00,
            'average_cost' => 2.00,
            'is_active' => true,
        ]);
        $this->supply->suppliers()->attach($this->supplier->id, ['purchase_price' => 1.90]);

        // Setup product catalog
        $product = Product::create([
            'name' => 'Torta Selva Negra',
            'description' => 'Torta clásica de chocolate con cerezas',
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Completa Grande',
            'sku' => 'TSN-COMPLETA',
            'price' => 100.00,
            'is_active' => true,
        ]);

        // Setup recipe: 1.0 kg of Harina per torta
        Recipe::create([
            'product_variant_id' => $this->variant->id,
            'supply_id' => $this->supply->id,
            'quantity' => 1.0000,
            'unit' => 'kg',
        ]);
    }

    public function test_complete_order_checkout_payment_and_production_flow(): void
    {
        // 1. Fake Baneco API requests
        Http::fake([
            '*/api/authentication/authenticate' => Http::response([
                'responseCode' => 0,
                'message' => 'Success',
                'token' => 'mocked_bearer_token_999'
            ], 200),
            '*/api/qrsimple/generateQR' => Http::response([
                'responseCode' => 0,
                'message' => 'Success',
                'qrId' => 'qr_code_999',
                'qrImage' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
            ], 200)
        ]);

        // 2. Perform catalog web checkout
        // Selected delivery date is 24 hours in the future
        $deliveryDate = now()->addDays(2)->format('Y-m-d');
        $deliveryTime = '12:00';

        $checkoutPayload = [
            'customer_name' => 'Rodrigo Flores',
            'customer_phone' => '59177872032',
            'delivery_type' => 'Retiro en tienda',
            'delivery_date' => $deliveryDate,
            'delivery_time' => $deliveryTime,
            'observations' => 'Escribir: Feliz Cumpleaños Rodrigo',
            'items' => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity' => 2, // Requires 2.0 kg of Harina
                    'extras' => []
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/checkout', $checkoutPayload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'qr_id',
                    'total',
                    'delivery_date',
                ]
            ]);

        $orderId = $response->json('data.id');
        $qrId = $response->json('data.qr_id');

        $this->assertEquals('qr_code_999', $qrId);

        // Verify order is initially registered in 'Pendiente' status
        $order = Order::find($orderId);
        $this->assertNotNull($order);
        $this->assertEquals('Pendiente', $order->status);
        $this->assertEquals('Programado', $order->production_stage);

        // Verify payment record is created in status 'Pendiente'
        $payment = Payment::where('transaction_code', 'qr_code_999')->first();
        $this->assertNotNull($payment);
        $this->assertEquals('Pendiente', $payment->status);
        $this->assertEquals(200.00, (float) $payment->amount);

        // Verify supply stock is NOT yet deducted
        $this->assertEquals(10.00, (float) $this->supply->fresh()->stock);

        // 3. Confirm payment using Baneco webhook
        $webhookPayload = [
            'qrId' => 'qr_code_999',
            'transactionId' => (string) $orderId,
            'amount' => 200.00,
            'currency' => 'BOB',
            'paymentDate' => now()->format('Y-m-d'),
            'paymentTime' => now()->format('H:i:s'),
        ];

        $webhookResponse = $this->postJson('/api/webhooks/baneco/payment', $webhookPayload);

        $webhookResponse->assertStatus(200);

        // Assert payment record transitions to 'Completado' and order to 'Confirmado'
        $this->assertEquals('Completado', $payment->fresh()->status);
        $this->assertEquals('Confirmado', $order->fresh()->status);

        // Assert stock is STILL NOT deducted upon payment confirmation
        $this->assertEquals(10.00, (float) $this->supply->fresh()->stock);

        // 4. Test Customer Order History page lookup
        $phoneToken = $this->historyService->generatePhoneToken('59177872032');
        $historyResponse = $this->getJson("/api/orders/history/{$phoneToken}");

        $historyResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.customer.name', 'Rodrigo Flores')
            ->assertJsonStructure([
                'data' => [
                    'customer',
                    'orders' => [
                        [
                            'id',
                            'status',
                            'production_stage',
                            'total',
                            'paid_amount',
                            'balance_pending',
                            'items'
                        ]
                    ]
                ]
            ]);

        // 5. Test Automated Production Stage transitions
        // Artificially simulate time passing by shifting the confirmation date to 20 hours ago
        $confirmedPayment = $payment->fresh();
        $confirmedPayment->payment_date = now()->subHours(20);
        $confirmedPayment->save();

        $order->delivery_date = now()->addHours(4); // Only 4 hours left until delivery (Tc is 20 hours ago, total window 24h)
        $order->created_at = now()->subHours(20);
        $order->save();

        // Run the scheduler job manually
        $job = new AutoTransitionProductionJob();
        $job->handle($this->orderService);

        // Order should now be in 'En preparación' status and 'Decoración' production stage
        // because elapsed time (20 hours) is >= 0.8 of the window or based on fixed offsets
        $freshOrder = $order->fresh();
        $this->assertEquals('En preparación', $freshOrder->status);
        $this->assertNotEquals('Programado', $freshOrder->production_stage);

        // Assert recipes were successfully deducted and stock is updated
        // 2 items * 1.0 kg/item = 2.0 kg deducted. Stock should be 8.00
        $this->assertEquals(8.00, (float) $this->supply->fresh()->stock);

        // Simulating further time passing (delivery date in the past to trigger 'Listo' stage)
        $order->delivery_date = now()->subMinutes(1);
        $order->save();

        // Run the scheduler job again
        $job->handle($this->orderService);

        // Verify order transitions to Listo status and stage
        $this->assertEquals('Listo', $order->fresh()->status);
        $this->assertEquals('Listo', $order->fresh()->production_stage);
    }
}
