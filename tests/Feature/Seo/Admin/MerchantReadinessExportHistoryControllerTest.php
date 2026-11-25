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

final class MerchantReadinessExportHistoryControllerTest extends TestCase
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

    public function test_readiness_workspace_includes_bounded_export_history_contract(): void
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
                        'exports.pagination.total',
                        0,
                    )
                    ->where(
                        'exports.pagination.per_page',
                        10,
                    )
                    ->has(
                        'exports.items',
                        0,
                    ),
            );
    }

    public function test_invalid_export_page_is_rejected(): void
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
                        'export_page' => 0,
                    ],
                ),
            )
            ->assertSessionHasErrors(
                'export_page',
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
