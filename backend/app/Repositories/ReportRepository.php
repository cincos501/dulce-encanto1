<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\ReportFilterDTO;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Supply;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportRepository implements ReportRepositoryInterface
{
    /**
     * Get real-time summary statistics for the reports dashboard.
     */
    public function getSummary(ReportFilterDTO $dto): array
    {
        $start = $dto->startDate . ' 00:00:00';
        $end = $dto->endDate . ' 23:59:59';

        $totalSales = (float) Order::where('status', '!=', 'Cancelado')
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

        $ordersCount = Order::whereBetween('created_at', [$start, $end])
            ->count();

        $productsSold = (int) OrderItem::whereHas('order', function ($query) use ($start, $end) {
            $query->where('status', '!=', 'Cancelado')
                  ->whereBetween('created_at', [$start, $end]);
        })->sum('quantity');

        $totalCustomers = Customer::count();

        $pendingCount = Order::where('status', 'Pendiente')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $deliveredCount = Order::where('status', 'Entregado')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $cancelledCount = Order::where('status', 'Cancelado')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $criticalStockCount = Supply::where(static function ($query) {
            $query->whereColumn('stock', '<=', DB::raw('minimum_stock * 0.25'))
                  ->orWhere('stock', '<=', 0);
        })->count();

        return [
            'period_sales' => $totalSales,
            'registered_orders' => $ordersCount,
            'products_sold' => $productsSold,
            'registered_customers' => $totalCustomers,
            'pending_orders' => $pendingCount,
            'delivered_orders' => $deliveredCount,
            'cancelled_orders' => $cancelledCount,
            'critical_stock' => $criticalStockCount,
        ];
    }

    /**
     * Get aggregated metrics for sales report.
     */
    public function getSalesReport(ReportFilterDTO $dto): array
    {
        $start = $dto->startDate . ' 00:00:00';
        $end = $dto->endDate . ' 23:59:59';

        $totalRevenue = (float) Order::where('status', '!=', 'Cancelado')
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

        $ordersCount = Order::whereBetween('created_at', [$start, $end])
            ->count();

        $deliveredOrdersCount = Order::where('status', 'Entregado')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $avgTicket = $ordersCount > 0 ? (float) ($totalRevenue / $ordersCount) : 0.00;

        $statuses = ['Pendiente', 'Confirmado', 'En preparación', 'Listo', 'Entregado', 'Cancelado'];
        $statusCounts = [];
        foreach ($statuses as $status) {
            $statusCounts[strtolower(str_replace(' ', '_', $status))] = Order::where('status', $status)
                ->whereBetween('created_at', [$start, $end])
                ->count();
        }

        return [
            'total_sales_count' => $deliveredOrdersCount,
            'total_orders_count' => $ordersCount,
            'total_revenue' => $totalRevenue,
            'average_ticket' => $avgTicket,
            'status_counts' => $statusCounts,
        ];
    }

    /**
     * Get list of orders for sales report (paginated or full collection).
     */
    public function getSalesList(ReportFilterDTO $dto, bool $paginate = true)
    {
        $start = $dto->startDate . ' 00:00:00';
        $end = $dto->endDate . ' 23:59:59';

        $query = Order::with('customer')
            ->whereBetween('created_at', [$start, $end]);

        $sortBy = $dto->sortBy === 'created_at' ? 'created_at' : ($dto->sortBy === 'total' ? 'total' : 'status');
        $sortOrder = $dto->sortOrder === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        if ($paginate) {
            return $query->paginate($dto->perPage);
        }

        return $query->get();
    }

    /**
     * Get list of top selling products and presentations.
     */
    public function getMostSoldProductsReport(ReportFilterDTO $dto): Collection
    {
        $start = $dto->startDate . ' 00:00:00';
        $end = $dto->endDate . ' 23:59:59';

        return OrderItem::select(
                'products.name as product_name',
                'product_variants.name as variant_name',
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
                DB::raw('SUM(order_items.quantity * order_items.price) as total_generated')
            )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('orders.status', '!=', 'Cancelado')
            ->whereBetween('orders.created_at', [$start, $end])
            ->groupBy('products.name', 'product_variants.name')
            ->orderBy('quantity_sold', 'desc')
            ->get();
    }

    /**
     * Get list of supplies and calculate stock state.
     */
    public function getSuppliesReport(): Collection
    {
        return Supply::select('name', 'stock', 'unit', 'minimum_stock')
            ->orderBy('name')
            ->get()
            ->map(static function ($supply) {
                $stock = (float) $supply->stock;
                $minStock = (float) $supply->minimum_stock;

                if ($stock <= $minStock * 0.25 || $stock <= 0) {
                    $status = 'Stock crítico';
                } elseif ($stock <= $minStock) {
                    $status = 'Stock bajo';
                } else {
                    $status = 'Stock suficiente';
                }

                $supply->setAttribute('status', $status);
                return $supply;
            });
    }

    /**
     * Get production status metrics for orders.
     */
    public function getProductionReport(ReportFilterDTO $dto): array
    {
        $start = $dto->startDate . ' 00:00:00';
        $end = $dto->endDate . ' 23:59:59';

        $statuses = ['Pendiente', 'Confirmado', 'En preparación', 'Listo', 'Entregado', 'Cancelado'];
        $statusCounts = [];
        foreach ($statuses as $status) {
            $statusCounts[strtolower(str_replace(' ', '_', $status))] = Order::where('status', $status)
                ->whereBetween('created_at', [$start, $end])
                ->count();
        }

        return [
            'status_counts' => $statusCounts,
        ];
    }

    /**
     * Get detailed list of orders with item counts for production report.
     */
    public function getProductionList(ReportFilterDTO $dto, bool $paginate = true)
    {
        $start = $dto->startDate . ' 00:00:00';
        $end = $dto->endDate . ' 23:59:59';

        $query = Order::with(['customer', 'items'])
            ->whereBetween('created_at', [$start, $end]);

        $sortBy = $dto->sortBy === 'created_at' ? 'created_at' : ($dto->sortBy === 'total' ? 'total' : 'status');
        $sortOrder = $dto->sortOrder === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        if ($paginate) {
            return $query->paginate($dto->perPage);
        }

        return $query->get();
    }
}
