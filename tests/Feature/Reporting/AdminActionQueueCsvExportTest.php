<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Reporting\Data\ActionQueueFiltersData;
use App\Domain\Reporting\Data\ActionQueueItemData;
use App\Domain\Reporting\Enums\ActionQueueSeverity;
use App\Domain\Reporting\Enums\ActionQueueSource;
use App\Domain\Reporting\Operations\ActionQueueProvider;
use App\Domain\Reporting\Operations\ActionQueueProviderRegistry;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminActionQueueCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_export_action_queue_csv(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.operations.action-queue.csv',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_export_action_queue_csv(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->bindQueue(
            [
                ActionQueueSource::InventoryOperations->value => [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 10,

                        reference: 'SKU-010',

                        title: 'Critical Product',

                        priority: 550,

                        severity: ActionQueueSeverity::Critical,

                        reason: 'Negative inventory requires immediate stock reconciliation.',

                        actionUrl: '/admin/products/10/edit',

                        actionLabel: 'Review Product',
                    ),
                ],
            ],
        );

        $response =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.action-queue.csv',
                    ),
                );

        $response
            ->assertOk()
            ->assertHeader(
                'content-type',
                'text/csv; charset=UTF-8',
            );

        $csv =
            $response
                ->streamedContent();

        $this->assertStringContainsString(
            'Source',
            $csv,
        );

        $this->assertStringContainsString(
            'SKU-010',
            $csv,
        );

        $this->assertStringContainsString(
            'Critical Product',
            $csv,
        );

        $this->assertStringContainsString(
            'critical',
            $csv,
        );

        $this->assertStringContainsString(
            '550',
            $csv,
        );

        $this->assertStringContainsString(
            'Review Product',
            $csv,
        );
    }

    public function test_csv_respects_source_filter(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->bindQueue(
            [
                ActionQueueSource::CodOperations->value => [
                    $this->item(
                        source: ActionQueueSource::CodOperations,

                        entityId: 1,

                        reference: 'COD-001',

                        title: 'COD Follow-up',
                    ),
                ],

                ActionQueueSource::InventoryOperations->value => [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 2,

                        reference: 'INV-002',

                        title: 'Inventory Follow-up',
                    ),
                ],
            ],
        );

        $csv =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.action-queue.csv',
                        [
                            'source' => [
                                ActionQueueSource::CodOperations->value,
                            ],
                        ],
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'COD-001',
            $csv,
        );

        $this->assertStringNotContainsString(
            'INV-002',
            $csv,
        );
    }

    public function test_csv_respects_severity_filter(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->bindQueue(
            [
                ActionQueueSource::OperationalOrders->value => [
                    $this->item(
                        source: ActionQueueSource::OperationalOrders,

                        entityId: 1,

                        reference: 'WARNING-001',

                        title: 'Warning Item',

                        priority: 220,

                        severity: ActionQueueSeverity::Warning,
                    ),

                    $this->item(
                        source: ActionQueueSource::OperationalOrders,

                        entityId: 2,

                        reference: 'CRITICAL-002',

                        title: 'Critical Item',

                        priority: 450,

                        severity: ActionQueueSeverity::Critical,
                    ),
                ],
            ],
        );

        $csv =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.action-queue.csv',
                        [
                            'severity' => [
                                ActionQueueSeverity::Critical->value,
                            ],
                        ],
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'CRITICAL-002',
            $csv,
        );

        $this->assertStringNotContainsString(
            'WARNING-001',
            $csv,
        );
    }

    public function test_csv_respects_search_filter(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->bindQueue(
            [
                ActionQueueSource::InventoryOperations->value => [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 1,

                        reference: 'SKU-TARGET',

                        title: 'Target Product',

                        reason: 'Negative inventory detected.',
                    ),

                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 2,

                        reference: 'SKU-OTHER',

                        title: 'Other Product',
                    ),
                ],
            ],
        );

        $csv =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.action-queue.csv',
                        [
                            'search' => 'TARGET',
                        ],
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'SKU-TARGET',
            $csv,
        );

        $this->assertStringNotContainsString(
            'SKU-OTHER',
            $csv,
        );
    }

    public function test_csv_respects_requires_action_filter(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->bindQueue(
            [
                ActionQueueSource::OperationalOrders->value => [
                    $this->item(
                        source: ActionQueueSource::OperationalOrders,

                        entityId: 1,

                        reference: 'ACTION-001',

                        title: 'Needs Action',

                        requiresAction: true,
                    ),

                    $this->item(
                        source: ActionQueueSource::OperationalOrders,

                        entityId: 2,

                        reference: 'NO-ACTION-002',

                        title: 'No Action',

                        requiresAction: false,

                        priority: 0,

                        severity: ActionQueueSeverity::Normal,
                    ),
                ],
            ],
        );

        $csv =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.action-queue.csv',
                        [
                            'requires_action' => '1',
                        ],
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'ACTION-001',
            $csv,
        );

        $this->assertStringNotContainsString(
            'NO-ACTION-002',
            $csv,
        );
    }

    public function test_csv_preserves_cross_domain_priority_order(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->bindQueue(
            [
                ActionQueueSource::OperationalOrders->value => [
                    $this->item(
                        source: ActionQueueSource::OperationalOrders,

                        entityId: 1,

                        reference: 'LOWER-001',

                        title: 'Lower Priority',

                        priority: 220,

                        severity: ActionQueueSeverity::Warning,
                    ),
                ],

                ActionQueueSource::InventoryOperations->value => [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 2,

                        reference: 'HIGHER-002',

                        title: 'Higher Priority',

                        priority: 550,

                        severity: ActionQueueSeverity::Critical,
                    ),
                ],
            ],
        );

        $csv =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.action-queue.csv',
                    ),
                )
                ->streamedContent();

        $highPosition =
            strpos(
                $csv,
                'HIGHER-002',
            );

        $lowPosition =
            strpos(
                $csv,
                'LOWER-001',
            );

        $this->assertNotFalse(
            $highPosition,
        );

        $this->assertNotFalse(
            $lowPosition,
        );

        $this->assertLessThan(
            $lowPosition,
            $highPosition,
        );
    }

    public function test_csv_protects_against_spreadsheet_formula_injection(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->bindQueue(
            [
                ActionQueueSource::InventoryOperations->value => [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 1,

                        reference: '=FORMULA',

                        title: '+Dangerous Title',

                        reason: '@Dangerous Reason',

                        actionUrl: '-dangerous-url',
                    ),
                ],
            ],
        );

        $csv =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.action-queue.csv',
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            "'=FORMULA",
            $csv,
        );

        $this->assertStringContainsString(
            "'+Dangerous Title",
            $csv,
        );

        $this->assertStringContainsString(
            "'@Dangerous Reason",
            $csv,
        );

        $this->assertStringContainsString(
            "'-dangerous-url",
            $csv,
        );
    }

    /**
     * @param  array<string, array<int, ActionQueueItemData>>  $itemsBySource
     */
    private function bindQueue(
        array $itemsBySource,
    ): void {
        $registry =
            new ActionQueueProviderRegistry;

        foreach (
            $itemsBySource as $sourceValue => $items
        ) {
            $registry->register(
                $this->provider(
                    source: ActionQueueSource::from(
                        $sourceValue,
                    ),

                    items: $items,
                ),
            );
        }

        $this->app->instance(
            ActionQueueProviderRegistry::class,
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
        int $priority = 320,
        ActionQueueSeverity $severity = ActionQueueSeverity::High,
        bool $requiresAction = true,
        string $reason = 'Requires operational review.',
        string $entityType = 'order',
        string $actionUrl = '/admin/test',
        string $actionLabel = 'Review',
        int $ageHours = 48,
        int $ageDays = 2,
    ): ActionQueueItemData {
        return new ActionQueueItemData(
            source: $source,

            entityType: $entityType,

            entityId: $entityId,

            reference: $reference,

            title: $title,

            priority: $priority,

            severity: $severity,

            reason: $reason,

            requiresAction: $requiresAction,

            ageHours: $ageHours,

            ageDays: $ageDays,

            actionUrl: $actionUrl,

            actionLabel: $actionLabel,
        );
    }
}
