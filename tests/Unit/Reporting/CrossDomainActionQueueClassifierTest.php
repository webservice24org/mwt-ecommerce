<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use App\Domain\Reporting\Enums\ActionQueueSeverity;
use App\Domain\Reporting\Enums\ActionQueueSource;
use App\Domain\Reporting\Operations\CrossDomainActionQueueClassifier;
use Tests\TestCase;

final class CrossDomainActionQueueClassifierTest extends TestCase
{
    public function test_negative_inventory_receives_critical_queue_priority(): void
    {
        $result =
            $this
                ->classifier()
                ->classify(
                    source: ActionQueueSource::InventoryOperations,

                    state: 'negative_stock',

                    label: 'Negative stock',

                    severity: 'critical',

                    nativePriority: 90,

                    requiresAction: true,
                );

        $this->assertSame(
            ActionQueueSeverity::Critical,
            $result->severity,
        );

        $this->assertSame(
            550,
            $result->priority,
        );

        $this->assertTrue(
            $result->requiresAction,
        );

        $this->assertSame(
            'Negative inventory requires immediate stock reconciliation.',
            $result->reason,
        );
    }

    public function test_delivered_overdue_cod_is_prioritized_above_normal_critical_item(): void
    {
        $classifier =
            $this->classifier();

        $deliveredOverdue =
            $classifier->classify(
                source: ActionQueueSource::CodOperations,

                state: 'delivered_overdue',

                label: 'Delivered overdue',

                severity: 'critical',

                nativePriority: 70,

                requiresAction: true,
            );

        $genericCritical =
            $classifier->classify(
                source: ActionQueueSource::OperationalOrders,

                state: 'some_critical_state',

                label: 'Critical operational order',

                severity: 'critical',

                nativePriority: 70,

                requiresAction: true,
            );

        $this->assertGreaterThan(
            $genericCritical->priority,
            $deliveredOverdue->priority,
        );
    }

    public function test_severity_dominates_lower_severity_native_priority(): void
    {
        $classifier =
            $this->classifier();

        $critical =
            $classifier->classify(
                source: ActionQueueSource::OperationalOrders,

                state: 'critical',

                label: 'Critical',

                severity: 'critical',

                nativePriority: 1,

                requiresAction: true,
            );

        $high =
            $classifier->classify(
                source: ActionQueueSource::OperationalOrders,

                state: 'high',

                label: 'High',

                severity: 'high',

                nativePriority: 99,

                requiresAction: true,
            );

        $this->assertGreaterThan(
            $high->priority,
            $critical->priority,
        );
    }

    public function test_non_actionable_item_gets_zero_queue_priority(): void
    {
        $result =
            $this
                ->classifier()
                ->classify(
                    source: ActionQueueSource::InventoryOperations,

                    state: 'healthy',

                    label: 'Healthy stock',

                    severity: 'normal',

                    nativePriority: 10,

                    requiresAction: false,
                );

        $this->assertSame(
            0,
            $result->priority,
        );

        $this->assertFalse(
            $result->requiresAction,
        );
    }

    public function test_unknown_operational_state_uses_authoritative_label_as_reason(): void
    {
        $result =
            $this
                ->classifier()
                ->classify(
                    source: ActionQueueSource::OperationalOrders,

                    state: 'future_state',

                    label: 'Order requires fulfilment review',

                    severity: 'warning',

                    nativePriority: 30,

                    requiresAction: true,
                );

        $this->assertSame(
            'Order requires fulfilment review',
            $result->reason,
        );
    }

    public function test_unknown_severity_falls_back_to_normal(): void
    {
        $result =
            $this
                ->classifier()
                ->classify(
                    source: ActionQueueSource::OperationalOrders,

                    state: 'test',

                    label: 'Test',

                    severity: 'unknown',

                    nativePriority: 5,

                    requiresAction: true,
                );

        $this->assertSame(
            ActionQueueSeverity::Normal,
            $result->severity,
        );

        $this->assertSame(
            5,
            $result->priority,
        );
    }

    public function test_native_priority_is_clamped_to_cross_domain_component(): void
    {
        $result =
            $this
                ->classifier()
                ->classify(
                    source: ActionQueueSource::OperationalOrders,

                    state: 'test',

                    label: 'Test',

                    severity: 'warning',

                    nativePriority: 5_000,

                    requiresAction: true,
                );

        $this->assertSame(
            299,
            $result->priority,
        );
    }

    private function classifier(): CrossDomainActionQueueClassifier
    {
        return new CrossDomainActionQueueClassifier;
    }
}
