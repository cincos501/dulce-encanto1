<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SupplierRepository implements SupplierRepositoryInterface
{
    /**
     * Get all suppliers.
     *
     * @return Collection<int, Supplier>
     */
    public function all(bool $onlyActive = false): Collection
    {
        $query = Supplier::query();
        if ($onlyActive) {
            $query->where('is_active', true);
        }
        return $query->orderBy('business_name')->get();
    }

    /**
     * Get paginated and filtered suppliers.
     */
    public function paginate(int $perPage = 10, ?string $search = null, bool $onlyActive = false): LengthAwarePaginator
    {
        $query = Supplier::query();

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        if ($search !== null && $search !== '') {
            $query->where(static function ($q) use ($search): void {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('business_name')->paginate($perPage);
    }

    /**
     * Find a supplier by ID.
     */
    public function findById(int $id): ?Supplier
    {
        return Supplier::find($id);
    }

    /**
     * Create a new supplier.
     */
    public function create(array $data): Supplier
    {
        return Supplier::create($data);
    }

    /**
     * Update an existing supplier.
     */
    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier;
    }

    /**
     * Delete a supplier.
     */
    public function delete(Supplier $supplier): bool
    {
        return (bool) $supplier->delete();
    }
}
