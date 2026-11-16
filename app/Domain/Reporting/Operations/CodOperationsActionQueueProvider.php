<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Operations;

use App\Domain\Reporting\Data\ActionQueueFiltersData;
use App\Domain\Reporting\Data\ActionQueueItemData;
use App\Domain\Reporting\Data\CodOperationsReportFiltersData;
use App\Domain\Reporting\Enums\ActionQueueSource;
use App\Domain\Reporting\Queries\GetAdminCodOperationsReportQuery;

final class CodOperationsActionQueueProvider implements ActionQueueProvider
{
    public function __construct(
        private readonly GetAdminCodOperationsReportQuery $query,
        private readonly ActionQueueReportRangeFactory $rangeFactory,
        private readonly ActionQueueItemFactory $itemFactory,
    ) {}

    public function source(): ActionQueueSource
    {
        return ActionQueueSource::CodOperations;
    }

    /**
     * @return array<int, ActionQueueItemData>
     */
    public function items(
        ActionQueueFiltersData $filters,
    ): array {
        $reportFilters =
            new CodOperationsReportFiltersData(
                range: $this
                    ->rangeFactory
                    ->make(),

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
                        ->collectionRisk
                        ->requiresFollowUp()
                ) {
                    continue;
                }

                $items[] =
                    $this
                        ->itemFactory
                        ->make(
                            source: ActionQueueSource::CodOperations,

                            entityType: 'order',

                            entityId: $row->orderId,

                            reference: $row->orderNumber,

                            title: $row->orderNumber.
                            ' · '.
                            $row->customerName,

                            state: $row
                                ->collectionRisk
                                ->state
                                ->value,

                            stateLabel: $row
                                ->collectionRisk
                                ->state
                                ->label(),

                            severity: $row
                                ->collectionRisk
                                ->state
                                ->severity(),

                            nativePriority: $row
                                ->collectionRisk
                                ->state
                                ->priority(),

                            requiresAction: true,

                            ageHours: $row->ageHours,

                            ageDays: $row->ageDays,

                            actionUrl: route(
                                'admin.orders.show',
                                $row->orderId,
                            ),

                            actionLabel: 'Review Payment',
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
