<?php

declare(strict_types=1);

namespace App\Baneco\Jobs;

use App\Baneco\DTO\PaymentQR;
use App\Baneco\Events\QRPaid;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessPaymentNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $paymentData
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $payment = PaymentQR::fromArray($this->paymentData);

        Log::channel('baneco')->info("ProcessPaymentNotificationJob: Processing webhook notification for QR: {$payment->qrId}", [
            'transactionId' => $payment->transactionId,
            'amount' => $payment->amount
        ]);

        $orderId = (int) $payment->transactionId;
        $order = Order::find($orderId);

        if (!$order) {
            Log::channel('baneco')->warning("ProcessPaymentNotificationJob: Order #{$orderId} not found for transaction: {$payment->transactionId}");
            return;
        }

        if ($order->status === 'Pendiente') {
            DB::transaction(function () use ($order) {
                // Update order to Confirmado (Pagado state)
                $order->status = 'Confirmado';
                $order->save();
            });

            Log::channel('baneco')->info("ProcessPaymentNotificationJob: Order #{$orderId} status successfully transitioned to Confirmado.");

            event(new QRPaid(
                qrId: $payment->qrId,
                transactionId: $payment->transactionId,
                paymentDate: $payment->paymentDate,
                paymentTime: $payment->paymentTime,
                amount: $payment->amount
            ));
        } else {
            Log::channel('baneco')->info("ProcessPaymentNotificationJob: Order #{$orderId} status is already '{$order->status}'. Skipping transition.");
        }
    }
}
