<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\ReportFilterDTO;
use Illuminate\Support\Collection;

interface ReportRepositoryInterface
{
    /**
     * Get real-time summary statistics for the reports dashboard.
     */
    public function getSummary(ReportFilterDTO $dto): array;

    /**
     * Get aggregated metrics for sales report.
     */
    public function getSalesReport(ReportFilterDTO $dto): array;

    /**
     * Get list of orders for sales report (paginated or full collection).
     */
    public function getSalesList(ReportFilterDTO $dto, bool $paginate = true);

    /**
     * Get list of top selling products and presentations.
     */
    public function getMostSoldProductsReport(ReportFilterDTO $dto): Collection;

    /**
     * Get list of supplies and calculate stock state.
     */
    public function getSuppliesReport(): Collection;

    /**
     * Get production status metrics for orders.
     */
    public function getProductionReport(ReportFilterDTO $dto): array;

    /**
     * Get detailed list of orders with item counts for production report.
     */
    public function getProductionList(ReportFilterDTO $dto, bool $paginate = true);
}
