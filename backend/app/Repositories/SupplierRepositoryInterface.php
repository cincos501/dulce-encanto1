<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SupplierRepositoryInterface
{
    /**
     * Get all suppliers.
     *
     * @return Collection<int, Supplier>
     */
    public function all(bool $onlyActive = false): Collection;

    /**
     * Get paginated and filtered suppliers.
     */
    public function paginate(int $perPage = 10, ?string $search = null, bool $onlyActive = false): LengthAwarePaginator;

    /**
     * Find a supplier by ID.
     */
    public function findById(int $id): ?Supplier;

    /**
     * Create a new supplier.
     */
    public function create(array $data): Supplier;

    /**
     * Update an existing supplier.
     */
    public function update(Supplier $supplier, array $data): Supplier;

    /**
     * Delete a supplier.
     */
    public function delete(Supplier $supplier): bool;
}
