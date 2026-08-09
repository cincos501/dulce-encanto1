<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Baneco\Contracts\EncryptionServiceInterface;
use Illuminate\Support\Facades\Log;

class OrderHistoryService
{
    public function __construct(
        protected EncryptionServiceInterface $encryptionService
    ) {}

    /**
     * Generate a secure URL-safe token representing a phone number.
     */
    public function generatePhoneToken(string $phone): string
    {
        $encrypted = $this->encryptionService->encrypt($phone);
        
        // Base64 URL-safe conversion
        return str_replace(['+', '/', '='], ['-', '_', ''], $encrypted);
    }

    /**
     * Decrypt a URL-safe token to retrieve the original phone number.
     */
    public function decryptPhoneToken(string $token): ?string
    {
        try {
            // Restore Base64 formatting characters
            $base64 = str_replace(['-', '_'], ['+', '/'], $token);
            
            // Re-append padding characters if needed
            $paddingLength = 4 - (strlen($base64) % 4);
            if ($paddingLength < 4) {
                $base64 .= str_repeat('=', $paddingLength);
            }
            
            return $this->encryptionService->decrypt($base64);
        } catch (\Throwable $e) {
            Log::error("OrderHistoryService: Failed to decrypt phone token.", [
                'token' => $token,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get the customer order history using the encrypted phone token.
     */
    public function getCustomerHistory(string $phoneToken): array
    {
        $phone = $this->decryptPhoneToken($phoneToken);
        if (empty($phone)) {
            return [];
        }

        // Match using normalized phone format
        $normalizedPhone = \App\Support\PhoneHelper::normalize($phone);
        $customer = Customer::where('phone', $normalizedPhone)->first();

        if (!$customer) {
            Log::info("OrderHistoryService: No customer record found for phone: {$normalizedPhone}");
            return [];
        }

        $orders = Order::with(['items.productVariant.product', 'payments'])
            ->where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'customer' => [
                'name' => $customer->full_name,
                'phone' => $customer->phone,
            ],
            'orders' => $orders->map(function ($order) {
                $paidAmount = (float) $order->payments->where('status', 'Completado')->sum('amount');
                $balancePending = max(0.00, (float) $order->total - $paidAmount);
                
                // Get active QR token if there is a pending payment
                $pendingPayment = $order->payments->where('status', 'Pendiente')->first();

                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'production_stage' => $order->production_stage ?? 'Programado',
                    'total' => (float) $order->total,
                    'paid_amount' => $paidAmount,
                    'balance_pending' => $balancePending,
                    'qr_id' => $pendingPayment?->transaction_code,
                    'delivery_date' => $order->delivery_date?->toIso8601String(),
                    'created_at' => $order->created_at?->toIso8601String(),
                    'items' => $order->items->map(function ($item) {
                        return [
                          'quantity' => $item->quantity,
                          'price' => (float) $item->price,
                          'product_name' => $item->productVariant?->product?->name ?? 'Producto',
                          'variant_name' => $item->productVariant?->name ?? 'Presentación',
                        ];
                    })->all()
                ];
            })->all()
        ];
    }
}
