<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Merchant;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Merchant\Data\MerchantStructuredDataPolicyData;
use App\Domain\Seo\Merchant\Enums\MerchantRefundType;
use App\Domain\Seo\Merchant\Enums\MerchantReturnFees;
use App\Domain\Seo\Merchant\Enums\MerchantReturnMethod;
use App\Domain\Seo\Merchant\Enums\MerchantReturnPolicyCategory;
use App\Domain\Seo\Merchant\Services\MerchantOrganizationPolicyStructuredDataResolver;
use App\Domain\Seo\Merchant\Services\MerchantStructuredDataPolicyService;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Domain\Shipping\Enums\ShippingZoneRuleType;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MerchantOrganizationPolicyStructuredDataResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'commerce.currency' => 'BDT',
        ]);

        $this->saveSeoSettings(
            enabled: true,
        );
    }

    public function test_country_only_checkout_shipping_and_return_policy_publish_on_same_organization_entity(): void
    {
        $zone =
            ShippingZone::factory()
                ->create([
                    'name' => 'Bangladesh',

                    'code' => 'bangladesh',
                ]);

        ShippingZoneRule::factory()
            ->for(
                $zone,
            )
            ->create([
                'type' => ShippingZoneRuleType::CountryCode,

                'value' => 'BD',
            ]);

        $method =
            ShippingMethod::factory()
                ->create([
                    'name' => 'Standard Delivery',
                ]);

        $rate =
            ShippingRate::factory()
                ->for(
                    $method,
                )
                ->create([
                    'amount' => 8000,

                    'min_order_amount' => 100000,

                    'max_order_amount' => 500000,
                ]);

        $rate
            ->zones()
            ->attach(
                $zone,
            );

        $this->saveMerchantPolicy(
            shipping: true,

            returns: true,
        );

        $document =
            $this->resolver()
                ->resolve();

        self::assertNotNull(
            $document,
        );

        $organization =
            $document[
                '@graph'
            ][
                0
            ];

        self::assertSame(
            'Organization',
            $organization[
                '@type'
            ],
        );

        self::assertSame(
            'https://example.com/#organization',
            $organization[
                '@id'
            ],
        );

        $shipping =
            $organization[
                'hasShippingService'
            ];

        self::assertSame(
            'ShippingService',
            $shipping[
                '@type'
            ],
        );

        self::assertSame(
            'Standard Delivery',
            $shipping[
                'name'
            ],
        );

        self::assertSame(
            'BD',
            $shipping[
                'shippingConditions'
            ][
                'shippingDestination'
            ][
                'addressCountry'
            ],
        );

        self::assertSame(
            '80.00',
            $shipping[
                'shippingConditions'
            ][
                'shippingRate'
            ][
                'value'
            ],
        );

        self::assertSame(
            'BDT',
            $shipping[
                'shippingConditions'
            ][
                'shippingRate'
            ][
                'currency'
            ],
        );

        self::assertSame(
            '1000.00',
            $shipping[
                'shippingConditions'
            ][
                'orderValue'
            ][
                'minValue'
            ],
        );

        self::assertSame(
            '5000.00',
            $shipping[
                'shippingConditions'
            ][
                'orderValue'
            ][
                'maxValue'
            ],
        );

        $returns =
            $organization[
                'hasMerchantReturnPolicy'
            ];

        self::assertSame(
            'MerchantReturnPolicy',
            $returns[
                '@type'
            ],
        );

        self::assertSame(
            'BD',
            $returns[
                'applicableCountry'
            ],
        );

        self::assertSame(
            'https://schema.org/MerchantReturnFiniteReturnWindow',
            $returns[
                'returnPolicyCategory'
            ],
        );

        self::assertSame(
            7,
            $returns[
                'merchantReturnDays'
            ],
        );

        self::assertSame(
            'https://schema.org/ReturnByMail',
            $returns[
                'returnMethod'
            ],
        );

        self::assertSame(
            'https://schema.org/FreeReturn',
            $returns[
                'returnFees'
            ],
        );

        self::assertSame(
            'https://schema.org/FullRefund',
            $returns[
                'refundType'
            ],
        );
    }

    public function test_free_shipping_threshold_is_split_without_changing_checkout_rate_truth(): void
    {
        $zone =
            ShippingZone::factory()
                ->create([
                    'name' => 'Bangladesh',

                    'code' => 'bd',
                ]);

        ShippingZoneRule::factory()
            ->for(
                $zone,
            )
            ->create([
                'type' => ShippingZoneRuleType::CountryCode,

                'value' => 'BD',
            ]);

        $rate =
            ShippingRate::factory()
                ->create([
                    'amount' => 8000,

                    'free_shipping_threshold' => 150000,
                ]);

        $rate
            ->zones()
            ->attach(
                $zone,
            );

        $this->saveMerchantPolicy(
            shipping: true,

            returns: false,
        );

        $document =
            $this->resolver()
                ->resolve();

        self::assertNotNull(
            $document,
        );

        $organization =
            $document[
                '@graph'
            ][
                0
            ];

        $conditions =
            $organization[
                'hasShippingService'
            ][
                'shippingConditions'
            ];

        self::assertCount(
            2,
            $conditions,
        );

        self::assertSame(
            '80.00',
            $conditions[
                0
            ][
                'shippingRate'
            ][
                'value'
            ],
        );

        self::assertSame(
            '1499.99',
            $conditions[
                0
            ][
                'orderValue'
            ][
                'maxValue'
            ],
        );

        self::assertSame(
            '0.00',
            $conditions[
                1
            ][
                'shippingRate'
            ][
                'value'
            ],
        );

        self::assertSame(
            '1500.00',
            $conditions[
                1
            ][
                'orderValue'
            ][
                'minValue'
            ],
        );
    }

    public function test_city_scoped_checkout_zone_suppresses_shipping_markup_but_keeps_return_policy(): void
    {
        $zone =
            ShippingZone::factory()
                ->create([
                    'name' => 'Dhaka',
                ]);

        ShippingZoneRule::factory()
            ->for(
                $zone,
            )
            ->create([
                'type' => ShippingZoneRuleType::CountryCode,

                'value' => 'BD',
            ]);

        ShippingZoneRule::factory()
            ->for(
                $zone,
            )
            ->create([
                'type' => ShippingZoneRuleType::City,

                'value' => 'Dhaka',
            ]);

        $rate =
            ShippingRate::factory()
                ->create([
                    'amount' => 80,
                ]);

        $rate
            ->zones()
            ->attach(
                $zone,
            );

        $this->saveMerchantPolicy(
            shipping: true,

            returns: true,
        );

        $resolver =
            $this->resolver();

        $document =
            $resolver->resolve();

        self::assertNotNull(
            $document,
        );

        $organization =
            $document[
                '@graph'
            ][
                0
            ];

        self::assertArrayNotHasKey(
            'hasShippingService',
            $organization,
        );

        self::assertArrayHasKey(
            'hasMerchantReturnPolicy',
            $organization,
        );

        self::assertNotSame(
            [],
            $resolver
                ->shippingOmissionReasons(),
        );
    }

    public function test_multiple_active_checkout_rates_in_one_zone_suppress_shipping_markup(): void
    {
        $zone =
            ShippingZone::factory()
                ->create();

        ShippingZoneRule::factory()
            ->for(
                $zone,
            )
            ->create([
                'type' => ShippingZoneRuleType::CountryCode,

                'value' => 'BD',
            ]);

        $first =
            ShippingRate::factory()
                ->create([
                    'priority' => 10,

                    'amount' => 80,
                ]);

        $second =
            ShippingRate::factory()
                ->create([
                    'priority' => 20,

                    'amount' => 120,
                ]);

        $first
            ->zones()
            ->attach(
                $zone,
            );

        $second
            ->zones()
            ->attach(
                $zone,
            );

        $this->saveMerchantPolicy(
            shipping: true,

            returns: false,
        );

        self::assertNull(
            $this->resolver()
                ->resolve(),
        );
    }

    public function test_no_returns_policy_suppresses_stale_return_mechanics(): void
    {
        $service =
            app(
                MerchantStructuredDataPolicyService::class,
            );

        $current =
            $service->get();

        $service->save(
            data: new MerchantStructuredDataPolicyData(
                shippingMarkupEnabled: false,

                shippingCountryCodes: [],

                returnPolicyEnabled: true,

                returnApplicableCountryCodes: [
                    'BD',
                ],

                returnPolicyCategory: MerchantReturnPolicyCategory::NotPermitted,

                returnDays: 30,

                returnPolicyUrl: 'https://example.com/pages/returns',

                returnCountryCode: 'BD',

                returnMethod: MerchantReturnMethod::ByMail,

                returnFees: MerchantReturnFees::ShippingFee,

                returnShippingFeeAmount: 8000,

                refundType: MerchantRefundType::Full,

                version: $current->version,
            ),

            expectedVersion: $current->version,
        );

        $document =
            $this->resolver()
                ->resolve();

        self::assertNotNull(
            $document,
        );

        $policy =
            $document[
                '@graph'
            ][
                0
            ][
                'hasMerchantReturnPolicy'
            ];

        self::assertSame(
            'https://schema.org/MerchantReturnNotPermitted',
            $policy[
                'returnPolicyCategory'
            ],
        );

        self::assertArrayNotHasKey(
            'merchantReturnDays',
            $policy,
        );

        self::assertArrayNotHasKey(
            'returnMethod',
            $policy,
        );

        self::assertArrayNotHasKey(
            'returnFees',
            $policy,
        );

        self::assertArrayNotHasKey(
            'returnShippingFeesAmount',
            $policy,
        );

        self::assertArrayNotHasKey(
            'refundType',
            $policy,
        );
    }

    public function test_structured_data_global_disable_wins_over_merchant_policy(): void
    {
        $this->saveSeoSettings(
            enabled: false,
        );

        $this->saveMerchantPolicy(
            shipping: false,

            returns: true,
        );

        self::assertNull(
            $this->resolver()
                ->resolve(),
        );
    }

    private function resolver(): MerchantOrganizationPolicyStructuredDataResolver
    {
        return app(
            MerchantOrganizationPolicyStructuredDataResolver::class,
        );
    }

    private function saveMerchantPolicy(
        bool $shipping,
        bool $returns,
    ): void {
        $service =
            app(
                MerchantStructuredDataPolicyService::class,
            );

        $current =
            $service->get();

        $service->save(
            data: new MerchantStructuredDataPolicyData(
                shippingMarkupEnabled: $shipping,

                shippingCountryCodes: [
                    'BD',
                ],

                returnPolicyEnabled: $returns,

                returnApplicableCountryCodes: $returns
                        ? [
                            'BD',
                        ]
                        : [],

                returnPolicyCategory: $returns
                        ? MerchantReturnPolicyCategory::Finite
                        : null,

                returnDays: $returns
                        ? 7
                        : null,

                returnPolicyUrl: $returns
                        ? 'https://example.com/pages/returns'
                        : null,

                returnCountryCode: $returns
                        ? 'BD'
                        : null,

                returnMethod: $returns
                        ? MerchantReturnMethod::ByMail
                        : null,

                returnFees: $returns
                        ? MerchantReturnFees::Free
                        : null,

                returnShippingFeeAmount: null,

                refundType: $returns
                        ? MerchantRefundType::Full
                        : null,

                version: $current->version,
            ),

            expectedVersion: $current->version,
        );
    }

    private function saveSeoSettings(
        bool $enabled,
    ): void {
        app(
            SeoSettingsService::class,
        )->save(
            new SeoSettingsData(
                siteName: 'MWT Store',

                titleSeparator: '|',

                defaultTitleTemplate: '{page_title} {separator} {site_name}',

                defaultMetaDescription: 'Default store description.',

                canonicalHost: 'https://example.com',

                defaultRobotsIndex: true,

                defaultRobotsFollow: true,

                defaultOgImage: null,

                twitterCard: 'summary_large_image',

                organizationName: 'MWT Store',

                organizationLogo: null,

                organizationSocialProfiles: [],

                googleSiteVerification: null,

                bingSiteVerification: null,

                enableStructuredData: $enabled,

                enableOpenGraph: true,

                enableTwitterCards: true,

                enableSitemap: true,
            ),
        );
    }
}
