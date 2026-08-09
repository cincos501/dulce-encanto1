<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemExtra;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Extra;
use App\DTO\StoreOrderDTO;
use App\Repositories\OrderRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepository
    ) {}

    /**
     * Get paginated and filtered orders.
     */
    public function paginate(int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        return $this->orderRepository->paginate($perPage, $search);
    }

    /**
     * Find an order by ID or throw exception.
     */
    public function findById(int $id): Order
    {
        $order = $this->orderRepository->findById($id);

        if ($order === null) {
            throw (new ModelNotFoundException)->setModel(Order::class, [$id]);
        }

        return $order;
    }

    /**
     * Update order status, performing stock deduction if transitioning to "En preparación".
     */
    public function updateStatus(int $id, string $newStatus): Order
    {
        $order = $this->findById($id);
        $oldStatus = $order->status;

        // Validate state transition machine
        $validTransitions = [
            'Pendiente' => ['Confirmado', 'Cancelado'],
            'Confirmado' => ['En preparación', 'Cancelado'],
            'En preparación' => ['Listo', 'Cancelado'],
            'Listo' => ['Entregado'],
            'Entregado' => [],
            'Cancelado' => [],
        ];

        if ($oldStatus !== $newStatus) {
            if (!isset($validTransitions[$oldStatus]) || !in_array($newStatus, $validTransitions[$oldStatus], true)) {
                throw ValidationException::withMessages([
                    'status' => ["Transición de estado no válida de '{$oldStatus}' a '{$newStatus}'."]
                ]);
            }
        }

        // Only trigger deduction logic when transitioning TO 'En preparación' from another status
        if ($newStatus === 'En preparación' && $oldStatus !== 'En preparación') {
            $requiredSupplies = []; // [supply_id => ['required' => float, 'supply' => Supply, 'unit' => string]]
            $requiredVariantStocks = []; // [variant_id => ['required' => int, 'variant' => ProductVariant]]
            
            foreach ($order->items as $item) {
                $variant = $item->productVariant;
                if ($variant === null) {
                    continue;
                }

                if ($variant->sale_type === 'READY_STOCK') {
                    $variantId = $variant->id;
                    $qty = (int) $item->quantity;
                    if (isset($requiredVariantStocks[$variantId])) {
                        $requiredVariantStocks[$variantId]['required'] += $qty;
                    } else {
                        $requiredVariantStocks[$variantId] = [
                            'required' => $qty,
                            'variant' => $variant
                        ];
                    }
                } else {
                    // MADE_TO_ORDER
                    foreach ($variant->recipes as $recipeItem) {
                        $supply = $recipeItem->supply;
                        if ($supply === null) {
                            continue;
                        }

                        $supplyId = $supply->id;
                        $neededQty = (float) $recipeItem->quantity * (int) $item->quantity;

                        if (isset($requiredSupplies[$supplyId])) {
                            $requiredSupplies[$supplyId]['required'] += $neededQty;
                        } else {
                            $requiredSupplies[$supplyId] = [
                              'required' => $neededQty,
                              'supply' => $supply,
                              'unit' => $recipeItem->unit,
                            ];
                        }
                    }
                }
            }

            $validationErrors = [];

            // Validate variant stock availability for READY_STOCK
            foreach ($requiredVariantStocks as $variantId => $data) {
                $variant = $data['variant'];
                $required = $data['required'];
                $available = (int) $variant->stock;

                if ($available < $required) {
                    $variantName = $variant->name;
                    $productName = $variant->product?->name ?? 'Producto';
                    $validationErrors[] = "Stock de presentación insuficiente para '{$productName} ({$variantName})'. Disponible: {$available}, Requerido: {$required}.";
                }
            }

            // Validate supply stock availability for MADE_TO_ORDER
            foreach ($requiredSupplies as $supplyId => $data) {
                $supply = $data['supply'];
                $required = $data['required'];
                $available = (float) $supply->stock;

                if ($available < $required) {
                    $supplyName = $supply->name;
                    $unit = $data['unit'];
                    $validationErrors[] = "Stock insuficiente para el insumo {$supplyName}. Disponible: " . number_format($available, 4) . " {$unit}, Requerido: " . number_format($required, 4) . " {$unit}.";
                }
            }

            if (!empty($validationErrors)) {
                throw ValidationException::withMessages([
                    'status' => $validationErrors
                ]);
            }

            // Perform deductions and update status in database transaction
            DB::transaction(function () use ($order, $newStatus, $requiredSupplies, $requiredVariantStocks): void {
                // Deduct READY_STOCK variant stocks
                foreach ($requiredVariantStocks as $variantId => $data) {
                    $variant = $data['variant'];
                    $variant->stock -= $data['required'];
                    $variant->save();

                    // Check if stock becomes 0 to fire READY_STOCK out of stock event
                    if ($variant->stock <= 0) {
                        event(new \App\Events\ReadyStockOutOfStock($variant));
                    }
                }

                // Deduct MADE_TO_ORDER supplies
                foreach ($requiredSupplies as $supplyId => $data) {
                    $supply = $data['supply'];
                    $supply->stock -= $data['required'];
                    $supply->save();

                    // Check if supply stock drops below minimum stock to fire alert event
                    if ($supply->stock <= $supply->minimum_stock) {
                        event(new \App\Events\SupplyStockLow($supply));
                    }
                }

                $this->orderRepository->update($order, ['status' => $newStatus]);
            });
        } else {
            // Direct update for other status changes
            $this->orderRepository->update($order, ['status' => $newStatus]);
        }

        return $this->findById($order->id);
    }

    /**
     * Create an order from public checkout, performing customer registration and item details population.
     */
    public function createOrder(StoreOrderDTO $dto): Order
    {
        return DB::transaction(function () use ($dto): Order {
            // 1. Create or retrieve Customer based on phone
            $normalizedPhone = \App\Support\PhoneHelper::normalize($dto->customerPhone);
            $customer = Customer::firstOrNew(['phone' => $normalizedPhone]);
            $customer->full_name = $dto->customerName;
            
            // Serialize delivery details into email column
            $customer->email = json_encode([
                'delivery_type' => $dto->deliveryType,
                'address' => $dto->address,
                'observations' => $dto->observations,
            ]);
            $customer->save();

            // 2. Compute order items prices and totals
            $itemsData = [];
            $orderTotal = 0.00;
            $requiredSupplies = [];
            $requiredVariantStocks = [];

            foreach ($dto->items as $itemDto) {
                $variant = ProductVariant::find($itemDto->productVariantId);
                if ($variant === null) {
                    throw ValidationException::withMessages([
                        'items' => ["La presentación seleccionada no existe o se encuentra inactiva."]
                    ]);
                }

                if (!$variant->is_active || !$variant->product?->is_active || ($variant->product?->category && !$variant->product->category->is_active)) {
                    throw ValidationException::withMessages([
                        'items' => ["La presentación '{$variant->name}' de '" . ($variant->product?->name ?? '') . "' no está disponible porque el producto o su categoría se encuentra inactivo."]
                    ]);
                }

                $qty = (int) $itemDto->quantity;

                if ($variant->sale_type === 'READY_STOCK') {
                    $variantId = $variant->id;
                    if (isset($requiredVariantStocks[$variantId])) {
                        $requiredVariantStocks[$variantId]['required'] += $qty;
                    } else {
                        $requiredVariantStocks[$variantId] = [
                            'required' => $qty,
                            'variant' => $variant
                        ];
                    }
                } else {
                    // MADE_TO_ORDER
                    foreach ($variant->recipes as $recipeItem) {
                        $supply = $recipeItem->supply;
                        if ($supply === null) {
                            continue;
                        }

                        $supplyId = $supply->id;
                        $neededQty = (float) $recipeItem->quantity * $qty;

                        if (isset($requiredSupplies[$supplyId])) {
                            $requiredSupplies[$supplyId]['required'] += $neededQty;
                        } else {
                            $requiredSupplies[$supplyId] = [
                                'required' => $neededQty,
                                'supply' => $supply,
                                'unit' => $recipeItem->unit,
                            ];
                        }
                    }
                }

                // Calculate variant price, considering active promotions
                $activePrice = (float) $variant->price;
                $activePromo = $variant->promotions()
                    ->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->first();

                if ($activePromo !== null) {
                    if ($activePromo->discount_type === 'percentage') {
                        $activePrice = $activePrice * (1 - ((float) $activePromo->discount / 100));
                    } else {
                        $activePrice = max(0.00, $activePrice - (float) $activePromo->discount);
                    }
                }

                // Calculate extras total price
                $extrasPriceSum = 0.00;
                $extrasModels = [];
                foreach ($itemDto->extras as $extraId) {
                    $extra = $variant->extras()->where('extras.id', $extraId)->first();
                    if ($extra !== null) {
                        $extrasPriceSum += (float) $extra->pivot->price;
                        $extrasModels[] = $extra;
                    }
                }

                $itemUnitPrice = round($activePrice + $extrasPriceSum, 2);
                $itemSubtotal = round($itemUnitPrice * $itemDto->quantity, 2);
                $orderTotal += $itemSubtotal;

                $itemsData[] = [
                    'variant' => $variant,
                    'quantity' => $itemDto->quantity,
                    'price' => $itemUnitPrice,
                    'extras' => $extrasModels
                ];
            }

            // Perform stock validations
            $validationErrors = [];

            // Validate variant stock availability for READY_STOCK
            foreach ($requiredVariantStocks as $variantId => $data) {
                $v = $data['variant'];
                $required = $data['required'];
                $available = (int) $v->stock;

                if ($available < $required) {
                    $variantName = $v->name;
                    $productName = $v->product?->name ?? 'Producto';
                    $validationErrors[] = "Stock insuficiente para '{$productName} ({$variantName})'. Disponible: {$available}, Requerido: {$required}.";
                }
            }

            // Validate supply stock availability for MADE_TO_ORDER
            foreach ($requiredSupplies as $supplyId => $data) {
                $supply = $data['supply'];
                $required = $data['required'];
                $available = (float) $supply->stock;

                if ($available < $required) {
                    $supplyName = $supply->name;
                    $unit = $data['unit'];
                    $validationErrors[] = "Stock insuficiente de insumos para fabricar este pedido. Insumo '{$supplyName}' - Disponible: " . number_format($available, 2) . " {$unit}, Requerido: " . number_format($required, 2) . " {$unit}.";
                }
            }

            if (!empty($validationErrors)) {
                throw ValidationException::withMessages([
                    'stock' => $validationErrors
                ]);
            }

            $orderTotal = round($orderTotal, 2);

            // 3. Create the Order
            $order = Order::create([
                'customer_id' => $customer->id,
                'status' => 'Pendiente',
                'total' => $orderTotal,
                'delivery_date' => $dto->deliveryDate,
            ]);

            // 4. Create OrderItems & OrderItemExtras
            foreach ($itemsData as $data) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $data['variant']->id,
                    'quantity' => $data['quantity'],
                    'price' => $data['price'],
                ]);

                foreach ($data['extras'] as $extra) {
                    OrderItemExtra::create([
                        'order_item_id' => $orderItem->id,
                        'extra_id' => $extra->id,
                        'quantity' => 1,
                        'price' => $extra->pivot->price,
                    ]);
                }
            }

            return $order;
        });
    }

    /**
     * Get all customers.
     */
    public function getCustomers(): \Illuminate\Support\Collection
    {
        return $this->orderRepository->getCustomers();
    }
}
