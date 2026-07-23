<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Supply;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SupplyRepository implements SupplyRepositoryInterface
{
    /**
     * Get all supplies.
     *
     * @return Collection<int, Supply>
     */
    public function all(bool $onlyActive = false): Collection
    {
        $query = Supply::with('suppliers');
        if ($onlyActive) {
            $query->where('is_active', true);
        }
        return $query->orderBy('name')->get();
    }

    /**
     * Get paginated and filtered supplies.
     */
    public function paginate(int $perPage = 10, ?string $search = null, bool $onlyActive = false): LengthAwarePaginator
    {
        $query = Supply::with('suppliers');

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        if ($search !== null && $search !== '') {
            $query->where(static function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('unit', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Find a supply by ID.
     */
    public function findById(int $id): ?Supply
    {
        return Supply::with('suppliers')->find($id);
    }

    /**
     * Create a new supply.
     */
    public function create(array $data): Supply
    {
        return Supply::create($data);
    }

    /**
     * Update an existing supply.
     */
    public function update(Supply $supply, array $data): Supply
    {
        $supply->update($data);

        return $supply;
    }

    /**
     * Delete a supply.
     */
    public function delete(Supply $supply): bool
    {
        return (bool) $supply->delete();
    }
}
