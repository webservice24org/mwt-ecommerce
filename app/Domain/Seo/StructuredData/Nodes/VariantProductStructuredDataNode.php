<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Nodes;

use App\Domain\Seo\StructuredData\Contracts\StructuredDataNode;

final readonly class VariantProductStructuredDataNode implements StructuredDataNode
{
    /**
     * @param  array<string, string>  $variantProperties
     */
    public function __construct(
        public string $id,
        public string $groupId,
        public string $url,
        public string $name,
        public string $sku,
        public OfferStructuredDataNode $offer,
        public array $variantProperties = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toStructuredData(): array
    {
        return [
            '@type' => 'Product',

            '@id' => $this->id,

            'isVariantOf' => [
                '@id' => $this->groupId,
            ],

            'url' => $this->url,

            'name' => $this->name,

            'sku' => $this->sku,

            ...$this->variantProperties,

            'offers' => $this
                ->offer
                ->toStructuredData(),
        ];
    }
}
