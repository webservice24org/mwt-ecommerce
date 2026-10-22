<?php

declare(strict_types=1);

namespace App\Domain\Orders\Data;

final readonly class InvoiceAddressData
{
    public function __construct(
        public string $recipientName,
        public string $phone,
        public string $addressLine1,
        public ?string $addressLine2,
        public string $city,
        public ?string $stateOrRegion,
        public ?string $postalCode,
        public string $countryCode,
    ) {}

    /**
     * @return array{
     *     recipient_name: string,
     *     phone: string,
     *     address_line_1: string,
     *     address_line_2: string|null,
     *     city: string,
     *     state_or_region: string|null,
     *     postal_code: string|null,
     *     country_code: string
     * }
     */
    public function toArray(): array
    {
        return [
            'recipient_name' => $this->recipientName,

            'phone' => $this->phone,

            'address_line_1' => $this->addressLine1,

            'address_line_2' => $this->addressLine2,

            'city' => $this->city,

            'state_or_region' => $this->stateOrRegion,

            'postal_code' => $this->postalCode,

            'country_code' => $this->countryCode,
        ];
    }
}
