<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Merchant;

use App\Domain\Seo\Merchant\Data\MerchantFeedCatalogData;
use App\Domain\Seo\Merchant\MerchantListingReadinessPolicy;
use App\Domain\Seo\Merchant\Services\MerchantProductFeedCatalogBuilder;
use PHPUnit\Framework\TestCase;

final class MerchantReadinessWorkspaceContractTest extends TestCase
{
    public function test_m4a_keeps_the_existing_m1_policy_and_m3_bounds_authoritative(): void
    {
        self::assertSame(
            'merchant-listing-readiness-v1',
            MerchantListingReadinessPolicy::VERSION,
        );

        self::assertSame(
            1000,
            MerchantProductFeedCatalogBuilder::MAX_PRODUCTS,
        );

        self::assertSame(
            5000,
            MerchantProductFeedCatalogBuilder::MAX_ITEMS,
        );
    }

    public function test_feed_catalog_constructor_remains_backwards_compatible_for_m3_callers(): void
    {
        $catalog =
            new MerchantFeedCatalogData(
                items: [],

                excluded: [],

                truncated: false,

                maxItems: 5000,
            );

        self::assertSame(
            [],
            $catalog->entries,
        );
    }

    public function test_workspace_query_reuses_m3_builder_and_does_not_create_a_second_catalog_pricing_or_availability_source(): void
    {
        $source =
            $this->source(
                'app/Domain/Seo/Merchant/Queries/GetMerchantReadinessWorkspaceQuery.php',
            );

        self::assertStringContainsString(
            'MerchantProductFeedCatalogBuilder',
            $source,
        );

        self::assertStringContainsString(
            'MerchantOrganizationPolicyStructuredDataResolver',
            $source,
        );

        foreach (
            [
                'Product::query',
                'ProductPricingResolver',
                'StructuredDataAvailabilityResolver',
                'CanonicalUrlResolver',
                'Http::',
                'MerchantApi',
                'Google\\Merchant',
                '->save(',
                '->update(',
                '->delete(',
            ] as $forbidden
        ) {
            self::assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }
    }

    public function test_m4a_query_has_no_export_queue_or_external_submission_behavior(): void
    {
        $source =
            $this->source(
                'app/Domain/Seo/Merchant/Queries/GetMerchantReadinessWorkspaceQuery.php',
            );

        foreach (
            [
                'dispatch(',
                'Storage::',
                'response()->download',
                'MerchantTsvFeedSerializer',
                'MerchantApi',
                'Google\\Merchant',
                'Http::',
            ] as $forbidden
        ) {
            self::assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }
    }

    private function source(
        string $path,
    ): string {
        $source =
            file_get_contents(
                dirname(
                    __DIR__,
                    4,
                )
                .'/'.$path,
            );

        self::assertIsString(
            $source,
            $path,
        );

        return $source;
    }
}
