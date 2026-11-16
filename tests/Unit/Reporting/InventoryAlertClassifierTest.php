<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use App\Domain\Reporting\Enums\InventoryAlertState;
use App\Domain\Reporting\Operations\InventoryAlertClassifier;
use Tests\TestCase;

final class InventoryAlertClassifierTest extends TestCase
{
    public function test_untracked_inventory_has_no_alert(): void
    {
        $result =
            $this->classifier()
                ->classify(
                    trackInventory: false,

                    stockQuantity: 0,
                );

        $this->assertSame(
            InventoryAlertState::NotTracked,
            $result->state,
        );

        $this->assertFalse(
            $result
                ->requiresAttention(),
        );
    }

    public function test_healthy_stock_is_not_attention_required(): void
    {
        $result =
            $this->classifier()
                ->classify(
                    trackInventory: true,

                    stockQuantity: 20,
                );

        $this->assertSame(
            InventoryAlertState::Healthy,
            $result->state,
        );

        $this->assertFalse(
            $result
                ->requiresAttention(),
        );
    }

    public function test_low_stock_is_warning(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 5,

            'reporting.inventory_operations.critical_stock_threshold' => 2,
        ]);

        $result =
            $this->classifier()
                ->classify(
                    trackInventory: true,

                    stockQuantity: 4,
                );

        $this->assertSame(
            InventoryAlertState::LowStock,
            $result->state,
        );

        $this->assertSame(
            'warning',
            $result
                ->state
                ->severity(),
        );
    }

    public function test_critical_stock_is_high_severity(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 5,

            'reporting.inventory_operations.critical_stock_threshold' => 2,
        ]);

        $result =
            $this->classifier()
                ->classify(
                    trackInventory: true,

                    stockQuantity: 2,
                );

        $this->assertSame(
            InventoryAlertState::CriticalStock,
            $result->state,
        );

        $this->assertSame(
            'high',
            $result
                ->state
                ->severity(),
        );
    }

    public function test_zero_stock_is_out_of_stock(): void
    {
        $result =
            $this->classifier()
                ->classify(
                    trackInventory: true,

                    stockQuantity: 0,
                );

        $this->assertSame(
            InventoryAlertState::OutOfStock,
            $result->state,
        );

        $this->assertSame(
            'critical',
            $result
                ->state
                ->severity(),
        );
    }

    public function test_negative_stock_has_highest_priority(): void
    {
        $result =
            $this->classifier()
                ->classify(
                    trackInventory: true,

                    stockQuantity: -3,
                );

        $this->assertSame(
            InventoryAlertState::NegativeStock,
            $result->state,
        );

        $this->assertSame(
            90,
            $result
                ->state
                ->priority(),
        );

        $this->assertTrue(
            $result
                ->requiresAttention(),
        );
    }

    public function test_critical_threshold_is_clamped_to_low_stock_threshold(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 3,

            'reporting.inventory_operations.critical_stock_threshold' => 10,
        ]);

        $result =
            $this->classifier()
                ->classify(
                    trackInventory: true,

                    stockQuantity: 3,
                );

        $this->assertSame(
            InventoryAlertState::CriticalStock,
            $result->state,
        );
    }

    private function classifier(): InventoryAlertClassifier
    {
        return new InventoryAlertClassifier;
    }
}
