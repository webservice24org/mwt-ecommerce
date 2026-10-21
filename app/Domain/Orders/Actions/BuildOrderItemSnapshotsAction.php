<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Cart\Data\CartSummaryData;
use App\Domain\Orders\Data\OrderItemSnapshotData;

final readonly class BuildOrderItemSnapshotsAction
{
    public function __construct(
        private BuildOrderItemSnapshotAction $buildSnapshot,
    ) {}

    /**
     * @return list<OrderItemSnapshotData>
     */
    public function execute(
        CartSummaryData $cart,
    ): array {
        $snapshots = [];

        foreach ($cart->items as $item) {
            $snapshots[] =
                $this->buildSnapshot
                    ->execute(
                        $item,
                    );
        }

        return $snapshots;
    }
}
