<?php

declare(strict_types=1);

namespace App\Baneco\Jobs;

use App\Baneco\DTO\GenerateQRRequest;
use App\Baneco\Services\BanecoService;
use App\Baneco\Events\QRGenerated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateQRJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $qrData
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BanecoService $banecoService): void
    {
        Log::channel('baneco')->info('GenerateQRJob: Processing QR generation request.', [
            'transactionId' => $this->qrData['transactionId'] ?? 'unknown'
        ]);

        $requestDto = GenerateQRRequest::fromArray($this->qrData);
        $response = $banecoService->generateQR($requestDto);

        if ($response->responseCode === 0 && !empty($response->qrId)) {
            Log::channel('baneco')->info('GenerateQRJob: QR code generated successfully.', [
                'qrId' => $response->qrId,
                'transactionId' => $requestDto->transactionId
            ]);

            event(new QRGenerated(
                qrId: $response->qrId,
                transactionId: $requestDto->transactionId,
                amount: $requestDto->amount,
                dueDate: $requestDto->dueDate,
                qrImage: $response->qrImage ?? ''
            ));
        } else {
            Log::channel('baneco')->error('GenerateQRJob: Failed to generate QR code.', [
                'code' => $response->responseCode,
                'message' => $response->message ?? 'No message returned'
            ]);
        }
    }
}
