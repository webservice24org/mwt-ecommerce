<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Reporting\Enums\OperationalExportType;
use App\Domain\Reporting\Operations\OperationalExportRegistry;
use Tests\TestCase;

final class OperationalOrdersExportInfrastructureRegressionTest extends TestCase
{
    public function test_operational_orders_export_is_registered(): void
    {
        $registry =
            app(
                OperationalExportRegistry::class,
            );

        $this->assertTrue(
            $registry->has(
                OperationalExportType::OperationalOrders,
            ),
        );
    }

    public function test_operational_orders_export_route_matches_registry(): void
    {
        $definition =
            app(
                OperationalExportRegistry::class,
            )->get(
                OperationalExportType::OperationalOrders,
            );

        $this->assertTrue(
            app(
                'router',
            )->has(
                $definition->routeName,
            ),
        );
    }

    public function test_operational_orders_export_filename_contract_is_stable(): void
    {
        $definition =
            app(
                OperationalExportRegistry::class,
            )->get(
                OperationalExportType::OperationalOrders,
            );

        $this->assertSame(
            'operational-orders-2026-11-16-130500.csv',
            $definition->filename(
                '2026-11-16-130500',
            ),
        );
    }

    public function test_operational_orders_headers_are_not_empty_or_duplicated(): void
    {
        $definition =
            app(
                OperationalExportRegistry::class,
            )->get(
                OperationalExportType::OperationalOrders,
            );

        $this->assertNotEmpty(
            $definition->headers,
        );

        $this->assertSame(
            count(
                $definition->headers,
            ),
            count(
                array_unique(
                    $definition->headers,
                ),
            ),
        );
    }
}
