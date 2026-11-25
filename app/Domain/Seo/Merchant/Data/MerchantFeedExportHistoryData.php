<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Data;

final readonly class MerchantFeedExportHistoryData
{
    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, int|null>  $pagination
     */
    public function __construct(
        public array $items,
        public array $pagination,
    ) {}

    /**
     * @return array{
     *     items: list<array<string, mixed>>,
     *     pagination: array<string, int|null>
     * }
     */
    public function toArray(): array
    {
        return [
            'items' => $this->items,

            'pagination' => $this->pagination,
        ];
    }
}
