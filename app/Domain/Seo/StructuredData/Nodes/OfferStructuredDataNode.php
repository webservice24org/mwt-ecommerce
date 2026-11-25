<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Nodes;

use App\Domain\Seo\StructuredData\Contracts\StructuredDataNode;

final readonly class OfferStructuredDataNode implements StructuredDataNode
{
    public function __construct(
        public string $id,
        public string $url,
        public string $price,
        public string $priceCurrency,
        public ?string $availability = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toStructuredData(): array
    {
        $data = [
            '@type' => 'Offer',

            '@id' => $this->id,

            'url' => $this->url,

            'price' => $this->price,

            'priceCurrency' => $this->priceCurrency,
        ];

        if ($this->availability !== null) {
            $data['availability'] =
                $this->availability;
        }

        return $data;
    }
}
