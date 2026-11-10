<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Data;

final readonly class ShippingRateQuote
{
    public function __construct(
        public int $shippingMethodId,
        public int $shippingRateId,
        public string $methodCode,
        public string $methodName,
        public string $rateCode,
        public string $rateName,
        public int $amount,
        public bool $free,
    ) {}

    /**
     * @return array{
     *     shipping_method_id: int,
     *     shipping_rate_id: int,
     *     method_code: string,
     *     method_name: string,
     *     rate_code: string,
     *     rate_name: string,
     *     amount: int,
     *     free: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'shipping_method_id' => $this->shippingMethodId,
            'shipping_rate_id' => $this->shippingRateId,
            'method_code' => $this->methodCode,
            'method_name' => $this->methodName,
            'rate_code' => $this->rateCode,
            'rate_name' => $this->rateName,
            'amount' => $this->amount,
            'free' => $this->free,
        ];
    }
}
