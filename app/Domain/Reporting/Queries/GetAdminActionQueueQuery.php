<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Data\ActionQueueFiltersData;
use App\Domain\Reporting\Data\ActionQueueItemData;
use App\Domain\Reporting\Data\ActionQueueResultData;
use App\Domain\Reporting\Data\ReportPaginationData;
use App\Domain\Reporting\Operations\ActionQueueProviderRegistry;

final class GetAdminActionQueueQuery
{
    public function __construct(
        private readonly ActionQueueProviderRegistry $registry,
    ) {}

    public function get(
        ActionQueueFiltersData $filters,
        int $page = 1,
    ): ActionQueueResultData {
        $items = [];

        $sourceValues =
            $filters
                ->sourceValues();

        foreach (
            $this
                ->registry
                ->providers() as $provider
        ) {
            if (
                $sourceValues !== [] &&
                ! in_array(
                    $provider
                        ->source()
                        ->value,
                    $sourceValues,
                    true,
                )
            ) {
                continue;
            }

            foreach (
                $provider->items(
                    $filters,
                ) as $item
            ) {
                if (
                    ! $this->matchesFilters(
                        item: $item,

                        filters: $filters,
                    )
                ) {
                    continue;
                }

                $items[] =
                    $item;
            }
        }

        usort(
            $items,
            static function (ActionQueueItemData $left, ActionQueueItemData $right): int {
                $priority =
                    $right->priority <=>
                    $left->priority;

                if (
                    $priority !== 0
                ) {
                    return $priority;
                }

                $severity =
                    $right
                        ->severity
                        ->rank() <=>
                    $left
                        ->severity
                        ->rank();

                if (
                    $severity !== 0
                ) {
                    return $severity;
                }

                $age =
                    $right->ageHours <=>
                    $left->ageHours;

                if (
                    $age !== 0
                ) {
                    return $age;
                }

                $source =
                    strcmp(
                        $left
                            ->source
                            ->value,
                        $right
                            ->source
                            ->value,
                    );

                if (
                    $source !== 0
                ) {
                    return $source;
                }

                $entityType =
                    strcmp(
                        $left->entityType,
                        $right->entityType,
                    );

                if (
                    $entityType !== 0
                ) {
                    return $entityType;
                }

                return $left->entityId <=>
                    $right->entityId;
            },
        );

        $perPage =
            $filters
                ->normalizedPerPage();

        $currentPage =
            max(
                1,
                $page,
            );

        $total =
            count(
                $items,
            );

        $lastPage =
            max(
                1,
                (int) ceil(
                    $total /
                    $perPage,
                ),
            );

        $offset =
            (
                $currentPage -
                1
            ) *
            $perPage;

        $pageItems =
            array_slice(
                $items,
                $offset,
                $perPage,
            );

        return new ActionQueueResultData(
            items: $pageItems,

            pagination: new ReportPaginationData(
                currentPage: $currentPage,

                lastPage: $lastPage,

                perPage: $perPage,

                total: $total,

                nextPageUrl: null,

                previousPageUrl: null,
            ),
        );
    }

    private function matchesFilters(
        ActionQueueItemData $item,
        ActionQueueFiltersData $filters,
    ): bool {
        $severityValues =
            $filters
                ->severityValues();

        if (
            $severityValues !== [] &&
            ! in_array(
                $item
                    ->severity
                    ->value,
                $severityValues,
                true,
            )
        ) {
            return false;
        }

        if (
            $filters->requiresAction !== null &&
            $item->requiresAction !==
            $filters->requiresAction
        ) {
            return false;
        }

        $search =
            $filters
                ->normalizedSearch();

        if (
            $search !== null
        ) {
            $needle =
                mb_strtolower(
                    $search,
                );

            $haystack =
                mb_strtolower(
                    implode(
                        ' ',
                        [
                            $item->reference,
                            $item->title,
                            $item->reason,
                            $item->entityType,
                            $item
                                ->source
                                ->label(),
                        ],
                    ),
                );

            if (
                ! str_contains(
                    $haystack,
                    $needle,
                )
            ) {
                return false;
            }
        }

        return true;
    }
}
