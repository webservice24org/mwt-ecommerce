<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Merchant;

use App\Domain\Seo\Merchant\Enums\MerchantIdentifierState;
use App\Domain\Seo\Merchant\Enums\MerchantListingFindingSeverity;
use App\Domain\Seo\Merchant\Enums\MerchantListingReadinessStatus;
use App\Domain\Seo\Merchant\MerchantListingReadinessPolicy;
use PHPUnit\Framework\TestCase;

final class MerchantListingReadinessContractTest extends TestCase
{
    public function test_policy_is_versioned_and_non_numeric(): void
    {
        self::assertSame(
            'merchant-listing-readiness-v1',
            MerchantListingReadinessPolicy::VERSION,
        );

        self::assertSame(
            [
                'ready',
                'needs_work',
                'blocked',
            ],
            array_map(
                static fn (
                    MerchantListingReadinessStatus $status,
                ): string => $status->value,
                MerchantListingReadinessStatus::cases(),
            ),
        );

        self::assertSame(
            [
                'blocker',
                'warning',
                'info',
            ],
            array_map(
                static fn (
                    MerchantListingFindingSeverity $severity,
                ): string => $severity->value,
                MerchantListingFindingSeverity::cases(),
            ),
        );
    }

    public function test_identifier_state_requires_explicit_truth_instead_of_guessing_gtin_or_mpn(): void
    {
        self::assertSame(
            [
                'available',
                'not_available_declared',
                'unknown',
            ],
            array_map(
                static fn (
                    MerchantIdentifierState $state,
                ): string => $state->value,
                MerchantIdentifierState::cases(),
            ),
        );
    }

    public function test_supported_product_types_match_current_storefront_product_contract(): void
    {
        self::assertSame(
            [
                'simple',
                'variable',
            ],
            MerchantListingReadinessPolicy::supportedProductTypes(),
        );

        self::assertSame(
            [
                'Product',
                'ProductGroup',
            ],
            MerchantListingReadinessPolicy::merchantProductStructuredDataTypes(),
        );
    }

    public function test_analyzer_does_not_call_merchant_api_or_mutate_catalog(): void
    {
        $source =
            file_get_contents(
                dirname(
                    __DIR__,
                    4,
                )
                .'/app/Domain/Seo/Merchant/Services/MerchantListingReadinessAnalyzer.php',
            );

        self::assertIsString(
            $source,
        );

        foreach (
            [
                'Http::',
                'MerchantApi',
                'Google\\Ads',
                '->save(',
                '->update(',
                '->delete(',
                'score',
            ] as $forbidden
        ) {
            self::assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }
    }
}
