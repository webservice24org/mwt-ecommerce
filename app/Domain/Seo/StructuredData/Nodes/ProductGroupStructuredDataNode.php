<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Nodes;

use App\Domain\Seo\StructuredData\Contracts\StructuredDataNode;

final readonly class ProductGroupStructuredDataNode implements StructuredDataNode
{
    /**
     * @param  list<string>  $variesBy
     * @param  list<VariantProductStructuredDataNode>  $variants
     */
    public function __construct(
        public string $id,
        public string $url,
        public string $name,
        public string $productGroupId,
        public array $variesBy,
        public array $variants,
        public ?string $description = null,
        public ?string $brandName = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toStructuredData(): array
    {
        $data = [
            '@type' => 'ProductGroup',

            '@id' => $this->id,

            'url' => $this->url,

            'name' => $this->name,

            'productGroupID' => $this->productGroupId,

            'hasVariant' => array_map(
                static fn (
                    VariantProductStructuredDataNode $variant,
                ): array => $variant
                    ->toStructuredData(),
                $this->variants,
            ),
        ];

        if ($this->description !== null) {
            $data['description'] =
                $this->description;
        }

        if ($this->brandName !== null) {
            $data['brand'] = [
                '@type' => 'Brand',

                'name' => $this->brandName,
            ];
        }

        if ($this->variesBy !== []) {
            $data['variesBy'] =
                $this->variesBy;
        }

        return $data;
    }
}
