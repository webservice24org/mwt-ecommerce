<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Data;

final readonly class CheckoutTotalsData
{
    public function __construct(
        public int $subtotal,
        public int $discountTotal,
        public int $deliveryTotal,
        public int $taxTotal,
        public int $grandTotal,
    ) {}

    /**
     * @return array{
     *     subtotal: int,
     *     discount_total: int,
     *     delivery_total: int,
     *     tax_total: int,
     *     grand_total: int
     * }
     */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discountTotal,
            'delivery_total' => $this->deliveryTotal,
            'tax_total' => $this->taxTotal,
            'grand_total' => $this->grandTotal,
        ];
    }
}
