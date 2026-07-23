<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\PromotionDTO;
use App\Models\Promotion;
use App\Repositories\PromotionRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PromotionService
{
    public function __construct(
        protected PromotionRepositoryInterface $promotionRepository
    ) {}

    /**
     * Get paginated and filtered promotions.
     */
    public function paginate(int $perPage = 10, ?string $search = null, bool $onlyActive = false): LengthAwarePaginator
    {
        return $this->promotionRepository->paginate($perPage, $search, $onlyActive);
    }

    /**
     * Get all promotions.
     *
     * @return Collection<int, Promotion>
     */
    public function all(bool $onlyActive = false): Collection
    {
        return $this->promotionRepository->all($onlyActive);
    }

    /**
     * Find a promotion by ID or throw exception.
     */
    public function findById(int $id): Promotion
    {
        $promotion = $this->promotionRepository->findById($id);

        if ($promotion === null) {
            throw (new ModelNotFoundException)->setModel(Promotion::class, [$id]);
        }

        return $promotion;
    }

    /**
     * Create a new promotion.
     */
    public function create(PromotionDTO $dto, array $variantIds = []): Promotion
    {
        $this->validateBusinessRules($dto->discount_type, $dto->discount, $dto->start_date, $dto->end_date);

        $promotion = $this->promotionRepository->create($dto->toArray());
        $promotion->variants()->sync($variantIds);

        $this->validateOverlap($promotion);

        return $promotion;
    }

    /**
     * Update an existing promotion.
     */
    public function update(int $id, PromotionDTO $dto, array $variantIds = []): Promotion
    {
        $promotion = $this->findById($id);

        $this->validateBusinessRules($dto->discount_type, $dto->discount, $dto->start_date, $dto->end_date);

        $updated = $this->promotionRepository->update($promotion, $dto->toArray());
        $updated->variants()->sync($variantIds);

        // Validate overlap for linked variants
        $this->validateOverlap($updated);

        return $updated;
    }

    /**
     * Toggle the active state of a promotion.
     */
    public function toggleActive(int $id): Promotion
    {
        $promotion = $this->findById($id);
        $newState = ! $promotion->is_active;

        $updated = $this->promotionRepository->update($promotion, [
            'is_active' => $newState,
        ]);

        if ($newState) {
            $this->validateOverlap($updated);
        }

        return $updated;
    }

    /**
     * Delete a promotion checking business rules.
     *
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        $promotion = $this->findById($id);
        $now = Carbon::now();

        // Business Rule: Cannot delete a promotion that is currently active and in progress to prevent pricing inconsistencies in carts.
        if ($promotion->is_active && $now->between($promotion->start_date, $promotion->end_date)) {
            throw new \Exception('No se puede eliminar una promoción activa que se encuentra actualmente en curso. Desactívela primero.');
        }

        // Clean associations from product variant promotions pivot
        $promotion->variants()->detach();

        return $this->promotionRepository->delete($promotion);
    }

    /**
     * Validate business rules regarding dates and discount rates.
     */
    protected function validateBusinessRules(string $type, float $discount, string $start, string $end): void
    {
        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);

        if ($startDate->gt($endDate)) {
            throw new \InvalidArgumentException('La fecha de inicio no puede ser posterior a la fecha de fin.');
        }

        if ($discount < 0) {
            throw new \InvalidArgumentException('El valor del descuento no puede ser negativo.');
        }

        if ($type === 'percentage' && $discount > 100) {
            throw new \InvalidArgumentException('El porcentaje de descuento no puede ser superior al 100%.');
        }
    }

    /**
     * Ensure that for all associated products, there are no overlapping active promotions.
     */
    protected function validateOverlap(Promotion $promotion): void
    {
        if (! $promotion->is_active) {
            return;
        }

        $variantIds = $promotion->variants()->pluck('product_variants.id')->toArray();
        if (empty($variantIds)) {
            return;
        }

        foreach ($variantIds as $variantId) {
            $overlappingPromotionExists = Promotion::where('is_active', true)
                ->where('id', '!=', $promotion->id)
                ->whereHas('variants', static function ($query) use ($variantId): void {
                    $query->where('product_variants.id', $variantId);
                })
                ->where(static function ($query) use ($promotion): void {
                    $query->whereBetween('start_date', [$promotion->start_date, $promotion->end_date])
                        ->orWhereBetween('end_date', [$promotion->start_date, $promotion->end_date])
                        ->orWhere(static function ($q) use ($promotion): void {
                            $q->where('start_date', '<=', $promotion->start_date)
                                ->where('end_date', '>=', $promotion->end_date);
                        });
                })
                ->exists();

            if ($overlappingPromotionExists) {
                throw new \RuntimeException("La presentación de producto ID {$variantId} ya cuenta con otra promoción activa durante este mismo período de tiempo. Evite inconsistencias inactivando el solapamiento.");
            }
        }
    }
}
