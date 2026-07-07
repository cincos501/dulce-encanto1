<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ExtraDTO;
use App\Models\Extra;
use App\Repositories\ExtraRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ExtraService
{
    public function __construct(
        protected ExtraRepositoryInterface $extraRepository
    ) {}

    /**
     * Get paginated and filtered extras.
     */
    public function paginate(int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        return $this->extraRepository->paginate($perPage, $search);
    }

    /**
     * Get all extras.
     *
     * @return Collection<int, Extra>
     */
    public function all(): Collection
    {
        return $this->extraRepository->all();
    }

    /**
     * Find an extra by ID or throw exception.
     */
    public function findById(int $id): Extra
    {
        $extra = $this->extraRepository->findById($id);

        if ($extra === null) {
            throw (new ModelNotFoundException)->setModel(Extra::class, [$id]);
        }

        return $extra;
    }

    /**
     * Create a new extra.
     */
    public function create(ExtraDTO $dto): Extra
    {
        return $this->extraRepository->create($dto->toArray());
    }

    /**
     * Update an existing extra.
     */
    public function update(int $id, ExtraDTO $dto): Extra
    {
        $extra = $this->findById($id);

        return $this->extraRepository->update($extra, $dto->toArray());
    }

    /**
     * Toggle the active state of an extra.
     */
    public function toggleActive(int $id): Extra
    {
        $extra = $this->findById($id);

        return $this->extraRepository->update($extra, [
            'is_active' => ! $extra->is_active,
        ]);
    }

    /**
     * Delete an extra checking business rules.
     *
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        $extra = $this->findById($id);

        // Business Rule: An extra cannot be deleted if it has been used in registered orders (historical sales integrity).
        if ($extra->orderItemExtras()->exists()) {
            throw new \Exception('No se puede eliminar el extra porque ha sido utilizado en pedidos registrados.');
        }

        // Clean associations from product variants
        $extra->productVariants()->detach();

        return $this->extraRepository->delete($extra);
    }
}
