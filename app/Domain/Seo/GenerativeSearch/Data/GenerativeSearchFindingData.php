<?php

declare(strict_types=1);

namespace App\Domain\Seo\GenerativeSearch\Data;

use App\Domain\Seo\GenerativeSearch\Enums\GenerativeSearchFindingSeverity;

final readonly class GenerativeSearchFindingData
{
    public function __construct(
        public string $id,
        public GenerativeSearchFindingSeverity $severity,
        public string $category,
        public string $message,
        public string $action,
    ) {}

    /**
     * @return array{
     *     id: string,
     *     severity: string,
     *     category: string,
     *     message: string,
     *     action: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'severity' => $this->severity->value,
            'category' => $this->category,
            'message' => $this->message,
            'action' => $this->action,
        ];
    }
}
