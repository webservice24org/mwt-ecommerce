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

final class AdminActionQueueReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_action_queue_report(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.operations.action-queue',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_access_action_queue_report(): void
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
    }

    public function test_report_uses_expected_inertia_component_and_props(): void
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
                    ->component(
                        'Admin/Reports/ActionQueue/Index',
                    )
                    ->has(
                        'filters',
                    )
                    ->has(
                        'items',
                    )
                    ->has(
                        'pagination',
                    )
                    ->has(
                        'options.sources',
                    )
                    ->has(
                        'options.severities',
                    ),
            );
    }

    public function test_report_exposes_all_queue_source_options(): void
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
                    ->has(
                        'options.sources',
                        count(
                            ActionQueueSource::cases(),
                        ),
                    )
                    ->where(
                        'options.sources.0.value',
                        ActionQueueSource::OperationalOrders->value,
                    )
                    ->where(
                        'options.sources.1.value',
                        ActionQueueSource::CodOperations->value,
                    )
                    ->where(
                        'options.sources.2.value',
                        ActionQueueSource::InventoryOperations->value,
                    ),
            );
    }

    public function test_report_exposes_all_queue_severity_options(): void
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
                    ->has(
                        'options.severities',
                        count(
                            ActionQueueSeverity::cases(),
                        ),
                    ),
            );
    }

    public function test_items_from_all_three_sources_are_exposed(): void
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

                        title: 'Operational Order 101',

                        priority: 330,

                        severity: ActionQueueSeverity::High,

                        actionUrl: '/admin/orders/101',
                    ),
                ],

                ActionQueueSource::CodOperations->value => [
                    $this->item(
                        source: ActionQueueSource::CodOperations,

                        entityId: 202,

                        reference: 'ORD-202',

                        title: 'COD Order 202',

                        priority: 470,

                        severity: ActionQueueSeverity::Critical,

                        actionUrl: '/admin/orders/202',
                    ),
                ],

                ActionQueueSource::InventoryOperations->value => [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 303,

                        reference: 'SKU-303',

                        title: 'Inventory Product 303',

                        priority: 550,

                        severity: ActionQueueSeverity::Critical,

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

    public function test_queue_is_sorted_by_cross_domain_priority(): void
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

                        reference: 'LOWER',

                        title: 'Lower Priority',

                        priority: 230,

                        severity: ActionQueueSeverity::Warning,
                    ),

                    $this->item(
                        source: ActionQueueSource::OperationalOrders,

                        entityId: 2,

                        reference: 'HIGHER',

                        title: 'Higher Priority',

                        priority: 490,

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
                        'HIGHER',
                    )
                    ->where(
                        'items.1.reference',
                        'LOWER',
                    ),
            );
    }

    public function test_source_filter_is_preserved_and_applied(): void
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

                        reference: 'ORD-001',

                        title: 'Operational Order',
                    ),
                ],

                ActionQueueSource::CodOperations->value => [
                    $this->item(
                        source: ActionQueueSource::CodOperations,

                        entityId: 2,

                        reference: 'COD-002',

                        title: 'COD Operation',
                    ),
                ],

                ActionQueueSource::InventoryOperations->value => [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 3,

                        reference: 'SKU-003',

                        title: 'Inventory Operation',
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
                    [
                        'source' => [
                            ActionQueueSource::CodOperations->value,
                        ],
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'filters.sources',
                        [
                            ActionQueueSource::CodOperations->value,
                        ],
                    )
                    ->where(
                        'pagination.total',
                        1,
                    )
                    ->has(
                        'items',
                        1,
                    )
                    ->where(
                        'items.0.source',
                        ActionQueueSource::CodOperations->value,
                    )
                    ->where(
                        'items.0.reference',
                        'COD-002',
                    ),
            );
    }

    public function test_multiple_source_filters_use_or_semantics(): void
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

                        reference: 'ORD-001',

                        title: 'Order',
                    ),
                ],

                ActionQueueSource::CodOperations->value => [
                    $this->item(
                        source: ActionQueueSource::CodOperations,

                        entityId: 2,

                        reference: 'COD-002',

                        title: 'COD',
                    ),
                ],

                ActionQueueSource::InventoryOperations->value => [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 3,

                        reference: 'SKU-003',

                        title: 'Inventory',
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
                    [
                        'source' => [
                            ActionQueueSource::CodOperations->value,
                            ActionQueueSource::InventoryOperations->value,
                        ],
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'pagination.total',
                        2,
                    )
                    ->has(
                        'items',
                        2,
                    ),
            );
    }

    public function test_severity_filter_is_preserved_and_applied(): void
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

                        priority: 430,

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
                    [
                        'severity' => [
                            ActionQueueSeverity::Critical->value,
                        ],
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'filters.severities',
                        [
                            ActionQueueSeverity::Critical->value,
                        ],
                    )
                    ->where(
                        'pagination.total',
                        1,
                    )
                    ->has(
                        'items',
                        1,
                    )
                    ->where(
                        'items.0.reference',
                        'CRITICAL-002',
                    )
                    ->where(
                        'items.0.severity',
                        'critical',
                    ),
            );
    }

    public function test_requires_action_filter_is_preserved_and_applied(): void
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

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.action-queue',
                    [
                        'requires_action' => '1',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'filters.requires_action',
                        true,
                    )
                    ->where(
                        'pagination.total',
                        1,
                    )
                    ->has(
                        'items',
                        1,
                    )
                    ->where(
                        'items.0.reference',
                        'ACTION-001',
                    )
                    ->where(
                        'items.0.requires_action',
                        true,
                    ),
            );
    }

    public function test_requires_action_false_filter_is_supported(): void
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

                        reference: 'ACTION',

                        title: 'Needs Action',

                        requiresAction: true,
                    ),

                    $this->item(
                        source: ActionQueueSource::OperationalOrders,

                        entityId: 2,

                        reference: 'NO-ACTION',

                        title: 'No Action',

                        requiresAction: false,

                        priority: 0,

                        severity: ActionQueueSeverity::Normal,
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
                    [
                        'requires_action' => '0',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'filters.requires_action',
                        false,
                    )
                    ->where(
                        'pagination.total',
                        1,
                    )
                    ->where(
                        'items.0.reference',
                        'NO-ACTION',
                    )
                    ->where(
                        'items.0.requires_action',
                        false,
                    ),
            );
    }

    public function test_search_filter_matches_reference(): void
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

                        reference: 'SKU-TARGET-001',

                        title: 'Target Product',
                    ),

                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 2,

                        reference: 'SKU-OTHER-002',

                        title: 'Other Product',
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
                    [
                        'search' => 'SKU-TARGET-001',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'filters.search',
                        'SKU-TARGET-001',
                    )
                    ->where(
                        'pagination.total',
                        1,
                    )
                    ->where(
                        'items.0.reference',
                        'SKU-TARGET-001',
                    ),
            );
    }

    public function test_search_filter_matches_title(): void
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

                        reference: 'ORD-001',

                        title: 'Rahim COD Collection',
                    ),

                    $this->item(
                        source: ActionQueueSource::CodOperations,

                        entityId: 2,

                        reference: 'ORD-002',

                        title: 'Karim COD Collection',
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
                    [
                        'search' => 'Rahim',
                    ],
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
                        'ORD-001',
                    ),
            );
    }

    public function test_search_filter_matches_reason(): void
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

                        reference: 'SKU-NEG',

                        title: 'Negative Product',

                        reason: 'Negative inventory requires immediate stock reconciliation.',
                    ),

                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 2,

                        reference: 'SKU-LOW',

                        title: 'Low Product',

                        reason: 'Tracked product is below the low-stock threshold.',
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
                    [
                        'search' => 'stock reconciliation',
                    ],
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
                        'SKU-NEG',
                    ),
            );
    }

    public function test_multiple_filters_are_combined(): void
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

                        reference: 'COD-TARGET',

                        title: 'Target COD',

                        priority: 450,

                        severity: ActionQueueSeverity::Critical,
                    ),

                    $this->item(
                        source: ActionQueueSource::CodOperations,

                        entityId: 2,

                        reference: 'COD-WARNING',

                        title: 'Warning COD',

                        priority: 230,

                        severity: ActionQueueSeverity::Warning,
                    ),
                ],

                ActionQueueSource::InventoryOperations->value => [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityId: 3,

                        reference: 'INV-TARGET',

                        title: 'Target Inventory',

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
                    [
                        'source' => [
                            ActionQueueSource::CodOperations->value,
                        ],

                        'severity' => [
                            ActionQueueSeverity::Critical->value,
                        ],

                        'search' => 'TARGET',

                        'requires_action' => '1',
                    ],
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
                    ->has(
                        'items',
                        1,
                    )
                    ->where(
                        'items.0.reference',
                        'COD-TARGET',
                    ),
            );
    }

    public function test_per_page_filter_is_preserved(): void
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
                        'per_page' => 50,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'filters.per_page',
                        50,
                    )
                    ->where(
                        'pagination.per_page',
                        50,
                    ),
            );
    }

    public function test_queue_paginates_merged_items(): void
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

                    reference: 'ORD-'.
                    str_pad(
                        (string) $index,
                        3,
                        '0',
                        STR_PAD_LEFT,
                    ),

                    title: 'Order '.
                    $index,

                    priority: 300 -
                    $index,

                    severity: ActionQueueSeverity::High,
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
                        'pagination.per_page',
                        10,
                    )
                    ->where(
                        'pagination.total',
                        12,
                    )
                    ->has(
                        'items',
                        2,
                    ),
            );
    }

    public function test_item_contract_exposes_action_url_and_label(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->bindQueue(
            [
                ActionQueueSource::OperationalOrders->value => [
                    $this->item(
                        source: ActionQueueSource::OperationalOrders,

                        entityId: 77,

                        reference: 'ORD-077',

                        title: 'Order 77',

                        actionUrl: '/admin/orders/77',

                        actionLabel: 'Review Order',
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
                        'items.0.action_url',
                        '/admin/orders/77',
                    )
                    ->where(
                        'items.0.action_label',
                        'Review Order',
                    )
                    ->where(
                        'items.0.entity_type',
                        'order',
                    )
                    ->where(
                        'items.0.entity_id',
                        77,
                    ),
            );
    }

    public function test_inventory_item_contract_can_point_to_product_review_page(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->bindQueue(
            [
                ActionQueueSource::InventoryOperations->value => [
                    $this->item(
                        source: ActionQueueSource::InventoryOperations,

                        entityType: 'product',

                        entityId: 88,

                        reference: 'SKU-088',

                        title: 'Product 88',

                        actionUrl: '/admin/products/88/edit',

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
                        'items.0.entity_type',
                        'product',
                    )
                    ->where(
                        'items.0.action_url',
                        '/admin/products/88/edit',
                    )
                    ->where(
                        'items.0.action_label',
                        'Review Product',
                    ),
            );
    }

    public function test_invalid_source_is_rejected(): void
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
                            'invalid-source',
                        ],
                    ],
                ),
            )
            ->assertSessionHasErrors(
                'source.0',
            );
    }

    public function test_invalid_severity_is_rejected(): void
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
                        'severity' => [
                            'extreme',
                        ],
                    ],
                ),
            )
            ->assertSessionHasErrors(
                'severity.0',
            );
    }

    public function test_invalid_requires_action_value_is_rejected(): void
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
                        'requires_action' => 'maybe',
                    ],
                ),
            )
            ->assertSessionHasErrors(
                'requires_action',
            );
    }

    public function test_invalid_per_page_value_is_rejected(): void
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
                        'per_page' => 999,
                    ],
                ),
            )
            ->assertSessionHasErrors(
                'per_page',
            );
    }

    public function test_invalid_page_value_is_rejected(): void
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
                        'page' => 0,
                    ],
                ),
            )
            ->assertSessionHasErrors(
                'page',
            );
    }

    public function test_empty_registry_returns_valid_empty_queue(): void
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
            $source =
                ActionQueueSource::from(
                    $sourceValue,
                );

            $registry->register(
                $this->provider(
                    source: $source,

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
        ?string $actionUrl = null,
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

            actionUrl: $actionUrl ??
            '/admin/test/'.
            $entityId,

            actionLabel: $actionLabel,
        );
    }
}
