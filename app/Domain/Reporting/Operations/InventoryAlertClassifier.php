<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Operations;

use App\Domain\Reporting\Data\InventoryAlertData;
use App\Domain\Reporting\Enums\InventoryAlertState;

final class InventoryAlertClassifier
{
    public function classify(
        bool $trackInventory,
        int $stockQuantity,
    ): InventoryAlertData {
        if (
            ! $trackInventory
        ) {
            return new InventoryAlertData(
                InventoryAlertState::NotTracked,
            );
        }

        if (
            $stockQuantity < 0
        ) {
            return new InventoryAlertData(
                InventoryAlertState::NegativeStock,
            );
        }

        if (
            $stockQuantity === 0
        ) {
            return new InventoryAlertData(
                InventoryAlertState::OutOfStock,
            );
        }

        if (
            $stockQuantity <=
            $this->criticalStockThreshold()
        ) {
            return new InventoryAlertData(
                InventoryAlertState::CriticalStock,
            );
        }

        if (
            $stockQuantity <=
            $this->lowStockThreshold()
        ) {
            return new InventoryAlertData(
                InventoryAlertState::LowStock,
            );
        }

        return new InventoryAlertData(
            InventoryAlertState::Healthy,
        );
    }

    private function lowStockThreshold(): int
    {
        return max(
            0,
            (int) config(
                'reporting.inventory_operations.low_stock_threshold',
                5,
            ),
        );
    }

    private function criticalStockThreshold(): int
    {
        $lowStockThreshold =
            $this->lowStockThreshold();

        return min(
            $lowStockThreshold,
            max(
                0,
                (int) config(
                    'reporting.inventory_operations.critical_stock_threshold',
                    2,
                ),
            ),
        );
    }
}
