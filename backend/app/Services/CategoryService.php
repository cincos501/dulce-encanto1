<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\CategoryDTO;
use App\Models\Category;
use App\Repositories\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CategoryService
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    ) {}

    /**
     * Get paginated and filtered categories.
     */
    public function paginate(int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        return $this->categoryRepository->paginate($perPage, $search);
    }

    /**
     * Get all categories.
     *
     * @return Collection<int, Category>
     */
    public function all(): Collection
    {
        return $this->categoryRepository->all();
    }

    /**
     * Find a category by ID or throw exception.
     */
    public function findById(int $id): Category
    {
        $category = $this->categoryRepository->findById($id);

        if ($category === null) {
            throw (new ModelNotFoundException)->setModel(Category::class, [$id]);
        }

        return $category;
    }

    /**
     * Create a new category.
     */
    public function create(CategoryDTO $dto): Category
    {
        return $this->categoryRepository->create($dto->toArray());
    }

    /**
     * Update an existing category.
     */
    public function update(int $id, CategoryDTO $dto): Category
    {
        $category = $this->findById($id);

        return $this->categoryRepository->update($category, $dto->toArray());
    }

    /**
     * Toggle the active state of a category.
     */
    public function toggleActive(int $id): Category
    {
        $category = $this->findById($id);

        return $this->categoryRepository->update($category, [
            'is_active' => ! $category->is_active,
        ]);
    }

    /**
     * Delete a category verifying business rules.
     *
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        $category = $this->findById($id);

        // Business Rule: A category cannot be deleted if it has products associated with it.
        if ($category->products()->exists()) {
            throw new \Exception('No se puede eliminar la categoría porque contiene productos asociados.');
        }

        return $this->categoryRepository->delete($category);
    }
}
