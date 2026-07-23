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
            // 1. Calculate required quantities of supplies
            $requiredSupplies = []; // [supply_id => ['required' => float, 'supply' => Supply, 'unit' => string]]

            foreach ($order->items as $item) {
                $variant = $item->productVariant;
                if ($variant === null) {
                    continue;
                }

                // If variant has no recipes, skip it
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

            // 2. Validate stock availability
            foreach ($requiredSupplies as $supplyId => $data) {
                $supply = $data['supply'];
                $required = $data['required'];
                $available = (float) $supply->stock;

                if ($available < $required) {
                    $supplyName = $supply->name;
                    $unit = $data['unit'];
                    
                    // Throw validation exception to return 422 HTTP response mapping to the status field
                    throw ValidationException::withMessages([
                        'status' => ["Stock insuficiente para el insumo {$supplyName}. Disponible: " . number_format($available, 4) . " {$unit}, Requerido: " . number_format($required, 4) . " {$unit}."]
                    ]);
                }
            }

            // 3. Perform deductions and update status in database transaction
            DB::transaction(function () use ($order, $newStatus, $requiredSupplies): void {
                foreach ($requiredSupplies as $supplyId => $data) {
                    $supply = $data['supply'];
                    $supply->stock -= $data['required'];
                    $supply->save();
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
            $customer = Customer::firstOrNew(['phone' => $dto->customerPhone]);
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

                $itemUnitPrice = $activePrice + $extrasPriceSum;
                $itemSubtotal = $itemUnitPrice * $itemDto->quantity;
                $orderTotal += $itemSubtotal;

                $itemsData[] = [
                    'variant' => $variant,
                    'quantity' => $itemDto->quantity,
                    'price' => $itemUnitPrice,
                    'extras' => $extrasModels
                ];
            }

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
