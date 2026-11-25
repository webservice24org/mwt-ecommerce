<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Merchant;

use App\Domain\Seo\Merchant\Enums\MerchantRefundType;
use App\Domain\Seo\Merchant\Enums\MerchantReturnFees;
use App\Domain\Seo\Merchant\Enums\MerchantReturnMethod;
use App\Domain\Seo\Merchant\Enums\MerchantReturnPolicyCategory;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class MerchantStructuredDataPolicyContractTest extends TestCase
{
    public function test_schema_enum_mappings_are_explicit(): void
    {
        self::assertSame(
            'https://schema.org/MerchantReturnFiniteReturnWindow',
            MerchantReturnPolicyCategory::Finite
                ->schemaUrl(),
        );

        self::assertSame(
            'https://schema.org/MerchantReturnNotPermitted',
            MerchantReturnPolicyCategory::NotPermitted
                ->schemaUrl(),
        );

        self::assertSame(
            'https://schema.org/MerchantReturnUnlimitedWindow',
            MerchantReturnPolicyCategory::Unlimited
                ->schemaUrl(),
        );

        self::assertSame(
            'https://schema.org/ReturnByMail',
            MerchantReturnMethod::ByMail
                ->schemaUrl(),
        );

        self::assertSame(
            'https://schema.org/FreeReturn',
            MerchantReturnFees::Free
                ->schemaUrl(),
        );

        self::assertSame(
            'https://schema.org/FullRefund',
            MerchantRefundType::Full
                ->schemaUrl(),
        );
    }

    public function test_admin_route_surface_is_small_and_explicit(): void
    {
        self::assertTrue(
            Route::has(
                'admin.seo.merchant.policies.index',
            ),
        );

        self::assertTrue(
            Route::has(
                'admin.seo.merchant.policies.update',
            ),
        );

        foreach (
            [
                'admin.seo.merchant.policies.index',
                'admin.seo.merchant.policies.update',
            ] as $routeName
        ) {
            $route =
                Route::getRoutes()
                    ->getByName(
                        $routeName,
                    );

            self::assertNotNull(
                $route,
                $routeName,
            );

            self::assertContains(
                'auth:admin',
                $route
                    ->gatherMiddleware(),
                $routeName,
            );

            self::assertContains(
                'admin.active',
                $route
                    ->gatherMiddleware(),
                $routeName,
            );
        }

        foreach (
            [
                'admin.seo.merchant.policies.auto-detect',
                'admin.seo.merchant.policies.sync-google',
                'admin.seo.merchant.policies.publish',
                'admin.seo.merchant.policies.guess',
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

    public function test_resolver_reuses_existing_stable_organization_id_and_does_not_invent_delivery_times(): void
    {
        $source =
            $this->source(
                'app/Domain/Seo/Merchant/Services/MerchantOrganizationPolicyStructuredDataResolver.php',
            );

        self::assertStringContainsString(
            ".'#organization'",
            $source,
        );

        self::assertStringContainsString(
            "'hasShippingService'",
            $source,
        );

        self::assertStringContainsString(
            "'hasMerchantReturnPolicy'",
            $source,
        );

        self::assertStringContainsString(
            "'shippingConditions'",
            $source,
        );

        self::assertStringNotContainsString(
            'handlingTime',
            $source,
        );

        self::assertStringNotContainsString(
            'transitTime',
            $source,
        );

        self::assertStringNotContainsString(
            'deliveryTime',
            $source,
        );

        self::assertStringContainsString(
            'intdiv(',
            $source,
        );

        self::assertStringNotContainsString(
            '(float)',
            $source,
        );
    }

    public function test_city_or_other_non_country_checkout_rules_are_fail_closed_for_shipping_markup(): void
    {
        $source =
            $this->source(
                'app/Domain/Seo/Merchant/Services/MerchantOrganizationPolicyStructuredDataResolver.php',
            );

        self::assertStringContainsString(
            'ShippingZoneRuleType::CountryCode',
            $source,
        );

        self::assertStringContainsString(
            'Google DefinedRegion cannot safely represent this checkout rule.',
            $source,
        );

        self::assertStringContainsString(
            '$this->shippingOmissionReasons()',
            $source,
        );
    }

    public function test_storefront_head_uses_separate_flat_jsonld_document_and_does_not_replace_page_structured_data(): void
    {
        $head =
            $this->source(
                'resources/js/Components/Frontend/Seo/MerchantPolicyStructuredDataHead.tsx',
            );

        $layout =
            $this->source(
                'resources/js/Layouts/Frontend/FrontendLayout.tsx',
            );

        self::assertStringContainsString(
            'merchant-policy-structured-data',
            $head,
        );

        self::assertStringContainsString(
            'JSON.stringify(',
            $head,
        );

        self::assertStringContainsString(
            'MerchantPolicyStructuredDataHead',
            $layout,
        );

        self::assertStringContainsString(
            '<MerchantPolicyStructuredDataHead',
            $layout,
        );

        self::assertStringNotContainsString(
            'head-key="structured-data"',
            $head,
        );
    }

    public function test_web_middleware_is_registered_without_replacing_existing_l2_policy(): void
    {
        $bootstrap =
            $this->source(
                'bootstrap/app.php',
            );

        self::assertStringContainsString(
            'ShareMerchantPolicyStructuredData::class',
            $bootstrap,
        );

        self::assertStringContainsString(
            'AppendAiCrawlerPolicyToRobotsResponse::class',
            $bootstrap,
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
