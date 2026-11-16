<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Operations;

use App\Domain\Reporting\Data\ActionQueueClassificationData;
use App\Domain\Reporting\Enums\ActionQueueSeverity;
use App\Domain\Reporting\Enums\ActionQueueSource;

final class CrossDomainActionQueueClassifier
{
    public function classify(
        ActionQueueSource $source,
        string $state,
        string $label,
        string $severity,
        int $nativePriority,
        bool $requiresAction,
    ): ActionQueueClassificationData {
        $queueSeverity =
            $this->severity(
                $severity,
            );

        if (
            ! $requiresAction
        ) {
            return new ActionQueueClassificationData(
                priority: 0,

                severity: $queueSeverity,

                reason: $this->reason(
                    source: $source,

                    state: $state,

                    fallback: $label,
                ),

                requiresAction: false,
            );
        }

        return new ActionQueueClassificationData(
            priority: $this->priority(
                source: $source,

                state: $state,

                severity: $queueSeverity,

                nativePriority: $nativePriority,
            ),

            severity: $queueSeverity,

            reason: $this->reason(
                source: $source,

                state: $state,

                fallback: $label,
            ),

            requiresAction: true,
        );
    }

    private function severity(
        string $severity,
    ): ActionQueueSeverity {
        return match (
            $severity
        ) {
            'critical' => ActionQueueSeverity::Critical,

            'high' => ActionQueueSeverity::High,

            'warning' => ActionQueueSeverity::Warning,

            'info' => ActionQueueSeverity::Info,

            default => ActionQueueSeverity::Normal,
        };
    }

    private function priority(
        ActionQueueSource $source,
        string $state,
        ActionQueueSeverity $severity,
        int $nativePriority,
    ): int {
        /*
         * Severity is the dominant cross-domain ordering
         * dimension.
         */
        $severityBase =
            match ($severity) {
                ActionQueueSeverity::Critical => 400,

                ActionQueueSeverity::High => 300,

                ActionQueueSeverity::Warning => 200,

                ActionQueueSeverity::Info => 100,

                ActionQueueSeverity::Normal => 0,
            };

        /*
         * Small explicit boosts let especially dangerous
         * states outrank peers with the same severity.
         *
         * Existing domain classifiers remain authoritative
         * for whether the state exists and whether action
         * is required.
         */
        $workflowBoost =
            match ([
                $source,
                $state,
            ]) {
                [
                    ActionQueueSource::InventoryOperations,
                    'negative_stock',
                ] => 60,

                [
                    ActionQueueSource::CodOperations,
                    'delivered_overdue',
                ] => 50,

                [
                    ActionQueueSource::CodOperations,
                    'delivered_due',
                ] => 40,

                [
                    ActionQueueSource::CodOperations,
                    'due_overdue',
                ] => 35,

                [
                    ActionQueueSource::InventoryOperations,
                    'out_of_stock',
                ] => 30,

                [
                    ActionQueueSource::CodOperations,
                    'delivered_unpaid',
                ] => 25,

                [
                    ActionQueueSource::InventoryOperations,
                    'critical_stock',
                ] => 20,

                default => 0,
            };

        /*
         * Preserve useful ordering from the authoritative
         * source classifier without allowing one domain's
         * priority scale to overwhelm severity.
         */
        $nativeComponent =
            min(
                99,
                max(
                    0,
                    $nativePriority,
                ),
            );

        return
            $severityBase +
            $workflowBoost +
            $nativeComponent;
    }

    private function reason(
        ActionQueueSource $source,
        string $state,
        string $fallback,
    ): string {
        return match ([
            $source,
            $state,
        ]) {
            [
                ActionQueueSource::InventoryOperations,
                'negative_stock',
            ] => 'Negative inventory requires immediate stock reconciliation.',

            [
                ActionQueueSource::InventoryOperations,
                'out_of_stock',
            ] => 'Tracked product is out of stock.',

            [
                ActionQueueSource::InventoryOperations,
                'critical_stock',
            ] => 'Tracked product has reached the critical stock threshold.',

            [
                ActionQueueSource::InventoryOperations,
                'low_stock',
            ] => 'Tracked product is below the low-stock threshold.',

            [
                ActionQueueSource::CodOperations,
                'delivered_overdue',
            ] => 'Delivered COD order remains overdue for collection.',

            [
                ActionQueueSource::CodOperations,
                'delivered_due',
            ] => 'Delivered COD order still has payment due.',

            [
                ActionQueueSource::CodOperations,
                'delivered_unpaid',
            ] => 'Delivered COD order remains unpaid.',

            [
                ActionQueueSource::CodOperations,
                'due_overdue',
            ] => 'COD payment is past its collection due window.',

            [
                ActionQueueSource::CodOperations,
                'unpaid_stale',
            ] => 'COD payment has remained unpaid beyond the stale threshold.',

            default => $fallback !== ''
                ? $fallback
                : $source->label().
                ' requires operational review.',
        };
    }
}
