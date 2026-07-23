<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTO\RecipeDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecipeRequest;
use App\Http\Resources\RecipeResource;
use App\Services\RecipeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecipeController extends Controller
{
    public function __construct(
        protected RecipeService $recipeService
    ) {}

    /**
     * Display a listing of product variants with their recipes.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 10);
        $paginate = filter_var($request->query('paginate', true), FILTER_VALIDATE_BOOLEAN);
        $onlyActive = filter_var($request->query('only_active', false), FILTER_VALIDATE_BOOLEAN);

        if (! $paginate) {
            $recipes = $this->recipeService->all($onlyActive);

            return response()->json([
                'success' => true,
                'message' => 'Recetas recuperadas con éxito.',
                'data' => RecipeResource::collection($recipes),
            ]);
        }

        $recipes = $this->recipeService->paginate($perPage, $search, $onlyActive);

        return RecipeResource::collection($recipes)->additional([
            'success' => true,
            'message' => 'Recetas recuperadas con éxito.',
        ]);
    }

    /**
     * Display the specified recipe (by variant ID).
     */
    public function show(int $variantId): RecipeResource
    {
        $variant = $this->recipeService->findVariantWithRecipe($variantId);

        return (new RecipeResource($variant))->additional([
            'success' => true,
            'message' => 'Receta recuperada con éxito.',
        ]);
    }

    /**
     * Store / Update the recipe for the specified variant.
     */
    public function update(RecipeRequest $request, int $variantId): RecipeResource
    {
        // Force the request body product_variant_id to match the route parameter
        $data = $request->validated();
        $data['product_variant_id'] = $variantId;

        $dto = RecipeDTO::fromArray($data);
        $variant = $this->recipeService->saveRecipe($dto);

        return (new RecipeResource($variant))->additional([
            'success' => true,
            'message' => 'Receta guardada con éxito.',
        ]);
    }
}
