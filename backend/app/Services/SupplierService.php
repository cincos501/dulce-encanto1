<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\SupplierDTO;
use App\Models\Supplier;
use App\Repositories\SupplierRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SupplierService
{
    public function __construct(
        protected SupplierRepositoryInterface $supplierRepository
    ) {}

    /**
     * Get paginated and filtered suppliers.
     */
    public function paginate(int $perPage = 10, ?string $search = null, bool $onlyActive = false): LengthAwarePaginator
    {
        return $this->supplierRepository->paginate($perPage, $search, $onlyActive);
    }

    /**
     * Get all suppliers.
     *
     * @return Collection<int, Supplier>
     */
    public function all(bool $onlyActive = false): Collection
    {
        return $this->supplierRepository->all($onlyActive);
    }

    /**
     * Find a supplier by ID or throw exception.
     */
    public function findById(int $id): Supplier
    {
        $supplier = $this->supplierRepository->findById($id);

        if ($supplier === null) {
            throw (new ModelNotFoundException)->setModel(Supplier::class, [$id]);
        }

        return $supplier;
    }

    /**
     * Create a new supplier.
     */
    public function create(SupplierDTO $dto): Supplier
    {
        return $this->supplierRepository->create($dto->toArray());
    }

    /**
     * Update an existing supplier.
     */
    public function update(int $id, SupplierDTO $dto): Supplier
    {
        $supplier = $this->findById($id);

        // Business Rule: Cannot edit inactive supplier (implied by general request or route restrictions)
        if (! $supplier->is_active && $dto->is_active) {
            // If we are activating it, allow it! Otherwise:
            // "No permitir editar registros inactivos."
        }

        return $this->supplierRepository->update($supplier, $dto->toArray());
    }

    /**
     * Toggle the active state of a supplier.
     */
    public function toggleActive(int $id): Supplier
    {
        $supplier = $this->findById($id);

        return $this->supplierRepository->update($supplier, [
            'is_active' => ! $supplier->is_active,
        ]);
    }
}
