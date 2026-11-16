<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

use App\Domain\Reporting\Enums\ActionQueueSeverity;
use App\Domain\Reporting\Enums\ActionQueueSource;

final readonly class ActionQueueItemData
{
    public function __construct(
        public ActionQueueSource $source,

        public string $entityType,
        public int $entityId,

        public string $reference,
        public string $title,

        public int $priority,
        public ActionQueueSeverity $severity,

        public string $reason,
        public bool $requiresAction,

        public int $ageHours,
        public int $ageDays,

        public string $actionUrl,
        public string $actionLabel,
    ) {}

    /**
     * @return array<string, bool|int|string>
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source->value,

            'source_label' => $this->source->label(),

            'entity_type' => $this->entityType,

            'entity_id' => $this->entityId,

            'reference' => $this->reference,

            'title' => $this->title,

            'priority' => $this->priority,

            'severity' => $this->severity->value,

            'reason' => $this->reason,

            'requires_action' => $this->requiresAction,

            'age_hours' => $this->ageHours,

            'age_days' => $this->ageDays,

            'action_url' => $this->actionUrl,

            'action_label' => $this->actionLabel,
        ];
    }
}
