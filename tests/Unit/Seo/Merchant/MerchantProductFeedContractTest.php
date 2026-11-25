<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Merchant;

use App\Domain\Seo\Merchant\Enums\MerchantProductCondition;
use App\Domain\Seo\Merchant\Services\MerchantProductFeedCatalogBuilder;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class MerchantProductFeedContractTest extends TestCase
{
    public function test_condition_contract_uses_only_supported_merchant_values(): void
    {
        self::assertSame(
            [
                'new',
                'refurbished',
                'used',
            ],
            array_map(
                static fn (
                    MerchantProductCondition $condition,
                ): string => $condition->value,
                MerchantProductCondition::cases(),
            ),
        );
    }

    public function test_feed_generation_is_bounded_and_not_exposed_as_public_route_yet(): void
    {
        self::assertSame(
            1000,
            MerchantProductFeedCatalogBuilder::MAX_PRODUCTS,
        );

        self::assertSame(
            5000,
            MerchantProductFeedCatalogBuilder::MAX_ITEMS,
        );

        foreach (
            [
                'merchant.feed',
                'merchant.feed.tsv',
                'admin.seo.merchant.feed.export',
                'admin.seo.merchant.feed.publish',
                'admin.seo.merchant.feed.sync',
            ] as $routeName
        ) {
            self::assertFalse(
                Route::has(
                    $routeName,
                ),
                $routeName,
            );
        }
    }

    public function test_feed_item_factory_reuses_m1_readiness_and_never_infers_gtin_from_barcode(): void
    {
        $source =
            $this->source(
                'app/Domain/Seo/Merchant/Services/MerchantProductFeedItemFactory.php',
            );

        self::assertStringContainsString(
            'MerchantListingReadinessStatus::Blocked',
            $source,
        );

        self::assertStringContainsString(
            "'primary_image_missing'",
            $this->source(
                'app/Domain/Seo/Merchant/Services/MerchantListingReadinessAnalyzer.php',
            ),
        );

        self::assertStringNotContainsString(
            '->barcode',
            $source,
        );

        self::assertStringContainsString(
            "'?variant='",
            $source,
        );

        self::assertStringContainsString(
            "'p-'",
            $source,
        );

        self::assertStringContainsString(
            "'v-'",
            $source,
        );
    }

    public function test_feed_foundation_has_no_external_merchant_api_calls_or_price_override(): void
    {
        $paths = [
            'app/Domain/Seo/Merchant/Services/MerchantProductFeedItemFactory.php',
            'app/Domain/Seo/Merchant/Services/MerchantProductFeedCatalogBuilder.php',
            'app/Domain/Seo/Merchant/Services/MerchantTsvFeedSerializer.php',
        ];

        foreach ($paths as $path) {
            $source =
                $this->source(
                    $path,
                );

            foreach (
                [
                    'Http::',
                    'MerchantApi',
                    'Google\\Shopping',
                    'Google\\Merchant',
                    'curl_',
                ] as $forbidden
            ) {
                self::assertStringNotContainsString(
                    $forbidden,
                    $source,
                    $path,
                );
            }
        }

        $factory =
            $this->source(
                'app/Domain/Seo/Merchant/Services/MerchantProductFeedItemFactory.php',
            );

        self::assertStringContainsString(
            'ProductPricingResolver',
            $factory,
        );

        self::assertStringContainsString(
            'StructuredDataAvailabilityResolver',
            $factory,
        );

        self::assertStringContainsString(
            'intdiv(',
            $factory,
        );

        self::assertStringNotContainsString(
            '(float)',
            $factory,
        );
    }

    public function test_tsv_contract_contains_core_google_product_attributes(): void
    {
        $source =
            $this->source(
                'app/Domain/Seo/Merchant/Services/MerchantTsvFeedSerializer.php',
            );

        foreach (
            [
                "'id'",
                "'title'",
                "'description'",
                "'link'",
                "'image_link'",
                "'availability'",
                "'price'",
                "'condition'",
                "'item_group_id'",
                "'identifier_exists'",
            ] as $attribute
        ) {
            self::assertStringContainsString(
                $attribute,
                $source,
            );
        }
    }

    public function test_metadata_migration_keeps_explicit_gtin_mpn_and_identifier_state_separate_from_catalog_barcode(): void
    {
        $source =
            $this->source(
                'database/migrations/2026_11_23_210000_create_merchant_product_data_table.php',
            );

        self::assertStringContainsString(
            "'identifier_state'",
            $source,
        );

        self::assertStringContainsString(
            "'gtin'",
            $source,
        );

        self::assertStringContainsString(
            "'mpn'",
            $source,
        );

        self::assertStringNotContainsString(
            "'barcode'",
            $source,
        );
    }

    private function source(
        string $path,
    ): string {
        $source =
            file_get_contents(
                base_path(
                    $path,
                ),
            );

        self::assertIsString(
            $source,
            $path,
        );

        return $source;
    }
}
