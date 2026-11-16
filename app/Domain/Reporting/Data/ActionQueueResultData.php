<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class ActionQueueResultData
{
    /**
     * @param  array<int, ActionQueueItemData>  $items
     */
    public function __construct(
        public array $items,
        public ReportPaginationData $pagination,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'items' => array_map(
                static fn (
                    ActionQueueItemData $item,
                ): array => $item->toArray(),

                $this->items,
            ),

            'pagination' => $this
                ->pagination
                ->toArray(),
        ];
    }
}
