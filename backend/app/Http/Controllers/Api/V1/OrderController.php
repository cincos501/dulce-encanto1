<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Requests\StoreOrderRequest;
use App\DTO\StoreOrderDTO;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Display a listing of orders.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 10);

        $orders = $this->orderService->paginate($perPage, $search);

        return OrderResource::collection($orders)->additional([
            'success' => true,
            'message' => 'Pedidos recuperados con éxito.',
        ]);
    }

    /**
     * Display the specified order.
     */
    public function show(int $id): OrderResource
    {
        $order = $this->orderService->findById($id);

        return (new OrderResource($order))->additional([
            'success' => true,
            'message' => 'Pedido recuperado con éxito.',
        ]);
    }

    /**
     * Update the status of the specified order.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, int $id): OrderResource
    {
        $data = $request->validated();
        $order = $this->orderService->updateStatus($id, $data['status']);

        return (new OrderResource($order))->additional([
            'success' => true,
            'message' => 'Estado del pedido actualizado con éxito.',
        ]);
    }

    /**
     * Store a newly created order from the public checkout.
     */
    public function store(StoreOrderRequest $request): OrderResource
    {
        $dto = StoreOrderDTO::fromArray($request->validated());
        $order = $this->orderService->createOrder($dto);

        // Load items.productVariant relation before converting to resource so it doesn't get omitted
        $order->load(['items.productVariant', 'customer']);

        return (new OrderResource($order))->additional([
            'success' => true,
            'message' => 'Pedido registrado con éxito.',
        ]);
    }

    /**
     * Get all customers.
     */
    public function customers(): JsonResponse
    {
        $customers = $this->orderService->getCustomers();

        return response()->json([
            'success' => true,
            'message' => 'Clientes recuperados con éxito.',
            'data' => $customers,
        ]);
    }
}
