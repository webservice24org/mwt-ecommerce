<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Operations;

use App\Domain\Reporting\Data\ActionQueueFiltersData;
use App\Domain\Reporting\Data\ActionQueueItemData;
use App\Domain\Reporting\Data\InventoryOperationsReportFiltersData;
use App\Domain\Reporting\Enums\ActionQueueSource;
use App\Domain\Reporting\Queries\GetAdminInventoryOperationsReportQuery;

final class InventoryOperationsActionQueueProvider implements ActionQueueProvider
{
    public function __construct(
        private readonly GetAdminInventoryOperationsReportQuery $query,
        private readonly ActionQueueItemFactory $itemFactory,
    ) {}

    public function source(): ActionQueueSource
    {
        return ActionQueueSource::InventoryOperations;
    }

    /**
     * @return array<int, ActionQueueItemData>
     */
    public function items(
        ActionQueueFiltersData $filters,
    ): array {
        $reportFilters =
            new InventoryOperationsReportFiltersData(
                perPage: $this->pageSize(),
            );

        $items = [];

        $page =
            1;

        $scanned =
            0;

        do {
            $result =
                $this
                    ->query
                    ->get(
                        filters: $reportFilters,

                        page: $page,
                    );

            foreach (
                $result->rows as $row
            ) {
                $scanned++;

                if (
                    ! $row
                        ->alert
                        ->requiresAttention()
                ) {
                    continue;
                }

                $reference =
                    $row->sku !== null &&
                    $row->sku !== ''
                    ? $row->sku
                    : '#'.
                    $row->productId;

                $items[] =
                    $this
                        ->itemFactory
                        ->make(
                            source: ActionQueueSource::InventoryOperations,

                            entityType: 'product',

                            entityId: $row->productId,

                            reference: $reference,

                            title: $row->name,

                            state: $row
                                ->alert
                                ->state
                                ->value,

                            stateLabel: $row
                                ->alert
                                ->state
                                ->label(),

                            severity: $row
                                ->alert
                                ->state
                                ->severity(),

                            nativePriority: $row
                                ->alert
                                ->state
                                ->priority(),

                            requiresAction: true,

                            /*
                             * Inventory alert persistence does
                             * not currently expose a dedicated
                             * alert age. Do not mislabel the
                             * product updated_at age as alert age.
                             */
                            ageHours: 0,

                            ageDays: 0,

                            actionUrl: route(
                                'admin.products.edit',
                                $row->productId,
                            ),

                            actionLabel: 'Review Product',
                        );

                if (
                    count(
                        $items,
                    ) >=
                    $this->maxRows()
                ) {
                    break 2;
                }
            }

            $page++;
        } while (
            $page <=
            $result
                ->pagination
                ->lastPage &&
            $scanned <
            $this->maxRows()
        );

        return $items;
    }

    private function pageSize(): int
    {
        return max(
            1,
            min(
                100,
                (int) config(
                    'reporting.action_queue.provider_page_size',
                    100,
                ),
            ),
        );
    }

    private function maxRows(): int
    {
        return max(
            1,
            (int) config(
                'reporting.action_queue.provider_max_rows',
                10_000,
            ),
        );
    }
}
