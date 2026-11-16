<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

use App\Domain\Reporting\Enums\InventoryAlertState;

final readonly class InventoryAlertData
{
    public function __construct(
        public InventoryAlertState $state,
    ) {}

    public function requiresAttention(): bool
    {
        return in_array(
            $this->state,
            [
                InventoryAlertState::LowStock,
                InventoryAlertState::CriticalStock,
                InventoryAlertState::OutOfStock,
                InventoryAlertState::NegativeStock,
            ],
            true,
        );
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
