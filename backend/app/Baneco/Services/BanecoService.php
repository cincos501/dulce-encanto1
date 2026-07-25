<?php

declare(strict_types=1);

namespace App\Baneco\Services;

use App\Baneco\DTO\GenerateQRRequest;
use App\Baneco\DTO\GenerateQRResponse;
use App\Baneco\DTO\StatusQRResponse;
use App\Baneco\DTO\PaidQRResponse;
use App\Baneco\Support\BanecoHttpClient;
use App\Baneco\Contracts\EncryptionServiceInterface;
use Illuminate\Support\Facades\Log;

class BanecoService
{
    public function __construct(
        protected BanecoHttpClient $client,
        protected EncryptionServiceInterface $encryptionService
    ) {}

    /**
     * Request QR Simple generation from Baneco.
     */
    public function generateQR(GenerateQRRequest $request): GenerateQRResponse
    {
        $data = $request->toArray();

        // Account credit number must be encrypted using AES-256
        $data['accountCredit'] = $this->encryptionService->encrypt($data['accountCredit']);

        try {
            $response = $this->client->request('POST', '/api/qrsimple/generateQR', $data);
            return GenerateQRResponse::fromArray($response);
        } catch (\Throwable $e) {
            Log::channel('baneco')->error('BanecoService: generateQR failed.', ['error' => $e->getMessage()]);
            return new GenerateQRResponse(-1, $e->getMessage());
        }
    }

    /**
     * Cancel an active QR code.
     */
    public function cancelQR(string $qrId): array
    {
        try {
            // Document suggests DELETE /api/qrsimple/cancelQR but does not specify parameters.
            // We pass it inside body/query params dynamically.
            return $this->client->request('DELETE', '/api/qrsimple/cancelQR', ['qrId' => $qrId]);
        } catch (\Throwable $e) {
            Log::channel('baneco')->error('BanecoService: cancelQR failed.', ['error' => $e->getMessage()]);
            return ['responseCode' => -1, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check current status of a QR code.
     */
    public function statusQR(string $qrId): StatusQRResponse
    {
        try {
            $response = $this->client->request('GET', "/api/qrsimple/v2/statusQR/{$qrId}");
            return StatusQRResponse::fromArray($response);
        } catch (\Throwable $e) {
            Log::channel('baneco')->error('BanecoService: statusQR failed.', ['error' => $e->getMessage()]);
            return new StatusQRResponse(-1, $e->getMessage(), 9); // default to cancelled/error status
        }
    }

    /**
     * Get list of paid QRs for a specific date (yyyyMMdd).
     */
    public function paidQR(string $date): PaidQRResponse
    {
        try {
            $response = $this->client->request('GET', "/api/qrsimple/v2/paidQR/{$date}");
            return PaidQRResponse::fromArray($response);
        } catch (\Throwable $e) {
            Log::channel('baneco')->error('BanecoService: paidQR failed.', ['error' => $e->getMessage()]);
            return new PaidQRResponse(-1, $e->getMessage(), []);
        }
    }

    /**
     * Query account statements history.
     */
    public function accountHistory(string $accountCode, string $startDate, string $endDate): array
    {
        try {
            $encryptedAccount = $this->encryptionService->encrypt($accountCode);
            return $this->client->request('POST', '/api/accounts/history', [
                'accountCode' => $encryptedAccount,
                'startDate' => $startDate,
                'endDate' => $endDate
            ]);
        } catch (\Throwable $e) {
            Log::channel('baneco')->error('BanecoService: accountHistory failed.', ['error' => $e->getMessage()]);
            return ['responseCode' => -1, 'message' => $e->getMessage()];
        }
    }

    /**
     * Upload batch payments payroll plan.
     */
    public function uploadBatch(
        string $batchId,
        string $type,
        string $description,
        string $accountCode,
        string $batchCurrency,
        float $batchAmount,
        array $paymentList
    ): array {
        try {
            $encryptedAccount = $this->encryptionService->encrypt($accountCode);

            return $this->client->request('POST', '/api/batchPayment/upload', [
                'batchId' => $batchId,
                'type' => $type,
                'descripction' => $description, // Spelled exactly like that in official docs
                'accountCode' => $encryptedAccount,
                'batchCurrency' => $batchCurrency,
                'batchAmount' => $batchAmount,
                'paymentList' => $paymentList
            ]);
        } catch (\Throwable $e) {
            Log::channel('baneco')->error('BanecoService: uploadBatch failed.', ['error' => $e->getMessage()]);
            return ['responseCode' => -1, 'message' => $e->getMessage()];
        }
    }
}
