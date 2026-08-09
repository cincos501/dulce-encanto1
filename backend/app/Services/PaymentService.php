<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Create a pending QR Baneco payment for an order.
     */
    public function createPendingPayment(Order $order, string $qrId): Payment
    {
        Log::channel('baneco')->info("PaymentService: Creating pending payment for Order #{$order->id} with QR ID: {$qrId}");

        return DB::transaction(function () use ($order, $qrId): Payment {
            // Cancel or update any other pending payments for this order if they exist
            Payment::where('order_id', $order->id)
                ->where('status', 'Pendiente')
                ->update(['status' => 'Fallido']);

            return Payment::create([
                'order_id' => $order->id,
                'amount' => $order->total,
                'payment_method' => 'QR Baneco',
                'transaction_code' => $qrId,
                'status' => 'Pendiente',
                'payment_date' => now(), // Placeholder required field
            ]);
        });
    }

    /**
     * Confirm a payment via transaction code.
     */
    public function confirmPayment(string $qrId, string $paymentDate, string $paymentTime, float $amount): ?Payment
    {
        Log::channel('baneco')->info("PaymentService: Confirming payment for QR ID: {$qrId}", [
            'date' => $paymentDate,
            'time' => $paymentTime,
            'amount' => $amount
        ]);

        return DB::transaction(function () use ($qrId, $paymentDate, $paymentTime, $amount): ?Payment {
            $payment = Payment::where('transaction_code', $qrId)->first();

            if (!$payment) {
                Log::channel('baneco')->warning("PaymentService: Payment record not found for QR ID: {$qrId}");
                return null;
            }

            try {
                $dateTimeStr = trim("{$paymentDate} {$paymentTime}");
                $parsedDate = Carbon::createFromFormat('Y-m-d H:i:s', $dateTimeStr);
            } catch (\Throwable $e) {
                Log::channel('baneco')->warning("PaymentService: Failed to parse date/time '{$paymentDate} {$paymentTime}'. Using current time.", [
                    'error' => $e->getMessage()
                ]);
                $parsedDate = now();
            }

            $payment->status = 'Completado';
            $payment->payment_date = $parsedDate;
            $payment->amount = $amount;
            $payment->save();

            Log::channel('baneco')->info("PaymentService: Payment #{$payment->id} successfully marked as Completado.");

            return $payment;
        });
    }
}
