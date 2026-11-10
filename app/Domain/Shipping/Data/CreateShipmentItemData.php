<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Data;

final readonly class CreateShipmentItemData
{
    public function __construct(
        public int $orderItemId,
        public int $quantity,
    ) {}
}
