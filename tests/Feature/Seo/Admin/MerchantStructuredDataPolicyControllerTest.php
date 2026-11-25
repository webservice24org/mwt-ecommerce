<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Domain\Seo\Merchant\Services\MerchantStructuredDataPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class MerchantStructuredDataPolicyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_center_renders_safe_default_state(): void
    {
        $this->withoutMiddleware();

        $this
            ->get(
                route(
                    'admin.seo.merchant.policies.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Admin/Seo/Merchant/Policies',
                    )
                    ->where(
                        'policy.shipping_markup_enabled',
                        false,
                    )
                    ->where(
                        'policy.return_policy_enabled',
                        false,
                    )
                    ->where(
                        'policy.version',
                        1,
                    )
                    ->where(
                        'structuredDataPreview',
                        null,
                    )
                    ->has(
                        'shippingOmissionReasons',
                    )
                    ->has(
                        'options.return_categories',
                    )
                    ->has(
                        'options.return_methods',
                    )
                    ->has(
                        'options.return_fees',
                    )
                    ->has(
                        'options.refund_types',
                    ),
            );
    }

    public function test_admin_can_save_explicit_return_policy_without_enabling_shipping_markup(): void
    {
        $this->withoutMiddleware();

        $this
            ->put(
                route(
                    'admin.seo.merchant.policies.update',
                ),
                [
                    'shipping_markup_enabled' => false,

                    'shipping_country_codes' => [],

                    'return_policy_enabled' => true,

                    'return_applicable_country_codes' => [
                        'BD',
                    ],

                    'return_policy_category' => 'finite',

                    'return_days' => 7,

                    'return_policy_url' => 'https://example.com/pages/returns',

                    'return_country_code' => 'BD',

                    'return_method' => 'by_mail',

                    'return_fees' => 'free',

                    'return_shipping_fee_amount' => null,

                    'refund_type' => 'full',

                    'version' => 1,
                ],
            )
            ->assertRedirect()
            ->assertSessionHas(
                'success',
            );

        $policy =
            app(
                MerchantStructuredDataPolicyService::class,
            )->get();

        self::assertFalse(
            $policy->shippingMarkupEnabled,
        );

        self::assertTrue(
            $policy->returnPolicyEnabled,
        );

        self::assertSame(
            7,
            $policy->returnDays,
        );

        self::assertSame(
            2,
            $policy->version,
        );
    }

    public function test_finite_return_policy_requires_explicit_return_days(): void
    {
        $this->withoutMiddleware();

        $this
            ->from(
                route(
                    'admin.seo.merchant.policies.index',
                ),
            )
            ->put(
                route(
                    'admin.seo.merchant.policies.update',
                ),
                [
                    'shipping_markup_enabled' => false,

                    'shipping_country_codes' => [],

                    'return_policy_enabled' => true,

                    'return_applicable_country_codes' => [
                        'BD',
                    ],

                    'return_policy_category' => 'finite',

                    'return_days' => null,

                    'return_country_code' => 'BD',

                    'return_method' => 'by_mail',

                    'return_fees' => 'free',

                    'refund_type' => 'full',

                    'version' => 1,
                ],
            )
            ->assertRedirect(
                route(
                    'admin.seo.merchant.policies.index',
                ),
            )
            ->assertSessionHasErrors(
                'return_days',
            );

        self::assertDatabaseCount(
            'merchant_structured_data_policy_events',
            0,
        );
    }
}
