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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminActionQueueFinalRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_action_queue_or_export(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.operations.action-queue',
                ),
            )
            ->assertRedirect();

        $this
            ->get(
                route(
                    'admin.reports.operations.action-queue.csv',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_access_action_queue_and_export(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->bindQueue(
            [],
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.action-queue',
                ),
            )
            ->assertOk();

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
            ->assertOk()
            ->assertHeader(
                'content-type',
                'text/csv; charset=UTF-8',
            );
    }

    public function test_queue_reconciles_all_three_sources(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->bindQueue(
            [
                ActionQueueSource::OperationalOrders->value => [
                    $this->item(
                        source: ActionQueueSource::OperationalOrders,

                        entityId: 101,

                        reference: 'ORD-101',

                        title: 'Operational Order',

                        priority: 330,

                        severity: ActionQueueSeverity::High,
                    ),
                ],

                ActionQueueSource::CodOperations->value => [
                    $this->item(
                        source: ActionQueueSource::CodOperations,

                        entityId: 202,

                        reference: 'COD-202',

                        title: 'COD Collection',

                        priority: 470,

                        severity: ActionQueueSeverity::Critical,
                    ),
                ],

                ActionQueueSource::InventoryOperations->value => [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 303,

                        reference: 'SKU-303',

                        title: 'Negative Inventory',

                        priority: 550,

                        severity: ActionQueueSeverity::Critical,

                        entityType: 'product',

                        actionUrl: '/admin/products/303/edit',

                        actionLabel: 'Review Product',
                    ),
                ],
            ],
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.action-queue',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'pagination.total',
                        3,
                    )
                    ->has(
                        'items',
                        3,
                    )
                    ->where(
                        'items.0.source',
                        ActionQueueSource::InventoryOperations->value,
                    )
                    ->where(
                        'items.1.source',
                        ActionQueueSource::CodOperations->value,
                    )
                    ->where(
                        'items.2.source',
                        ActionQueueSource::OperationalOrders->value,
                    ),
            );
    }

    public function test_priority_order_is_deterministic_across_domains(): void
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

                        reference: 'ORDER-HIGH',

                        title: 'Order High',

                        priority: 330,

                        severity: ActionQueueSeverity::High,
                    ),
                ],

                ActionQueueSource::CodOperations->value => [
                    $this->item(
                        source: ActionQueueSource::CodOperations,

                        entityId: 2,

                        reference: 'COD-CRITICAL',

                        title: 'COD Critical',

                        priority: 470,

                        severity: ActionQueueSeverity::Critical,
                    ),
                ],

                ActionQueueSource::InventoryOperations->value => [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 3,

                        reference: 'INV-CRITICAL',

                        title: 'Inventory Critical',

                        priority: 550,

                        severity: ActionQueueSeverity::Critical,
                    ),
                ],
            ],
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.action-queue',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'items.0.reference',
                        'INV-CRITICAL',
                    )
                    ->where(
                        'items.1.reference',
                        'COD-CRITICAL',
                    )
                    ->where(
                        'items.2.reference',
                        'ORDER-HIGH',
                    ),
            );
    }

    public function test_browser_and_csv_reconcile_source_filter(): void
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

                        reference: 'COD-MATCH',

                        title: 'COD Match',
                    ),
                ],

                ActionQueueSource::InventoryOperations->value => [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 2,

                        reference: 'INV-OTHER',

                        title: 'Inventory Other',
                    ),
                ],
            ],
        );

        $filters = [
            'source' => [
                ActionQueueSource::CodOperations->value,
            ],
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.action-queue',
                    $filters,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'pagination.total',
                        1,
                    )
                    ->where(
                        'items.0.reference',
                        'COD-MATCH',
                    ),
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
                        $filters,
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'COD-MATCH',
            $csv,
        );

        $this->assertStringNotContainsString(
            'INV-OTHER',
            $csv,
        );
    }

    public function test_browser_and_csv_reconcile_severity_filter(): void
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

                        reference: 'CRITICAL-MATCH',

                        title: 'Critical Match',

                        priority: 450,

                        severity: ActionQueueSeverity::Critical,
                    ),

                    $this->item(
                        source: ActionQueueSource::OperationalOrders,

                        entityId: 2,

                        reference: 'WARNING-OTHER',

                        title: 'Warning Other',

                        priority: 220,

                        severity: ActionQueueSeverity::Warning,
                    ),
                ],
            ],
        );

        $filters = [
            'severity' => [
                ActionQueueSeverity::Critical->value,
            ],
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.action-queue',
                    $filters,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'pagination.total',
                        1,
                    )
                    ->where(
                        'items.0.reference',
                        'CRITICAL-MATCH',
                    ),
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
                        $filters,
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'CRITICAL-MATCH',
            $csv,
        );

        $this->assertStringNotContainsString(
            'WARNING-OTHER',
            $csv,
        );
    }

    public function test_browser_and_csv_reconcile_search_filter(): void
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

                        reference: 'SKU-TARGET-FINAL',

                        title: 'Target Product',

                        reason: 'Negative inventory requires reconciliation.',
                    ),

                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 2,

                        reference: 'SKU-OTHER-FINAL',

                        title: 'Other Product',
                    ),
                ],
            ],
        );

        $filters = [
            'search' => 'TARGET-FINAL',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.action-queue',
                    $filters,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'pagination.total',
                        1,
                    )
                    ->where(
                        'items.0.reference',
                        'SKU-TARGET-FINAL',
                    ),
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
                        $filters,
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'SKU-TARGET-FINAL',
            $csv,
        );

        $this->assertStringNotContainsString(
            'SKU-OTHER-FINAL',
            $csv,
        );
    }

    public function test_browser_and_csv_reconcile_requires_action_filter(): void
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

                        reference: 'ACTION-YES',

                        title: 'Needs Action',

                        requiresAction: true,
                    ),

                    $this->item(
                        source: ActionQueueSource::OperationalOrders,

                        entityId: 2,

                        reference: 'ACTION-NO',

                        title: 'No Action',

                        priority: 0,

                        severity: ActionQueueSeverity::Normal,

                        requiresAction: false,
                    ),
                ],
            ],
        );

        $filters = [
            'requires_action' => '1',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.action-queue',
                    $filters,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'pagination.total',
                        1,
                    )
                    ->where(
                        'items.0.reference',
                        'ACTION-YES',
                    ),
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
                        $filters,
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'ACTION-YES',
            $csv,
        );

        $this->assertStringNotContainsString(
            'ACTION-NO',
            $csv,
        );
    }

    public function test_combined_filters_reconcile_between_browser_and_csv(): void
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

                        reference: 'COD-TARGET-FINAL',

                        title: 'Target COD',

                        priority: 470,

                        severity: ActionQueueSeverity::Critical,

                        requiresAction: true,
                    ),

                    $this->item(
                        source: ActionQueueSource::CodOperations,

                        entityId: 2,

                        reference: 'COD-WARNING',

                        title: 'Target Warning',

                        priority: 220,

                        severity: ActionQueueSeverity::Warning,
                    ),
                ],

                ActionQueueSource::InventoryOperations->value => [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 3,

                        reference: 'INV-TARGET-FINAL',

                        title: 'Target Inventory',

                        priority: 550,

                        severity: ActionQueueSeverity::Critical,
                    ),
                ],
            ],
        );

        $filters = [
            'source' => [
                ActionQueueSource::CodOperations->value,
            ],

            'severity' => [
                ActionQueueSeverity::Critical->value,
            ],

            'requires_action' => '1',

            'search' => 'TARGET-FINAL',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.action-queue',
                    $filters,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'pagination.total',
                        1,
                    )
                    ->where(
                        'items.0.reference',
                        'COD-TARGET-FINAL',
                    ),
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
                        $filters,
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'COD-TARGET-FINAL',
            $csv,
        );

        $this->assertStringNotContainsString(
            'INV-TARGET-FINAL',
            $csv,
        );

        $this->assertStringNotContainsString(
            'COD-WARNING',
            $csv,
        );
    }

    public function test_pagination_keeps_cross_domain_order(): void
    {
        $admin =
            Admin::factory()
                ->create();

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

                    reference: 'QUEUE-'.
                    str_pad(
                        (string) $index,
                        2,
                        '0',
                        STR_PAD_LEFT,
                    ),

                    title: 'Queue Item '.
                    $index,

                    priority: 500 -
                    $index,

                    severity: ActionQueueSeverity::Critical,
                );
        }

        $this->bindQueue(
            [
                ActionQueueSource::OperationalOrders->value => $items,
            ],
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.action-queue',
                    [
                        'per_page' => 10,

                        'page' => 2,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'pagination.current_page',
                        2,
                    )
                    ->where(
                        'pagination.last_page',
                        2,
                    )
                    ->where(
                        'pagination.total',
                        12,
                    )
                    ->has(
                        'items',
                        2,
                    )
                    ->where(
                        'items.0.reference',
                        'QUEUE-11',
                    )
                    ->where(
                        'items.1.reference',
                        'QUEUE-12',
                    ),
            );
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->bindQueue(
            [],
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.action-queue',
                    [
                        'source' => [
                            'invalid_source',
                        ],

                        'severity' => [
                            'extreme',
                        ],

                        'requires_action' => 'maybe',

                        'per_page' => 999,

                        'page' => 0,
                    ],
                ),
            )
            ->assertSessionHasErrors([
                'source.0',
                'severity.0',
                'requires_action',
                'per_page',
                'page',
            ]);
    }

    public function test_invalid_filters_are_also_rejected_by_csv_export(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->bindQueue(
            [],
        );

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
                            'invalid_source',
                        ],
                    ],
                ),
            )
            ->assertSessionHasErrors(
                'source.0',
            );
    }

    public function test_action_queue_item_contract_contains_required_operational_fields(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->bindQueue(
            [
                ActionQueueSource::InventoryOperations->value => [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 55,

                        reference: 'SKU-55',

                        title: 'Product 55',

                        priority: 550,

                        severity: ActionQueueSeverity::Critical,

                        reason: 'Negative stock requires reconciliation.',

                        entityType: 'product',

                        actionUrl: '/admin/products/55/edit',

                        actionLabel: 'Review Product',

                        ageHours: 0,

                        ageDays: 0,
                    ),
                ],
            ],
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.action-queue',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'items.0.source',
                        ActionQueueSource::InventoryOperations->value,
                    )
                    ->where(
                        'items.0.entity_type',
                        'product',
                    )
                    ->where(
                        'items.0.entity_id',
                        55,
                    )
                    ->where(
                        'items.0.reference',
                        'SKU-55',
                    )
                    ->where(
                        'items.0.priority',
                        550,
                    )
                    ->where(
                        'items.0.severity',
                        'critical',
                    )
                    ->where(
                        'items.0.requires_action',
                        true,
                    )
                    ->where(
                        'items.0.action_url',
                        '/admin/products/55/edit',
                    )
                    ->where(
                        'items.0.action_label',
                        'Review Product',
                    ),
            );
    }

    public function test_csv_contains_authoritative_queue_metadata(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->bindQueue(
            [
                ActionQueueSource::InventoryOperations->value => [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 99,

                        reference: 'SKU-FINAL-99',

                        title: 'Final Inventory Product',

                        priority: 550,

                        severity: ActionQueueSeverity::Critical,

                        reason: 'Negative inventory requires immediate stock reconciliation.',

                        entityType: 'product',

                        actionUrl: '/admin/products/99/edit',

                        actionLabel: 'Review Product',
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
            'Inventory Operations',
            $csv,
        );

        $this->assertStringContainsString(
            'SKU-FINAL-99',
            $csv,
        );

        $this->assertStringContainsString(
            '550',
            $csv,
        );

        $this->assertStringContainsString(
            'critical',
            $csv,
        );

        $this->assertStringContainsString(
            'Negative inventory requires immediate stock reconciliation.',
            $csv,
        );

        $this->assertStringContainsString(
            'Review Product',
            $csv,
        );
    }

    public function test_csv_formula_injection_protection_remains_enabled(): void
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

                        title: '+Title',

                        reason: '@Reason',

                        actionUrl: '-url',
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
            "'+Title",
            $csv,
        );

        $this->assertStringContainsString(
            "'@Reason",
            $csv,
        );

        $this->assertStringContainsString(
            "'-url",
            $csv,
        );
    }

    public function test_frontend_contains_final_action_queue_workflow_contracts(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/ActionQueue/Index.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'Quick workflows',
            $source,
        );

        $this->assertStringContainsString(
            'Needs Action',
            $source,
        );

        $this->assertStringContainsString(
            'Critical',
            $source,
        );

        $this->assertStringContainsString(
            'High Priority',
            $source,
        );

        $this->assertStringContainsString(
            'COD Collection',
            $source,
        );

        $this->assertStringContainsString(
            'Inventory',
            $source,
        );

        $this->assertStringContainsString(
            'Active filters',
            $source,
        );

        $this->assertStringContainsString(
            'Clear search',
            $source,
        );

        $this->assertStringContainsString(
            'Clear all',
            $source,
        );

        $this->assertStringContainsString(
            'MobileQueueCard',
            $source,
        );

        $this->assertStringContainsString(
            'Export CSV',
            $source,
        );

        $this->assertStringContainsString(
            'admin.reports.operations.action-queue.csv',
            $source,
        );

        $this->assertStringContainsString(
            'item.action_url',
            $source,
        );

        $this->assertStringContainsString(
            'item.action_label',
            $source,
        );

        $this->assertStringContainsString(
            'preserveState',
            $source,
        );

        $this->assertStringContainsString(
            'preserveScroll',
            $source,
        );
    }

    public function test_action_queue_navigation_contract_is_present(): void
    {
        $sidebar =
            file_get_contents(
                resource_path(
                    'js/Components/Admin/AdminSidebar.tsx',
                ),
            );

        $this->assertIsString(
            $sidebar,
        );

        $this->assertStringContainsString(
            'Action Queue',
            $sidebar,
        );

        $this->assertStringContainsString(
            'admin.reports.operations.action-queue',
            $sidebar,
        );

        $this->assertStringContainsString(
            '/admin/reports/operations/action-queue',
            $sidebar,
        );
    }

    public function test_action_queue_routes_are_registered(): void
    {
        $router =
            app(
                'router',
            );

        $this->assertTrue(
            $router->has(
                'admin.reports.operations.action-queue',
            ),
        );

        $this->assertTrue(
            $router->has(
                'admin.reports.operations.action-queue.csv',
            ),
        );
    }

    public function test_empty_queue_remains_valid(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->bindQueue(
            [],
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.action-queue',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'pagination.total',
                        0,
                    )
                    ->where(
                        'pagination.current_page',
                        1,
                    )
                    ->where(
                        'pagination.last_page',
                        1,
                    )
                    ->has(
                        'items',
                        0,
                    ),
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
