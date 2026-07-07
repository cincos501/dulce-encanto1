<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Promotion;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PromotionRepositoryInterface
{
    /**
     * Get all promotions.
     *
     * @return Collection<int, Promotion>
     */
    public function all(): Collection;

    /**
     * Get paginated and filtered promotions.
     */
    public function paginate(int $perPage = 10, ?string $search = null): LengthAwarePaginator;

    /**
     * Find a promotion by ID.
     */
    public function findById(int $id): ?Promotion;

    /**
     * Create a new promotion.
     */
    public function create(array $data): Promotion;

    /**
     * Update an existing promotion.
     */
    public function update(Promotion $promotion, array $data): Promotion;

    /**
     * Delete a promotion.
     */
    public function delete(Promotion $promotion): bool;
}
