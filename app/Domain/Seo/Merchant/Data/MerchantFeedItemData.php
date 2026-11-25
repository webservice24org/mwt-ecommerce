<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Data;

final readonly class MerchantFeedItemData
{
    /**
     * @param  list<string>  $additionalImageLinks
     */
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $link,
        public string $imageLink,
        public array $additionalImageLinks,
        public string $availability,
        public string $price,
        public ?string $brand,
        public ?string $gtin,
        public ?string $mpn,
        public ?string $identifierExists,
        public string $condition,
        public ?string $itemGroupId,
        public ?string $googleProductCategory,
        public ?string $color,
        public ?string $size,
        public ?string $material,
        public ?string $pattern,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toFlatArray(): array
    {
        $data = [
            'id' => $this->id,

            'title' => $this->title,

            'description' => $this->description,

            'link' => $this->link,

            'image_link' => $this->imageLink,

            'availability' => $this->availability,

            'price' => $this->price,

            'condition' => $this->condition,
        ];

        $optional = [
            'additional_image_link' => $this->additionalImageLinks !== []
                    ? implode(
                        ',',
                        $this->additionalImageLinks,
                    )
                    : null,

            'brand' => $this->brand,

            'gtin' => $this->gtin,

            'mpn' => $this->mpn,

            'identifier_exists' => $this->identifierExists,

            'item_group_id' => $this->itemGroupId,

            'google_product_category' => $this->googleProductCategory,

            'color' => $this->color,

            'size' => $this->size,

            'material' => $this->material,

            'pattern' => $this->pattern,
        ];

        foreach (
            $optional as $key => $value
        ) {
            if (
                $value === null
                || $value === ''
            ) {
                continue;
            }

            $data[
                $key
            ] =
                $value;
        }

        return $data;
    }
}
