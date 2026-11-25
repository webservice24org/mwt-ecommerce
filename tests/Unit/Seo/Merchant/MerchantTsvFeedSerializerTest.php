<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Merchant;

use App\Domain\Seo\Merchant\Data\MerchantFeedItemData;
use App\Domain\Seo\Merchant\Services\MerchantTsvFeedSerializer;
use PHPUnit\Framework\TestCase;

final class MerchantTsvFeedSerializerTest extends TestCase
{
    public function test_serializer_emits_stable_google_attribute_headers_and_utf8_safe_rows(): void
    {
        $item =
            new MerchantFeedItemData(
                id: 'v-41',

                title: "USB Cable\nBlack",

                description: "Fast\tbraided cable.",

                link: 'https://example.com/products/cable?variant=41',

                imageLink: 'https://example.com/storage/cable.jpg',

                additionalImageLinks: [
                    'https://example.com/storage/cable-2.jpg',
                ],

                availability: 'in_stock',

                price: '850.00 BDT',

                brand: 'MWT',

                gtin: '4006381333931',

                mpn: 'CAB-41',

                identifierExists: 'yes',

                condition: 'new',

                itemGroupId: 'p-10',

                googleProductCategory: 'Electronics > Cables',

                color: 'Black',

                size: null,

                material: null,

                pattern: null,
            );

        $tsv =
            (new MerchantTsvFeedSerializer)
                ->serialize([
                    $item,
                ]);

        $lines =
            explode(
                "\n",
                trim(
                    $tsv,
                ),
            );

        self::assertCount(
            2,
            $lines,
        );

        self::assertStringStartsWith(
            "id\ttitle\tdescription\tlink\timage_link\tadditional_image_link\tavailability\tprice",
            $lines[
                0
            ],
        );

        self::assertStringContainsString(
            "v-41\tUSB Cable Black\tFast braided cable.",
            $lines[
                1
            ],
        );

        self::assertStringContainsString(
            "\t850.00 BDT\tMWT\t4006381333931\tCAB-41\tyes\tnew\tp-10\t",
            $lines[
                1
            ],
        );
    }

    public function test_optional_values_are_rendered_as_empty_cells_not_placeholder_text(): void
    {
        $item =
            new MerchantFeedItemData(
                id: 'p-10',

                title: 'Cable',

                description: 'Cable description.',

                link: 'https://example.com/products/cable',

                imageLink: 'https://example.com/cable.jpg',

                additionalImageLinks: [],

                availability: 'out_of_stock',

                price: '850.00 BDT',

                brand: null,

                gtin: null,

                mpn: null,

                identifierExists: null,

                condition: 'new',

                itemGroupId: null,

                googleProductCategory: null,

                color: null,

                size: null,

                material: null,

                pattern: null,
            );

        $tsv =
            (new MerchantTsvFeedSerializer)
                ->serialize([
                    $item,
                ]);

        self::assertStringNotContainsString(
            'N/A',
            $tsv,
        );

        self::assertStringNotContainsString(
            'Generic',
            $tsv,
        );
    }
}
