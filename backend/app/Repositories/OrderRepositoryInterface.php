<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    /**
     * Get paginated and filtered orders.
     */
    public function paginate(int $perPage = 10, ?string $search = null): LengthAwarePaginator;

    /**
     * Find an order by ID.
     */
    public function findById(int $id): ?Order;

    /**
     * Update an existing order.
     */
    public function update(Order $order, array $data): Order;

    /**
     * Get all customers.
     */
    public function getCustomers(): \Illuminate\Support\Collection;
}
