<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Extra;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ExtraRepositoryInterface
{
    /**
     * Get all extras.
     *
     * @return Collection<int, Extra>
     */
    public function all(): Collection;

    /**
     * Get paginated and filtered extras.
     */
    public function paginate(int $perPage = 10, ?string $search = null): LengthAwarePaginator;

    /**
     * Find an extra by ID.
     */
    public function findById(int $id): ?Extra;

    /**
     * Create a new extra.
     */
    public function create(array $data): Extra;

    /**
     * Update an existing extra.
     */
    public function update(Extra $extra, array $data): Extra;

    /**
     * Delete an extra.
     */
    public function delete(Extra $extra): bool;
}
