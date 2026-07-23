<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTO\SupplyDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\SupplyRequest;
use App\Http\Resources\SupplyResource;
use App\Services\SupplyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplyController extends Controller
{
    public function __construct(
        protected SupplyService $supplyService
    ) {}

    /**
     * Display a listing of supplies.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 10);
        $paginate = filter_var($request->query('paginate', true), FILTER_VALIDATE_BOOLEAN);
        $onlyActive = filter_var($request->query('only_active', false), FILTER_VALIDATE_BOOLEAN);

        if (! $paginate) {
            $supplies = $this->supplyService->all($onlyActive);

            return response()->json([
                'success' => true,
                'message' => 'Insumos recuperados con éxito.',
                'data' => SupplyResource::collection($supplies),
            ]);
        }

        $supplies = $this->supplyService->paginate($perPage, $search, $onlyActive);

        return SupplyResource::collection($supplies)->additional([
            'success' => true,
            'message' => 'Insumos recuperados con éxito.',
        ]);
    }

    /**
     * Store a newly created supply in storage.
     */
    public function store(SupplyRequest $request): SupplyResource
    {
        $dto = SupplyDTO::fromArray($request->validated());
        $supply = $this->supplyService->create($dto);

        return (new SupplyResource($supply))->additional([
            'success' => true,
            'message' => 'Insumo creado con éxito.',
        ]);
    }

    /**
     * Display the specified supply.
     */
    public function show(int $id): SupplyResource
    {
        $supply = $this->supplyService->findById($id);

        return (new SupplyResource($supply))->additional([
            'success' => true,
            'message' => 'Insumo recuperado con éxito.',
        ]);
    }

    /**
     * Update the specified supply in storage.
     */
    public function update(SupplyRequest $request, int $id): SupplyResource
    {
        $dto = SupplyDTO::fromArray($request->validated());
        $supply = $this->supplyService->update($id, $dto);

        return (new SupplyResource($supply))->additional([
            'success' => true,
            'message' => 'Insumo actualizado con éxito.',
        ]);
    }

    /**
     * Toggle the active state of a supply.
     */
    public function toggleActive(int $id): SupplyResource
    {
        $supply = $this->supplyService->toggleActive($id);

        return (new SupplyResource($supply))->additional([
            'success' => true,
            'message' => 'Estado del insumo actualizado con éxito.',
        ]);
    }

    /**
     * Register a supply purchase and increment stock.
     */
    public function purchase(SupplyRequest $request): JsonResponse
    {
        $this->supplyService->registerPurchase($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Compra de insumos registrada con éxito.',
        ]);
    }
}
