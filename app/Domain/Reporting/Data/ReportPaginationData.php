<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class ReportPaginationData
{
    public function __construct(
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
        public ?string $nextPageUrl,
        public ?string $previousPageUrl,
    ) {}

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'current_page' => $this->currentPage,

            'last_page' => $this->lastPage,

            'per_page' => $this->perPage,

            'total' => $this->total,

            'next_page_url' => $this->nextPageUrl,

            'previous_page_url' => $this->previousPageUrl,
        ];
    }
}
