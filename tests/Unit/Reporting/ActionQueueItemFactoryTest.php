<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use App\Domain\Reporting\Enums\ActionQueueSeverity;
use App\Domain\Reporting\Enums\ActionQueueSource;
use App\Domain\Reporting\Operations\ActionQueueItemFactory;
use App\Domain\Reporting\Operations\CrossDomainActionQueueClassifier;
use Tests\TestCase;

final class ActionQueueItemFactoryTest extends TestCase
{
    public function test_factory_normalizes_domain_classification_into_queue_item(): void
    {
        $factory =
            new ActionQueueItemFactory(
                new CrossDomainActionQueueClassifier,
            );

        $item =
            $factory->make(
                source: ActionQueueSource::InventoryOperations,

                entityType: 'product',

                entityId: 55,

                reference: 'SKU-55',

                title: 'Example Product',

                state: 'negative_stock',

                stateLabel: 'Negative stock',

                severity: 'critical',

                nativePriority: 90,

                requiresAction: true,

                ageHours: 30,

                ageDays: 1,

                actionUrl: '/admin/products/55/edit',

                actionLabel: 'Review Product',
            );

        $this->assertSame(
            ActionQueueSource::InventoryOperations,
            $item->source,
        );

        $this->assertSame(
            ActionQueueSeverity::Critical,
            $item->severity,
        );

        $this->assertSame(
            550,
            $item->priority,
        );

        $this->assertSame(
            'Negative inventory requires immediate stock reconciliation.',
            $item->reason,
        );

        $this->assertTrue(
            $item->requiresAction,
        );
    }

    public function test_factory_clamps_negative_age_values(): void
    {
        $factory =
            new ActionQueueItemFactory(
                new CrossDomainActionQueueClassifier,
            );

        $item =
            $factory->make(
                source: ActionQueueSource::OperationalOrders,

                entityType: 'order',

                entityId: 1,

                reference: 'ORD-001',

                title: 'Order',

                state: 'warning',

                stateLabel: 'Review order',

                severity: 'warning',

                nativePriority: 20,

                requiresAction: true,

                ageHours: -5,

                ageDays: -1,

                actionUrl: '/admin/orders/1',

                actionLabel: 'Review',
            );

        $this->assertSame(
            0,
            $item->ageHours,
        );

        $this->assertSame(
            0,
            $item->ageDays,
        );
    }
}
