<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Supply;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SupplyRepositoryInterface
{
    /**
     * Get all supplies.
     *
     * @return Collection<int, Supply>
     */
    public function all(bool $onlyActive = false): Collection;

    /**
     * Get paginated and filtered supplies.
     */
    public function paginate(int $perPage = 10, ?string $search = null, bool $onlyActive = false): LengthAwarePaginator;

    /**
     * Find a supply by ID.
     */
    public function findById(int $id): ?Supply;

    /**
     * Create a new supply.
     */
    public function create(array $data): Supply;

    /**
     * Update an existing supply.
     */
    public function update(Supply $supply, array $data): Supply;

    /**
     * Delete a supply.
     */
    public function delete(Supply $supply): bool;
}
