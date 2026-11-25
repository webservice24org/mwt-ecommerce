<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Data;

use App\Domain\Seo\Merchant\Enums\MerchantListingFindingSeverity;

final readonly class MerchantListingFindingData
{
    public function __construct(
        public string $id,
        public MerchantListingFindingSeverity $severity,
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

            'severity' => $this->severity
                ->value,

            'category' => $this->category,

            'message' => $this->message,

            'action' => $this->action,
        ];
    }
}
