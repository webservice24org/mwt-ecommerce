<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Reporting\Enums\OperationalExportType;
use App\Domain\Reporting\Operations\OperationalExportRegistry;
use Tests\TestCase;

final class OperationalExportRegistryBindingTest extends TestCase
{
    public function test_operational_export_registry_is_resolvable_from_container(): void
    {
        $registry =
            $this->app->make(
                OperationalExportRegistry::class,
            );

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
}
