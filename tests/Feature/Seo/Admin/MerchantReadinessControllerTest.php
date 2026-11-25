<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class MerchantReadinessControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://example.com',

            'commerce.currency' => 'BDT',
        ]);

        $this->saveSeoSettings();
    }

    public function test_authenticated_product_editor_can_view_merchant_readiness_workspace(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Editor,
                ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.seo.merchant.readiness.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Admin/Seo/Merchant/Readiness',
                    )
                    ->where(
                        'workspace.summary.item_candidates',
                        0,
                    )
                    ->where(
                        'workspace.pagination.total',
                        0,
                    )
                    ->where(
                        'workspace.pagination.per_page',
                        50,
                    )
                    ->where(
                        'workspace.filters.search',
                        '',
                    )
                    ->has(
                        'workspace.options.statuses',
                        3,
                    )
                    ->has(
                        'workspace.options.feed_states',
                        2,
                    )
                    ->has(
                        'workspace.policy',
                    ),
            );
    }

    public function test_guest_cannot_access_merchant_readiness_workspace(): void
    {
        $this
            ->get(
                route(
                    'admin.seo.merchant.readiness.index',
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );
    }

    public function test_invalid_readiness_filter_is_rejected_before_query_execution(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.seo.merchant.readiness.index',
                    [
                        'status' => 'invented-status',
                    ],
                ),
            )
            ->assertSessionHasErrors(
                'status',
            );
    }

    private function saveSeoSettings(): void
    {
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

                enableStructuredData: true,

                enableOpenGraph: true,

                enableTwitterCards: true,

                enableSitemap: true,
            ),
        );
    }
}
