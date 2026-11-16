<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

use App\Domain\Reporting\Enums\ActionQueueSeverity;

final readonly class ActionQueueClassificationData
{
    public function __construct(
        public int $priority,
        public ActionQueueSeverity $severity,
        public string $reason,
        public bool $requiresAction,
    ) {}

    /**
     * @return array<string, bool|int|string>
     */
    public function toArray(): array
    {
        return [
            'priority' => $this->priority,

            'severity' => $this->severity->value,

            'reason' => $this->reason,

            'requires_action' => $this->requiresAction,
        ];
    }
}
