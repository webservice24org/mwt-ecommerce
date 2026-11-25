<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Nodes;

use App\Domain\Seo\StructuredData\Contracts\StructuredDataNode;

final readonly class ProductStructuredDataNode implements StructuredDataNode
{
    /**
     * @param  list<string>  $images
     */
    public function __construct(
        public string $id,
        public string $url,
        public string $name,
        public ?string $description = null,
        public ?string $sku = null,
        public array $images = [],
        public ?string $brandName = null,
        public ?OfferStructuredDataNode $offer = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toStructuredData(): array
    {
        $data = [
            '@type' => 'Product',

            '@id' => $this->id,

            'url' => $this->url,

            'name' => $this->name,
        ];

        if ($this->description !== null) {
            $data['description'] =
                $this->description;
        }

        if ($this->sku !== null) {
            $data['sku'] =
                $this->sku;
        }

        if ($this->images !== []) {
            $data['image'] =
                $this->images;
        }

        if ($this->brandName !== null) {
            $data['brand'] = [
                '@type' => 'Brand',

                'name' => $this->brandName,
            ];
        }

        if ($this->offer !== null) {
            $data['offers'] =
                $this
                    ->offer
                    ->toStructuredData();
        }

        return $data;
    }
}
