<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ordersData = [
            // Order 1
            [
                'customer_phone' => '+56911112222', // María José Soto
                'status' => 'Pendiente',
                'delivery_date' => now()->addDays(2),
                'items' => [
                    ['product' => 'Torta Selva Negra', 'variant' => 'Torta completa', 'quantity' => 1, 'price' => 25.00],
                    ['product' => 'Cupcake de Vainilla', 'variant' => 'Unidad', 'quantity' => 6, 'price' => 2.00],
                ]
            ],
            // Order 2
            [
                'customer_phone' => '+56933334444', // Juan Carlos Perez
                'status' => 'Confirmado',
                'delivery_date' => now()->addDays(1),
                'items' => [
                    ['product' => 'Torta Tres Leches', 'variant' => 'Torta completa', 'quantity' => 1, 'price' => 28.00],
                ]
            ],
            // Order 3
            [
                'customer_phone' => '+56955556666', // Sofía Camila Castro
                'status' => 'Pendiente',
                'delivery_date' => now()->addDays(3),
                'items' => [
                    ['product' => 'Cheesecake de Frutilla', 'variant' => 'Entero', 'quantity' => 2, 'price' => 32.00],
                ]
            ],
            // Order 4
            [
                'customer_phone' => '+56911112222', // María José Soto
                'status' => 'Listo',
                'delivery_date' => now()->subDay(),
                'items' => [
                    ['product' => 'Galletas de Chispas', 'variant' => 'Unidad', 'quantity' => 12, 'price' => 1.50],
                ]
            ],
        ];

        foreach ($ordersData as $oData) {
            $customer = Customer::where('phone', $oData['customer_phone'])->first();
            if (! $customer) {
                continue;
            }

            // Calculate total
            $total = 0;
            foreach ($oData['items'] as $item) {
                $total += $item['price'] * $item['quantity'];
            }

            // Create order
            $order = Order::create([
                'customer_id' => $customer->id,
                'status' => $oData['status'],
                'total' => $total,
                'delivery_date' => $oData['delivery_date'],
            ]);

            // Add items
            foreach ($oData['items'] as $item) {
                $product = Product::where('name', $item['product'])->first();
                if (! $product) {
                    continue;
                }

                $variant = ProductVariant::where('product_id', $product->id)
                    ->where('name', $item['variant'])
                    ->first();

                if (! $variant) {
                    continue;
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }
        }
    }
}
