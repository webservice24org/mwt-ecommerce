<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Reporting\Data\ActionQueueFiltersData;
use App\Domain\Reporting\Data\ActionQueueItemData;
use App\Domain\Reporting\Enums\ActionQueueSeverity;
use App\Domain\Reporting\Enums\ActionQueueSource;
use App\Domain\Reporting\Operations\ActionQueueProvider;
use App\Domain\Reporting\Operations\ActionQueueProviderRegistry;
use App\Domain\Reporting\Queries\GetAdminActionQueueQuery;
use Tests\TestCase;

final class AdminActionQueueQueryTest extends TestCase
{
    public function test_queue_merges_items_from_multiple_sources(): void
    {
        $registry =
            new ActionQueueProviderRegistry;

        $registry->register(
            $this->provider(
                source: ActionQueueSource::OperationalOrders,

                items: [
                    $this->item(
                        source: ActionQueueSource::OperationalOrders,

                        entityId: 1,

                        reference: 'ORD-001',

                        title: 'Order requires attention',

                        priority: 40,

                        severity: ActionQueueSeverity::High,
                    ),
                ],
            ),
        );

        $registry->register(
            $this->provider(
                source: ActionQueueSource::InventoryOperations,

                items: [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 2,

                        reference: 'SKU-001',

                        title: 'Negative inventory',

                        priority: 90,

                        severity: ActionQueueSeverity::Critical,
                    ),
                ],
            ),
        );

        $result =
            $this->query(
                $registry,
            )->get(
                new ActionQueueFiltersData,
            );

        $this->assertSame(
            2,
            $result
                ->pagination
                ->total,
        );

