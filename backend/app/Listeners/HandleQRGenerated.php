<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Baneco\Events\QRGenerated;
use App\Models\Order;
use App\Services\PaymentService;
use App\Services\ChatwootService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class HandleQRGenerated
{
    public function __construct(
        protected PaymentService $paymentService,
        protected ChatwootService $chatwootService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(QRGenerated $event): void
    {
        Log::channel('baneco')->info("HandleQRGenerated: Processing QR generation event for order #{$event->transactionId}");

        $order = Order::find((int) $event->transactionId);
        if (!$order) {
            Log::channel('baneco')->error("HandleQRGenerated: Order #{$event->transactionId} not found.");
            return;
        }

        // 1. Save the QR Base64 image to public disk
        if (!empty($event->qrImage)) {
            try {
                $rawImage = preg_replace('#^data:image/\w+;base64,#i', '', $event->qrImage);
                $decodedImage = base64_decode($rawImage, true);
                if ($decodedImage !== false) {
                    Storage::disk('public')->put("qrs/{$event->qrId}.png", $decodedImage);
                    Log::channel('baneco')->info("HandleQRGenerated: Saved QR image to public storage for QR ID: {$event->qrId}");
                } else {
                    Log::channel('baneco')->warning("HandleQRGenerated: Failed to decode Base64 image for QR ID: {$event->qrId}");
                }
            } catch (\Throwable $e) {
                Log::channel('baneco')->error("HandleQRGenerated: Exception writing QR image to disk: " . $e->getMessage());
            }
        }

        // 2. Create the pending payment record
        $this->paymentService->createPendingPayment($order, $event->qrId);
    }
}
