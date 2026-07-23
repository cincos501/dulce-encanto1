<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTO\ReportFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportFilterRequest;
use App\Http\Resources\MostSoldProductResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductionReportResource;
use App\Http\Resources\ReportSummaryResource;
use App\Http\Resources\SalesReportResource;
use App\Http\Resources\SupplyReportResource;
use App\Services\ReportService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(
        protected readonly ReportService $reportService
    ) {}

    /**
     * Get reports dashboard summary metrics.
     */
    public function summary(ReportFilterRequest $request): ReportSummaryResource
    {
        $dto = ReportFilterDTO::fromArray($request->validated());
        $summary = $this->reportService->getSummary($dto);

        return new ReportSummaryResource($summary);
    }

    /**
     * Get sales report metrics and orders list.
     */
    public function sales(ReportFilterRequest $request): JsonResponse
    {
        $dto = ReportFilterDTO::fromArray($request->validated());
        $metrics = $this->reportService->getSalesReport($dto);
        $orders = $this->reportService->getSalesList($dto, true);

        return response()->json([
            'success' => true,
            'data' => new SalesReportResource($metrics),
            'orders' => OrderResource::collection($orders)->response()->getData(true),
        ]);
    }

    /**
     * Get most sold products report list.
     */
    public function products(ReportFilterRequest $request): JsonResponse
    {
        $dto = ReportFilterDTO::fromArray($request->validated());
        $products = $this->reportService->getMostSoldProductsReport($dto);

        return response()->json([
            'success' => true,
            'data' => MostSoldProductResource::collection($products),
        ]);
    }

    /**
     * Get supplies inventory status report.
     */
    public function supplies(): JsonResponse
    {
        $supplies = $this->reportService->getSuppliesReport();

        return response()->json([
            'success' => true,
            'data' => SupplyReportResource::collection($supplies),
        ]);
    }

    /**
     * Get production report metrics and orders list.
     */
    public function production(ReportFilterRequest $request): JsonResponse
    {
        $dto = ReportFilterDTO::fromArray($request->validated());
        $metrics = $this->reportService->getProductionReport($dto);
        $orders = $this->reportService->getProductionList($dto, true);

        return response()->json([
            'success' => true,
            'data' => new ProductionReportResource($metrics),
            'orders' => OrderResource::collection($orders)->response()->getData(true),
        ]);
    }

    /**
     * Export sales report to Excel (CSV).
     */
    public function exportSalesExcel(ReportFilterRequest $request): StreamedResponse
    {
        $dto = ReportFilterDTO::fromArray($request->validated());
        $orders = $this->reportService->getSalesList($dto, false);

        $headers = ['Nº Pedido', 'Cliente', 'Fecha', 'Estado', 'Tipo de Entrega', 'Total (Bs.)'];
        $rows = [];

        foreach ($orders as $o) {
            $deliveryType = 'Retiro en tienda';
            $email = $o->customer?->email;
            if ($email && str_starts_with(trim($email), '{')) {
                $parsed = json_decode($email, true);
                if (is_array($parsed)) {
                    $deliveryType = $parsed['delivery_type'] ?? 'Retiro en tienda';
                }
            }

            $rows[] = [
                $o->id,
                $o->customer?->full_name ?? 'Cliente Anónimo',
                $o->created_at->format('Y-m-d H:i:s'),
                $o->status,
                $deliveryType,
                number_format((float) $o->total, 2, '.', ''),
            ];
        }

        return $this->reportService->exportCsv($headers, $rows, 'reporte_ventas_' . date('Ymd_His') . '.csv');
    }

    /**
     * Export sales report to PDF.
     */
    public function exportSalesPdf(ReportFilterRequest $request): Response
    {
        $dto = ReportFilterDTO::fromArray($request->validated());
        $metrics = $this->reportService->getSalesReport($dto);
        $orders = $this->reportService->getSalesList($dto, false);

        $data = [
            'startDate' => $dto->startDate,
            'endDate' => $dto->endDate,
            'metrics' => $metrics,
            'orders' => $orders,
        ];

        return $this->reportService->exportPdf('reports.sales_pdf', $data, 'reporte_ventas_' . date('Ymd_His') . '.pdf');
    }

    /**
     * Export products report to Excel (CSV).
     */
    public function exportProductsExcel(ReportFilterRequest $request): StreamedResponse
    {
        $dto = ReportFilterDTO::fromArray($request->validated());
        $products = $this->reportService->getMostSoldProductsReport($dto);

        $headers = ['Producto', 'Presentación', 'Cantidad Vendida', 'Total Generado (Bs.)'];
        $rows = [];

        foreach ($products as $p) {
            $rows[] = [
                $p->product_name,
                $p->variant_name,
                $p->quantity_sold,
                number_format((float) $p->total_generated, 2, '.', ''),
            ];
        }

        return $this->reportService->exportCsv($headers, $rows, 'reporte_productos_mas_vendidos_' . date('Ymd_His') . '.csv');
    }

    /**
     * Export products report to PDF.
     */
    public function exportProductsPdf(ReportFilterRequest $request): Response
    {
        $dto = ReportFilterDTO::fromArray($request->validated());
        $products = $this->reportService->getMostSoldProductsReport($dto);

        $data = [
            'startDate' => $dto->startDate,
            'endDate' => $dto->endDate,
            'products' => $products,
        ];

        return $this->reportService->exportPdf('reports.products_pdf', $data, 'reporte_productos_mas_vendidos_' . date('Ymd_His') . '.pdf');
    }

    /**
     * Export supplies report to Excel (CSV).
     */
    public function exportSuppliesExcel(): StreamedResponse
    {
        $supplies = $this->reportService->getSuppliesReport();

        $headers = ['Nombre del Insumo', 'Stock Actual', 'Unidad', 'Stock Mínimo', 'Estado'];
        $rows = [];

        foreach ($supplies as $s) {
            $rows[] = [
                $s->name,
                $s->stock,
                $s->unit,
                $s->minimum_stock,
                $s->status,
            ];
        }

        return $this->reportService->exportCsv($headers, $rows, 'reporte_insumos_' . date('Ymd_His') . '.csv');
    }

    /**
     * Export supplies report to PDF.
     */
    public function exportSuppliesPdf(): Response
    {
        $supplies = $this->reportService->getSuppliesReport();

        $data = [
            'supplies' => $supplies,
        ];

        return $this->reportService->exportPdf('reports.supplies_pdf', $data, 'reporte_insumos_' . date('Ymd_His') . '.pdf');
    }

    /**
     * Export production report to Excel (CSV).
     */
    public function exportProductionExcel(ReportFilterRequest $request): StreamedResponse
    {
        $dto = ReportFilterDTO::fromArray($request->validated());
        $orders = $this->reportService->getProductionList($dto, false);

        $headers = ['Nº Pedido', 'Fecha', 'Cliente', 'Cantidad de Productos', 'Estado', 'Tipo de Entrega'];
        $rows = [];

        foreach ($orders as $o) {
            $deliveryType = 'Retiro en tienda';
            $email = $o->customer?->email;
            if ($email && str_starts_with(trim($email), '{')) {
                $parsed = json_decode($email, true);
                if (is_array($parsed)) {
                    $deliveryType = $parsed['delivery_type'] ?? 'Retiro en tienda';
                }
            }

            $rows[] = [
                $o->id,
                $o->created_at->format('Y-m-d'),
                $o->customer?->full_name ?? 'Cliente Anónimo',
                $o->items->sum('quantity'),
                $o->status,
                $deliveryType,
            ];
        }

        return $this->reportService->exportCsv($headers, $rows, 'reporte_produccion_' . date('Ymd_His') . '.csv');
    }

    /**
     * Export production report to PDF.
     */
    public function exportProductionPdf(ReportFilterRequest $request): Response
    {
        $dto = ReportFilterDTO::fromArray($request->validated());
        $metrics = $this->reportService->getProductionReport($dto);
        $orders = $this->reportService->getProductionList($dto, false);

        $data = [
            'startDate' => $dto->startDate,
            'endDate' => $dto->endDate,
            'metrics' => $metrics,
            'orders' => $orders,
        ];

        return $this->reportService->exportPdf('reports.production_pdf', $data, 'reporte_produccion_' . date('Ymd_His') . '.pdf');
    }
}
