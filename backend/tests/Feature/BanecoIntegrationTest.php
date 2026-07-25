<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Baneco\Contracts\EncryptionServiceInterface;
use App\Baneco\Services\BanecoAuthenticationService;
use App\Baneco\Services\BanecoService;
use App\Baneco\DTO\GenerateQRRequest;
use App\Baneco\Jobs\ProcessPaymentNotificationJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BanecoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup configuration parameters for test
        config(['baneco.base_url' => 'http://localhost/ApiGateway']);
        config(['baneco.username' => 'test_user']);
        config(['baneco.password' => 'test_password']);
        config(['baneco.aes_key' => 'test_key_32_bytes_long_placeholder']);
        config(['baneco.account' => 'test_account']);
    }

    public function test_local_aes_encryption_and_decryption(): void
    {
        $service = $this->app->make(EncryptionServiceInterface::class);
        $testText = 'Test Message 123';

        $encrypted = $service->encrypt($testText);
        $this->assertNotEmpty($encrypted);
        $this->assertNotEquals($testText, $encrypted);

        $decrypted = $service->decrypt($encrypted);
        $this->assertEquals($testText, $decrypted);
    }

    public function test_auth_service_authenticates_and_caches_token(): void
    {
        // 1. Fake authentication API call
        Http::fake([
            '*/api/authentication/authenticate' => Http::response([
                'responseCode' => 0,
                'message' => 'Success',
                'token' => 'mocked_bearer_token_123'
            ], 200)
        ]);

        $authService = $this->app->make(BanecoAuthenticationService::class);
        
        // 2. Fetch token and assert it's returned
        $token = $authService->getAccessToken();
        $this->assertEquals('mocked_bearer_token_123', $token);

        // 3. Verify it is cached
        $this->assertTrue(Cache::has('baneco_bearer_token'));
        $this->assertEquals('mocked_bearer_token_123', Cache::get('baneco_bearer_token'));
    }

    public function test_baneco_service_generates_qr_successfully(): void
    {
        Http::fake([
            '*/api/authentication/authenticate' => Http::response([
                'responseCode' => 0,
                'message' => 'Success',
                'token' => 'mocked_bearer_token_123'
            ], 200),
            '*/api/qrsimple/generateQR' => Http::response([
                'responseCode' => 0,
                'message' => 'Success',
                'qrId' => 'qr_code_id_999',
                'qrImage' => 'base64_encoded_qr_image_data'
            ], 200)
        ]);

        $service = $this->app->make(BanecoService::class);

        $requestDto = GenerateQRRequest::fromArray([
            'transactionId' => '1001',
            'accountCredit' => '1234567890',
            'currency' => 'BOB',
            'amount' => 150.50,
            'description' => 'Pago de Torta',
            'dueDate' => '2026-07-26',
            'singleUse' => true,
            'modifyAmount' => false
        ]);

        $response = $service->generateQR($requestDto);

        $this->assertEquals(0, $response->responseCode);
        $this->assertEquals('qr_code_id_999', $response->qrId);
        $this->assertEquals('base64_encoded_qr_image_data', $response->qrImage);
    }

    public function test_payment_webhook_dispatches_job_with_idempotency_protection(): void
    {
        Queue::fake();
        Cache::flush();

        $payload = [
            'qrId' => 'qr_code_id_999',
            'transactionId' => '1001',
            'paymentDate' => '2026-07-25',
            'paymentTime' => '12:00:00',
            'currency' => 'BOB',
            'amount' => 150.50,
            'senderName' => 'Juan Perez'
        ];

        // 1. Post notification webhook first time
        $response1 = $this->postJson('/api/webhooks/baneco/payment', $payload);
        $response1->assertStatus(200);
        $response1->assertJsonFragment(['message' => 'Notification queued successfully']);

        Queue::assertPushed(ProcessPaymentNotificationJob::class, 1);

        // 2. Post same notification second time to verify idempotency ignores it
        $response2 = $this->postJson('/api/webhooks/baneco/payment', $payload);
        $response2->assertStatus(200);
        $response2->assertJsonFragment(['message' => 'Notification already processed']);

        // Verify it was NOT pushed again (remains 1 push)
        Queue::assertPushed(ProcessPaymentNotificationJob::class, 1);
    }
}
