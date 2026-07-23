<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'period_sales' => (float) $this->resource['period_sales'],
            'registered_orders' => (int) $this->resource['registered_orders'],
            'products_sold' => (int) $this->resource['products_sold'],
            'registered_customers' => (int) $this->resource['registered_customers'],
            'pending_orders' => (int) $this->resource['pending_orders'],
            'delivered_orders' => (int) $this->resource['delivered_orders'],
            'cancelled_orders' => (int) $this->resource['cancelled_orders'],
            'critical_stock' => (int) $this->resource['critical_stock'],
        ];
    }
}
