<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Operations;

use App\Domain\Reporting\Data\ActionQueueItemData;
use App\Domain\Reporting\Enums\ActionQueueSource;

final class ActionQueueItemFactory
{
    public function __construct(
        private readonly CrossDomainActionQueueClassifier $classifier,
    ) {}

    public function make(
        ActionQueueSource $source,

        string $entityType,
        int $entityId,

        string $reference,
        string $title,

        string $state,
        string $stateLabel,
        string $severity,
        int $nativePriority,
        bool $requiresAction,

        int $ageHours,
        int $ageDays,

        string $actionUrl,
        string $actionLabel,
    ): ActionQueueItemData {
        $classification =
            $this
                ->classifier
                ->classify(
                    source: $source,

                    state: $state,

                    label: $stateLabel,

                    severity: $severity,

                    nativePriority: $nativePriority,

                    requiresAction: $requiresAction,
                );

        return new ActionQueueItemData(
            source: $source,

            entityType: $entityType,

            entityId: $entityId,

            reference: $reference,

            title: $title,

            priority: $classification
                ->priority,

            severity: $classification
                ->severity,

            reason: $classification
                ->reason,

            requiresAction: $classification
                ->requiresAction,

            ageHours: max(
                0,
                $ageHours,
            ),

            ageDays: max(
                0,
                $ageDays,
            ),

            actionUrl: $actionUrl,

            actionLabel: $actionLabel,
        );
    }
}
