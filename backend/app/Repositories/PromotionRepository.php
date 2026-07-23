<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Promotion;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PromotionRepository implements PromotionRepositoryInterface
{
    /**
     * Get all promotions.
     *
     * @return Collection<int, Promotion>
     */
    public function all(bool $onlyActive = false): Collection
    {
        $query = Promotion::query();
        if ($onlyActive) {
            $query->where('is_active', true);
        }
        return $query->orderBy('name')->get();
    }

    /**
     * Get paginated and filtered promotions.
     */
    public function paginate(int $perPage = 10, ?string $search = null, bool $onlyActive = false): LengthAwarePaginator
    {
        $query = Promotion::query();

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        if ($search !== null && $search !== '') {
            $query->where(static function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Find a promotion by ID.
     */
    public function findById(int $id): ?Promotion
    {
        return Promotion::find($id);
    }

    /**
     * Create a new promotion.
     */
    public function create(array $data): Promotion
    {
        return Promotion::create($data);
    }

    /**
     * Update an existing promotion.
     */
    public function update(Promotion $promotion, array $data): Promotion
    {
        $promotion->update($data);

        return $promotion;
    }

    /**
     * Delete a promotion.
     */
    public function delete(Promotion $promotion): bool
    {
        return (bool) $promotion->delete();
    }
}
