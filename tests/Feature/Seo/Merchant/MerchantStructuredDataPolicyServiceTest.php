<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Merchant;

use App\Domain\Seo\Merchant\Data\MerchantStructuredDataPolicyData;
use App\Domain\Seo\Merchant\Enums\MerchantRefundType;
use App\Domain\Seo\Merchant\Enums\MerchantReturnFees;
use App\Domain\Seo\Merchant\Enums\MerchantReturnMethod;
use App\Domain\Seo\Merchant\Enums\MerchantReturnPolicyCategory;
use App\Domain\Seo\Merchant\Services\MerchantStructuredDataPolicyService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MerchantStructuredDataPolicyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_policy_is_safe_and_disabled(): void
    {
        $policy =
            app(
                MerchantStructuredDataPolicyService::class,
            )->get();

        self::assertFalse(
            $policy->shippingMarkupEnabled,
        );

        self::assertFalse(
            $policy->returnPolicyEnabled,
        );

        self::assertSame(
            [],
            $policy->shippingCountryCodes,
        );

        self::assertSame(
            [],
            $policy->returnApplicableCountryCodes,
        );

        self::assertSame(
            1,
            $policy->version,
        );

        self::assertDatabaseCount(
            'merchant_structured_data_policies',
            1,
        );

        self::assertDatabaseCount(
            'merchant_structured_data_policy_events',
            0,
        );
    }

    public function test_changed_policy_is_versioned_and_audited(): void
    {
        $service =
            app(
                MerchantStructuredDataPolicyService::class,
            );

        $initial =
            $service->get();

        $saved =
            $service->save(
                data: $this->policyData(
                    version: $initial->version,
                ),

                expectedVersion: $initial->version,

                changedByAdminId: 55,
            );

        self::assertTrue(
            $saved->shippingMarkupEnabled,
        );

        self::assertTrue(
            $saved->returnPolicyEnabled,
        );

        self::assertSame(
            2,
            $saved->version,
        );

        self::assertSame(
            [
                'BD',
            ],
            $saved->shippingCountryCodes,
        );

        self::assertSame(
            MerchantReturnPolicyCategory::Finite,
            $saved->returnPolicyCategory,
        );

        self::assertDatabaseHas(
            'merchant_structured_data_policy_events',
            [
                'version' => 2,

                'changed_by_admin_id' => 55,
            ],
        );
    }

    public function test_noop_save_does_not_increment_version_or_create_history(): void
    {
        $service =
            app(
                MerchantStructuredDataPolicyService::class,
            );

        $initial =
            $service->get();

        $same =
            new MerchantStructuredDataPolicyData(
                shippingMarkupEnabled: false,

                shippingCountryCodes: [],

                returnPolicyEnabled: false,

                returnApplicableCountryCodes: [],

                returnPolicyCategory: null,

                returnDays: null,

                returnPolicyUrl: null,

                returnCountryCode: null,

                returnMethod: null,

                returnFees: null,

                returnShippingFeeAmount: null,

                refundType: null,

                version: $initial->version,
            );

        $saved =
            $service->save(
                data: $same,

                expectedVersion: $initial->version,
            );

        self::assertSame(
            1,
            $saved->version,
        );

        self::assertDatabaseCount(
            'merchant_structured_data_policy_events',
            0,
        );
    }

    public function test_stale_version_cannot_overwrite_newer_policy(): void
    {
        $service =
            app(
                MerchantStructuredDataPolicyService::class,
            );

        $initial =
            $service->get();

        $service->save(
            data: $this->policyData(
                version: $initial->version,
            ),

            expectedVersion: $initial->version,
        );

        $this->expectException(
            DomainException::class,
        );

        try {
            $service->save(
                data: $this->policyData(
                    version: $initial->version,
                ),

                expectedVersion: $initial->version,
            );
        } finally {
            self::assertDatabaseCount(
                'merchant_structured_data_policy_events',
                1,
            );
        }
    }

    private function policyData(
        int $version,
    ): MerchantStructuredDataPolicyData {
        return new MerchantStructuredDataPolicyData(
            shippingMarkupEnabled: true,

            shippingCountryCodes: [
                'bd',
                'BD',
            ],

            returnPolicyEnabled: true,

            returnApplicableCountryCodes: [
                'bd',
            ],

            returnPolicyCategory: MerchantReturnPolicyCategory::Finite,

            returnDays: 7,

            returnPolicyUrl: 'https://example.com/pages/returns',

            returnCountryCode: 'bd',

            returnMethod: MerchantReturnMethod::ByMail,

            returnFees: MerchantReturnFees::Free,

            returnShippingFeeAmount: null,

            refundType: MerchantRefundType::Full,

            version: $version,
        );
    }
}
