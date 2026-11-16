<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use App\Domain\Reporting\Data\OperationalExportDefinitionData;
use App\Domain\Reporting\Enums\OperationalExportType;
use App\Domain\Reporting\Operations\OperationalExportRegistry;
use App\Domain\Reporting\Operations\OperationalExportRegistryFactory;
use InvalidArgumentException;
use Tests\TestCase;

final class OperationalExportRegistryTest extends TestCase
{
    public function test_factory_registers_all_operational_exports(): void
    {
        $registry =
            $this
                ->app
                ->make(
                    OperationalExportRegistryFactory::class,
                )
                ->make();

        $this->assertCount(
            4,
            $registry->all(),
        );

        foreach (
            OperationalExportType::cases() as $type
        ) {
            $this->assertTrue(
                $registry->has(
                    $type,
                ),
            );
        }
    }

    public function test_operational_orders_definition_is_registered(): void
    {
        $definition =
            $this->registry()
                ->get(
                    OperationalExportType::OperationalOrders,
                );

        $this->assertSame(
            'admin.reports.operations.orders.csv',
            $definition->routeName,
        );

        $this->assertSame(
            'Operational Orders',
            $definition->label(),
        );

        $this->assertNotEmpty(
            $definition->headers,
        );
    }

    public function test_cod_definition_is_registered(): void
    {
        $definition =
            $this->registry()
                ->get(
                    OperationalExportType::CodOperations,
                );

        $this->assertSame(
            'admin.reports.operations.cod.csv',
            $definition->routeName,
        );
    }

    public function test_inventory_definition_is_registered(): void
    {
        $definition =
            $this->registry()
                ->get(
                    OperationalExportType::InventoryOperations,
                );

        $this->assertSame(
            'admin.reports.operations.inventory.csv',
            $definition->routeName,
        );
    }

    public function test_action_queue_definition_is_registered(): void
    {
        $definition =
            $this->registry()
                ->get(
                    OperationalExportType::ActionQueue,
                );

        $this->assertSame(
            'admin.reports.operations.action-queue.csv',
            $definition->routeName,
        );
    }

    public function test_registry_throws_for_unregistered_export(): void
    {
        $registry =
            new OperationalExportRegistry;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $registry->get(
            OperationalExportType::ActionQueue,
        );
    }

    public function test_registry_replaces_duplicate_type_registration_deterministically(): void
    {
        $registry =
            new OperationalExportRegistry;

        $registry->register(
            new OperationalExportDefinitionData(
                type: OperationalExportType::ActionQueue,

                routeName: 'first.route',

                headers: [
                    'First',
                ],

                maxRows: 100,
            ),
        );

        $registry->register(
            new OperationalExportDefinitionData(
                type: OperationalExportType::ActionQueue,

                routeName: 'second.route',

                headers: [
                    'Second',
                ],

                maxRows: 200,
            ),
        );

        $definition =
            $registry->get(
                OperationalExportType::ActionQueue,
            );

        $this->assertSame(
            'second.route',
            $definition->routeName,
        );

        $this->assertSame(
            200,
            $definition->maxRows,
        );
    }

    public function test_definition_generates_stable_filename(): void
    {
        $definition =
            $this->registry()
                ->get(
                    OperationalExportType::InventoryOperations,
                );

        $this->assertSame(
            'inventory-operations-2026-11-16-130000.csv',
            $definition->filename(
                '2026-11-16-130000',
            ),
        );
    }

    private function registry(): OperationalExportRegistry
    {
        return $this
            ->app
            ->make(
                OperationalExportRegistryFactory::class,
            )
            ->make();
    }
}
