<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Get paginated and filtered orders.
     */
    public function paginate(int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        $query = Order::with(['customer', 'items.productVariant.product']);

        if ($search !== null && $search !== '') {
            $query->where(static function ($q) use ($search): void {
                $q->where('status', 'like', "%{$search}%")
                    ->orWhereHas('customer', static function ($cQ) use ($search): void {
                        $cQ->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Find an order by ID.
     */
    public function findById(int $id): ?Order
    {
        return Order::with(['customer', 'items.productVariant.recipes.supply'])->find($id);
    }

    /**
     * Update an existing order.
     */
    public function update(Order $order, array $data): Order
    {
        $order->update($data);

        return $order;
    }

    /**
     * Get all customers.
     */
    public function getCustomers(): \Illuminate\Support\Collection
    {
        return \App\Models\Customer::orderBy('full_name')->get();
    }
}
