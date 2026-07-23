<?php

declare(strict_types=1);

namespace App\DTO;

class ReportFilterDTO
{
    public function __construct(
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly string $sortBy,
        public readonly string $sortOrder,
        public readonly int $perPage
    ) {}

    /**
     * Create a DTO from request array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Default start date: beginning of current month
        $defaultStartDate = date('Y-m-01');
        // Default end date: today
        $defaultEndDate = date('Y-m-d');

        return new self(
            startDate: (string) ($data['start_date'] ?? $defaultStartDate),
            endDate: (string) ($data['end_date'] ?? $defaultEndDate),
            sortBy: (string) ($data['sort_by'] ?? 'created_at'),
            sortOrder: (string) ($data['sort_order'] ?? 'desc'),
            perPage: (int) ($data['per_page'] ?? 10)
        );
    }
}
