<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ReportFilterDTO;
use App\Repositories\ReportRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Response;

class ReportService
{
    public function __construct(
        protected readonly ReportRepositoryInterface $reportRepository
    ) {}

    /**
     * Get summary metrics for the reports dashboard.
     */
    public function getSummary(ReportFilterDTO $dto): array
    {
        return $this->reportRepository->getSummary($dto);
    }

    /**
     * Get sales report metrics.
     */
    public function getSalesReport(ReportFilterDTO $dto): array
    {
        return $this->reportRepository->getSalesReport($dto);
    }

    /**
     * Get order list for sales report.
     */
    public function getSalesList(ReportFilterDTO $dto, bool $paginate = true)
    {
        return $this->reportRepository->getSalesList($dto, $paginate);
    }

    /**
     * Get most sold products list.
     */
    public function getMostSoldProductsReport(ReportFilterDTO $dto): Collection
    {
        return $this->reportRepository->getMostSoldProductsReport($dto);
    }

    /**
     * Get supplies inventory status report.
     */
    public function getSuppliesReport(): Collection
    {
        return $this->reportRepository->getSuppliesReport();
    }

    /**
     * Get production report metrics.
     */
    public function getProductionReport(ReportFilterDTO $dto): array
    {
        return $this->reportRepository->getProductionReport($dto);
    }

    /**
     * Get orders list for production report.
     */
    public function getProductionList(ReportFilterDTO $dto, bool $paginate = true)
    {
        return $this->reportRepository->getProductionList($dto, $paginate);
    }

    /**
     * Helper to export data as Excel-compatible CSV.
     */
    public function exportCsv(array $headers, array $rows, string $filename): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM so Excel decodes Spanish characters correctly
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, $headers);

            foreach ($rows as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    /**
     * Helper to export a view as PDF.
     */
    public function exportPdf(string $viewName, array $data, string $filename): Response
    {
        $pdf = Pdf::loadView($viewName, $data);
        $pdf->setPaper('letter', 'portrait');
        return $pdf->download($filename);
    }
}
