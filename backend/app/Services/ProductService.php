<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ProductDTO;
use App\Models\Product;
use App\Repositories\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductService
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Get paginated and filtered products.
     */
    public function paginate(int $perPage = 10, ?string $search = null, bool $onlyActive = false): LengthAwarePaginator
    {
        return $this->productRepository->paginate($perPage, $search, $onlyActive);
    }

    /**
     * Get all products.
     *
     * @return Collection<int, Product>
     */
    public function all(bool $onlyActive = false): Collection
    {
        return $this->productRepository->all($onlyActive);
    }

    /**
     * Find a product by ID or throw exception.
     */
    public function findById(int $id): Product
    {
        $product = $this->productRepository->findById($id);

        if ($product === null) {
            throw (new ModelNotFoundException)->setModel(Product::class, [$id]);
        }

        return $product;
    }

    /**
     * Create a new product.
     */
    public function create(ProductDTO $dto): Product
    {
        $category = \App\Models\Category::find($dto->category_id);
        if ($category && !$category->is_active) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'category_id' => ['No se puede crear un producto dentro de una categoría inactiva.']
            ]);
        }

        return $this->productRepository->create($dto->toArray());
    }

    /**
     * Update an existing product.
     */
    public function update(int $id, ProductDTO $dto): Product
    {
        $product = $this->findById($id);

        if ($product->category && !$product->category->is_active) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'category_id' => ['No se puede editar un producto perteneciente a una categoría inactiva.']
            ]);
        }

        $targetCategory = \App\Models\Category::find($dto->category_id);
        if ($targetCategory && !$targetCategory->is_active) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'category_id' => ['No se puede mover un producto a una categoría inactiva.']
            ]);
        }

        return $this->productRepository->update($product, $dto->toArray());
    }

    /**
     * Toggle the active state of a product.
     */
    public function toggleActive(int $id): Product
    {
        $product = $this->findById($id);

        if ($product->category && !$product->category->is_active) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'is_active' => ['No se puede activar individualmente un producto perteneciente a una categoría inactiva.']
            ]);
        }

        return $this->productRepository->update($product, [
            'is_active' => ! $product->is_active,
        ]);
    }

    /**
     * Delete a product checking business rules.
     *
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        $product = $this->findById($id);

        // Business Rule: Cannot delete product if any of its variants are linked to registered orders
        $hasOrders = $product->variants()->whereHas('orderItems')->exists();

        // Wait, order items references product_variants directly, let's verify if we have orderItemExtras or orderItems.
        // Wait! Let's check relation name on ProductVariant.php!
        // Let's do grep_search or look in app/Models/ProductVariant.php to make sure we use the correct relation!
        return $this->deleteWithRelationCheck($product);
    }

    /**
     * Check if product variant relations exist and delete.
     *
     * @throws \Exception
     */
    protected function deleteWithRelationCheck(Product $product): bool
    {
        // Let's check if the product has variants linked to order items.
        // Product has many variants. Variants has many orderItems.
        $hasOrders = false;
        foreach ($product->variants as $variant) {
            // Check if variant is in any order item
            if ($variant->orderItems()->exists()) {
                $hasOrders = true;
                break;
            }
        }

        if ($hasOrders) {
            throw new \Exception('No se puede eliminar el producto porque tiene variantes asociadas a pedidos registrados en el sistema.');
        }

        // Clean up relations
        foreach ($product->variants as $variant) {
            // Delete recipes/formulas if they exist
            if (method_exists($variant, 'recipes')) {
                $variant->recipes()->delete();
            }
            // Detach extras pivot
            if (method_exists($variant, 'extras')) {
                $variant->extras()->detach();
            }
            // Detach promotions pivot
            if (method_exists($variant, 'promotions')) {
                $variant->promotions()->detach();
            }
            // Delete variant images
            if (method_exists($variant, 'images')) {
                $variant->images()->delete();
            }
            $variant->delete();
        }

        return $this->productRepository->delete($product);
    }
}
