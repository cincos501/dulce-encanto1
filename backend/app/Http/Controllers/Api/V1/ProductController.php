<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTO\ProductDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    /**
     * Display a listing of products.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 10);
        $paginate = filter_var($request->query('paginate', true), FILTER_VALIDATE_BOOLEAN);

        if (! $paginate) {
            $products = $this->productService->all();

            return response()->json([
                'success' => true,
                'message' => 'Productos recuperados con éxito.',
                'data' => ProductResource::collection($products->load('category')),
            ]);
        }

        $products = $this->productService->paginate($perPage, $search);

        // Explicitly load category relation on paginated collection
        $products->getCollection()->load('category');

        return ProductResource::collection($products)->additional([
            'success' => true,
            'message' => 'Productos recuperados con éxito.',
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(ProductRequest $request): ProductResource
    {
        $dto = ProductDTO::fromArray($request->validated());
        $product = $this->productService->create($dto);

        return (new ProductResource($product->load('category')))->additional([
            'success' => true,
            'message' => 'Producto creado con éxito.',
        ]);
    }

    /**
     * Display the specified product.
     */
    public function show(int $id): ProductResource
    {
        $product = $this->productService->findById($id);

        return (new ProductResource($product->load('category')))->additional([
            'success' => true,
            'message' => 'Producto recuperado con éxito.',
        ]);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(ProductRequest $request, int $id): ProductResource
    {
        $dto = ProductDTO::fromArray($request->validated());
        $product = $this->productService->update($id, $dto);

        return (new ProductResource($product->load('category')))->additional([
            'success' => true,
            'message' => 'Producto actualizado con éxito.',
        ]);
    }

    /**
     * Toggle the active state of the specified product.
     */
    public function toggleActive(int $id): ProductResource
    {
        $product = $this->productService->toggleActive($id);

        return (new ProductResource($product->load('category')))->additional([
            'success' => true,
            'message' => 'Estado del producto actualizado con éxito.',
        ]);
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->productService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado con éxito.',
                'data' => (object) [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [],
            ], 422);
        }
    }
}
