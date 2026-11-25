<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Services;

use App\Domain\Seo\Merchant\Data\MerchantFeedItemData;

final readonly class MerchantTsvFeedSerializer
{
    /**
     * @var list<string>
     */
    private const HEADERS = [
        'id',
        'title',
        'description',
        'link',
        'image_link',
        'additional_image_link',
        'availability',
        'price',
        'brand',
        'gtin',
        'mpn',
        'identifier_exists',
        'condition',
        'item_group_id',
        'google_product_category',
        'color',
        'size',
        'material',
        'pattern',
    ];

    /**
     * @param  list<MerchantFeedItemData>  $items
     */
    public function serialize(
        array $items,
    ): string {
        $lines = [
            implode(
                "\t",
                self::HEADERS,
            ),
        ];

        foreach ($items as $item) {
            $flat =
                $item->toFlatArray();

            $row = [];

            foreach (
                self::HEADERS as $header
            ) {
                $row[] =
                    $this->cell(
                        $flat[
                            $header
                        ]
                        ?? '',
                    );
            }

            $lines[] =
                implode(
                    "\t",
                    $row,
                );
        }

        return implode(
            "\n",
            $lines,
        )
        ."\n";
    }

    private function cell(
        string $value,
    ): string {
        $value =
            preg_replace(
                '/[\t\r\n]+/u',
                ' ',
                $value,
            );

        if (! is_string($value)) {
            return '';
        }

        $value =
            preg_replace(
                '/\s{2,}/u',
                ' ',
                $value,
            );

        return is_string($value)
            ? trim(
                $value,
            )
            : '';
    }
}
