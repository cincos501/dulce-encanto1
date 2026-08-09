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

        $totalSales = (float) DB::table('vw_orders_dashboard_summary')
            ->where('status', '!=', 'Cancelado')
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

        $ordersCount = DB::table('vw_orders_dashboard_summary')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $productsSold = (int) DB::table('vw_orders_dashboard_summary')
            ->where('status', '!=', 'Cancelado')
            ->whereBetween('created_at', [$start, $end])
            ->sum('products_count');

        $totalCustomers = Customer::count();

        $pendingCount = DB::table('vw_orders_dashboard_summary')
            ->where('status', 'Pendiente')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $deliveredCount = DB::table('vw_orders_dashboard_summary')
            ->where('status', 'Entregado')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $cancelledCount = DB::table('vw_orders_dashboard_summary')
            ->where('status', 'Cancelado')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $criticalStockCount = DB::table('vw_supplies_status')
            ->where('status', 'Stock crítico')
            ->count();

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

        $totalRevenue = (float) DB::table('vw_orders_dashboard_summary')
            ->where('status', '!=', 'Cancelado')
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

        $ordersCount = DB::table('vw_orders_dashboard_summary')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $deliveredOrdersCount = DB::table('vw_orders_dashboard_summary')
            ->where('status', 'Entregado')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $avgTicket = $ordersCount > 0 ? (float) ($totalRevenue / $ordersCount) : 0.00;

        $statuses = ['Pendiente', 'Confirmado', 'En preparación', 'Listo', 'Entregado', 'Cancelado'];
        $statusCounts = [];
        foreach ($statuses as $status) {
            $statusCounts[strtolower(str_replace(' ', '_', $status))] = DB::table('vw_orders_dashboard_summary')
                ->where('status', $status)
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

        return DB::table('vw_most_sold_products')
            ->select(
                'product_name',
                'variant_name',
                DB::raw('SUM(quantity_sold) as quantity_sold'),
                DB::raw('SUM(total_generated) as total_generated')
            )
            ->where('order_status', '!=', 'Cancelado')
            ->whereBetween('order_created_at', [$start, $end])
            ->groupBy('product_name', 'variant_name')
            ->orderBy('quantity_sold', 'desc')
            ->get();
    }

    /**
     * Get list of supplies and calculate stock state.
     */
    public function getSuppliesReport(): Collection
    {
        return DB::table('vw_supplies_status')
            ->select('name', 'stock', 'unit', 'minimum_stock', 'status')
            ->orderBy('name')
            ->get();
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
            $statusCounts[strtolower(str_replace(' ', '_', $status))] = DB::table('vw_orders_dashboard_summary')
                ->where('status', $status)
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
