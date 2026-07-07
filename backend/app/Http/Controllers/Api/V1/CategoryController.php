<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTO\CategoryDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Display a listing of categories.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 10);
        $paginate = filter_var($request->query('paginate', true), FILTER_VALIDATE_BOOLEAN);

        if (! $paginate) {
            $categories = $this->categoryService->all();

            return response()->json([
                'success' => true,
                'message' => 'Categorías recuperadas con éxito.',
                'data' => CategoryResource::collection($categories),
            ]);
        }

        $categories = $this->categoryService->paginate($perPage, $search);

        return CategoryResource::collection($categories)->additional([
            'success' => true,
            'message' => 'Categorías recuperadas con éxito.',
        ]);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(CategoryRequest $request): CategoryResource
    {
        $dto = CategoryDTO::fromArray($request->validated());
        $category = $this->categoryService->create($dto);

        return (new CategoryResource($category))->additional([
            'success' => true,
            'message' => 'Categoría creada con éxito.',
        ]);
    }

    /**
     * Display the specified category.
     */
    public function show(int $id): CategoryResource
    {
        $category = $this->categoryService->findById($id);

        return (new CategoryResource($category))->additional([
            'success' => true,
            'message' => 'Categoría recuperada con éxito.',
        ]);
    }

    /**
     * Update the specified category in storage.
     */
    public function update(CategoryRequest $request, int $id): CategoryResource
    {
        $dto = CategoryDTO::fromArray($request->validated());
        $category = $this->categoryService->update($id, $dto);

        return (new CategoryResource($category))->additional([
            'success' => true,
            'message' => 'Categoría actualizada con éxito.',
        ]);
    }

    /**
     * Toggle the active state of the specified category.
     */
    public function toggleActive(int $id): CategoryResource
    {
        $category = $this->categoryService->toggleActive($id);

        return (new CategoryResource($category))->additional([
            'success' => true,
            'message' => 'Estado de la categoría actualizado con éxito.',
        ]);
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->categoryService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada con éxito.',
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
