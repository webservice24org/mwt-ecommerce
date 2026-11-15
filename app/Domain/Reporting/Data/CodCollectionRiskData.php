<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

use App\Domain\Reporting\Enums\CodCollectionRiskState;

final readonly class CodCollectionRiskData
{
    public function __construct(
        public CodCollectionRiskState $state,
    ) {}

    public function requiresFollowUp(): bool
    {
        return $this->state !==
            CodCollectionRiskState::None;
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

            'requires_follow_up' => $this->requiresFollowUp(),
        ];
    }
}
