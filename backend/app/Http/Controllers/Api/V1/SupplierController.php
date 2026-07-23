<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTO\SupplierDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService
    ) {}

    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 10);
        $paginate = filter_var($request->query('paginate', true), FILTER_VALIDATE_BOOLEAN);
        $onlyActive = filter_var($request->query('only_active', false), FILTER_VALIDATE_BOOLEAN);

        if (! $paginate) {
            $suppliers = $this->supplierService->all($onlyActive);

            return response()->json([
                'success' => true,
                'message' => 'Proveedores recuperados con éxito.',
                'data' => SupplierResource::collection($suppliers),
            ]);
        }

        $suppliers = $this->supplierService->paginate($perPage, $search, $onlyActive);

        return SupplierResource::collection($suppliers)->additional([
            'success' => true,
            'message' => 'Proveedores recuperados con éxito.',
        ]);
    }

    /**
     * Store a newly created supplier in storage.
     */
    public function store(SupplierRequest $request): SupplierResource
    {
        $dto = SupplierDTO::fromArray($request->validated());
        $supplier = $this->supplierService->create($dto);

        return (new SupplierResource($supplier))->additional([
            'success' => true,
            'message' => 'Proveedor creado con éxito.',
        ]);
    }

    /**
     * Display the specified supplier.
     */
    public function show(int $id): SupplierResource
    {
        $supplier = $this->supplierService->findById($id);

        return (new SupplierResource($supplier))->additional([
            'success' => true,
            'message' => 'Proveedor recuperado con éxito.',
        ]);
    }

    /**
     * Update the specified supplier in storage.
     */
    public function update(SupplierRequest $request, int $id): SupplierResource
    {
        $dto = SupplierDTO::fromArray($request->validated());
        $supplier = $this->supplierService->update($id, $dto);

        return (new SupplierResource($supplier))->additional([
            'success' => true,
            'message' => 'Proveedor actualizado con éxito.',
        ]);
    }

    /**
     * Toggle the active state of a supplier.
     */
    public function toggleActive(int $id): SupplierResource
    {
        $supplier = $this->supplierService->toggleActive($id);

        return (new SupplierResource($supplier))->additional([
            'success' => true,
            'message' => 'Estado del proveedor actualizado con éxito.',
        ]);
    }
}
