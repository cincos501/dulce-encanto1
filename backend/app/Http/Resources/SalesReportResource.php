<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'total_sales_count' => (int) $this->resource['total_sales_count'],
            'total_orders_count' => (int) $this->resource['total_orders_count'],
            'total_revenue' => (float) $this->resource['total_revenue'],
            'average_ticket' => (float) $this->resource['average_ticket'],
            'status_counts' => $this->resource['status_counts'],
        ];
    }
}
