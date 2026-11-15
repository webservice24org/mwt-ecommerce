<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

use App\Domain\Reporting\Enums\OperationalOrderAttentionState;

final readonly class OperationalOrderAttentionData
{
    public function __construct(
        public OperationalOrderAttentionState $state,
    ) {}

    public function requiresAttention(): bool
    {
        return $this->state !==
            OperationalOrderAttentionState::None;
    }

    /**
     * @return array<string, bool|int|string>
     */
    public function toArray(): array
    {
        return [
            'state' => $this->state->value,

            'label' => $this->state->label(),

            'severity' => $this->state->severity(),

            'priority' => $this->state->priority(),

            'requires_attention' => $this->requiresAttention(),
        ];
    }
}