        $this->assertCount(
            2,
            $result->items,
        );
    }

    public function test_queue_orders_highest_priority_first(): void
    {
        $registry =
            new ActionQueueProviderRegistry;

        $registry->register(
            $this->provider(
                source: ActionQueueSource::InventoryOperations,

                items: [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 1,

                        reference: 'LOW',

                        title: 'Lower priority',

                        priority: 30,

                        severity: ActionQueueSeverity::Warning,
                    ),

                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 2,

                        reference: 'HIGH',

                        title: 'Highest priority',

                        priority: 90,

                        severity: ActionQueueSeverity::Critical,
                    ),
                ],
            ),
        );

        $result =
            $this->query(
                $registry,
            )->get(
                new ActionQueueFiltersData,
            );

        $this->assertSame(
            'HIGH',
            $result
                ->items[0]
                ->reference,
        );

        $this->assertSame(
            'LOW',
            $result
                ->items[1]
                ->reference,
        );
    }

    public function test_source_filter_excludes_other_providers(): void
    {
        $registry =
            new ActionQueueProviderRegistry;

        $registry->register(
            $this->provider(
                source: ActionQueueSource::CodOperations,

                items: [
                    $this->item(
                        source: ActionQueueSource::CodOperations,

                        entityId: 1,

                        reference: 'COD-001',

                        title: 'COD follow-up',

                        priority: 70,

                        severity: ActionQueueSeverity::Critical,
                    ),
                ],
            ),
        );

        $registry->register(
            $this->provider(
                source: ActionQueueSource::InventoryOperations,

                items: [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 2,

                        reference: 'SKU-001',

                        title: 'Inventory follow-up',

                        priority: 90,

                        severity: ActionQueueSeverity::Critical,
                    ),
                ],
            ),
        );

        $result =
            $this->query(
                $registry,
            )->get(
                new ActionQueueFiltersData(
                    sources: [
                        ActionQueueSource::CodOperations->value,
                    ],
                ),
            );

        $this->assertCount(
            1,
            $result->items,
        );

        $this->assertSame(
            ActionQueueSource::CodOperations,
            $result
                ->items[0]
                ->source,
        );
    }

    public function test_severity_filter_is_applied(): void
    {
        $registry =
            new ActionQueueProviderRegistry;

        $registry->register(
            $this->provider(
                source: ActionQueueSource::OperationalOrders,

                items: [
                    $this->item(
                        source: ActionQueueSource::OperationalOrders,

                        entityId: 1,

                        reference: 'WARNING',

                        title: 'Warning item',

                        priority: 20,

                        severity: ActionQueueSeverity::Warning,
                    ),

                    $this->item(
                        source: ActionQueueSource::OperationalOrders,

                        entityId: 2,

                        reference: 'CRITICAL',

                        title: 'Critical item',

                        priority: 80,

                        severity: ActionQueueSeverity::Critical,
                    ),
                ],
            ),
        );

        $result =
            $this->query(
                $registry,
            )->get(
                new ActionQueueFiltersData(
                    severities: [
                        ActionQueueSeverity::Critical->value,
                    ],
                ),
            );

        $this->assertCount(
            1,
            $result->items,
        );

        $this->assertSame(
            'CRITICAL',
            $result
                ->items[0]
                ->reference,
        );
    }

    public function test_requires_action_filter_is_applied(): void
    {
        $registry =
            new ActionQueueProviderRegistry;

        $registry->register(
            $this->provider(
                source: ActionQueueSource::CodOperations,

                items: [
                    $this->item(
                        source: ActionQueueSource::CodOperations,

                        entityId: 1,

                        reference: 'ACTION',

                        title: 'Needs action',

                        priority: 60,

                        severity: ActionQueueSeverity::High,

                        requiresAction: true,
                    ),

                    $this->item(
                        source: ActionQueueSource::CodOperations,

                        entityId: 2,

                        reference: 'NO-ACTION',

                        title: 'No action',

                        priority: 10,

                        severity: ActionQueueSeverity::Normal,

                        requiresAction: false,
                    ),
                ],
            ),
        );

        $result =
            $this->query(
                $registry,
            )->get(
                new ActionQueueFiltersData(
                    requiresAction: true,
                ),
            );

        $this->assertCount(
            1,
            $result->items,
        );

        $this->assertSame(
            'ACTION',
            $result
                ->items[0]
                ->reference,
        );
    }

    public function test_search_matches_reference_title_and_reason(): void
    {
        $registry =
            new ActionQueueProviderRegistry;

        $registry->register(
            $this->provider(
                source: ActionQueueSource::InventoryOperations,

                items: [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 1,

                        reference: 'SKU-TARGET',

                        title: 'Inventory item',

                        priority: 50,

                        severity: ActionQueueSeverity::High,

                        reason: 'Negative stock detected',
                    ),

                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 2,

                        reference: 'SKU-OTHER',

                        title: 'Healthy inventory',

                        priority: 10,

                        severity: ActionQueueSeverity::Normal,
                    ),
                ],
            ),
        );

        $result =
            $this->query(
                $registry,
            )->get(
                new ActionQueueFiltersData(
                    search: 'negative stock',
                ),
            );

        $this->assertCount(
            1,
            $result->items,
        );

        $this->assertSame(
            'SKU-TARGET',
            $result
                ->items[0]
                ->reference,
        );
    }

    public function test_queue_paginates_merged_items(): void
    {
        $registry =
            new ActionQueueProviderRegistry;

        $items = [];

        for (
            $index = 1;
            $index <= 12;
            $index++
        ) {
            $items[] =
                $this->item(
                    source: ActionQueueSource::OperationalOrders,

                    entityId: $index,

                    reference: 'ORD-'.$index,

                    title: 'Order '.$index,

                    priority: 20,

                    severity: ActionQueueSeverity::Warning,
                );
        }

        $registry->register(
            $this->provider(
                source: ActionQueueSource::OperationalOrders,

                items: $items,
            ),
        );

        $result =
            $this->query(
                $registry,
            )->get(
                filters: new ActionQueueFiltersData(
                    perPage: 10,
                ),

                page: 2,
            );

        $this->assertSame(
            12,
            $result
                ->pagination
                ->total,
        );

        $this->assertSame(
            2,
            $result
                ->pagination
                ->currentPage,
        );

        $this->assertSame(
            2,
            $result
                ->pagination
                ->lastPage,
        );

        $this->assertCount(
            2,
            $result->items,
        );
    }

    public function test_filters_normalize_invalid_values(): void
    {
        $filters =
            new ActionQueueFiltersData(
                sources: [
                    ActionQueueSource::CodOperations->value,
                    'invalid-source',
                ],

                severities: [
                    ActionQueueSeverity::High->value,
                    'invalid-severity',
                ],

                perPage: 999,
            );

        $this->assertSame(
            [
                ActionQueueSource::CodOperations->value,
            ],
            $filters
                ->sourceValues(),
        );

        $this->assertSame(
            [
                ActionQueueSeverity::High->value,
            ],
            $filters
                ->severityValues(),
        );

        $this->assertSame(
            25,
            $filters
                ->normalizedPerPage(),
        );
    }

    private function query(
        ActionQueueProviderRegistry $registry,
    ): GetAdminActionQueueQuery {
        return new GetAdminActionQueueQuery(
            $registry,
        );
    }

    /**
     * @param  array<int, ActionQueueItemData>  $items
     */
    private function provider(
        ActionQueueSource $source,
        array $items,
    ): ActionQueueProvider {
        return new class($source, $items) implements ActionQueueProvider
        {
            /**
             * @param  array<int, ActionQueueItemData>  $items
             */
            public function __construct(
                private readonly ActionQueueSource $queueSource,
                private readonly array $queueItems,
            ) {}

            public function source(): ActionQueueSource
            {
                return $this
                    ->queueSource;
            }

            /**
             * @return array<int, ActionQueueItemData>
             */
            public function items(
                ActionQueueFiltersData $filters,
            ): array {
                return $this
                    ->queueItems;
            }
        };
    }

    private function item(
        ActionQueueSource $source,
        int $entityId,
        string $reference,
        string $title,
        int $priority,
        ActionQueueSeverity $severity,
        bool $requiresAction = true,
        string $reason = 'Requires operational review',
    ): ActionQueueItemData {
        return new ActionQueueItemData(
            source: $source,

            entityType: 'test',

            entityId: $entityId,

            reference: $reference,

            title: $title,

            priority: $priority,

            severity: $severity,

            reason: $reason,

            requiresAction: $requiresAction,

            ageHours: 24,

            ageDays: 1,

            actionUrl: '/admin/test/'.
            $entityId,

            actionLabel: 'Review',
        );
    }
}
