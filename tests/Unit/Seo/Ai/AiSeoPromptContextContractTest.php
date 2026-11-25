<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai;

use App\Domain\Seo\Ai\Data\AiSeoEntitySnapshotData;
use App\Domain\Seo\Ai\Data\AiSeoPromptContextData;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Services\AiSeoPromptContextFactory;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiSeoPromptContextContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_prompt_contract_is_versioned_bounded_and_restricted_to_h1_fields(): void
    {
        app(
            SeoSettingsService::class,
        )->save(
            $this->settings(),
        );

        $context =
            app(
                AiSeoPromptContextFactory::class,
            )
                ->make(
                    new AiSeoEntitySnapshotData(
                        entityType: AiSeoEntityType::Product,

                        entityId: 10,

                        name: 'USB Cable',

                        slug: 'usb-cable',

                        content: [
                            'summary' => 'Fast cable.',

                            'body' => null,
                        ],

                        context: [
                            'brand' => 'Acme',

                            'categories' => [
                                'Cables',
                            ],
                        ],

                        currentSeo: [
                            'meta_title' => null,

                            'meta_description' => null,

                            'og_title' => null,

                            'og_description' => null,

                            'twitter_title' => null,

                            'twitter_description' => null,
                        ],
                    ),
                )
                ->toArray();

        self::assertSame(
            AiSeoPromptContextData::PROMPT_VERSION,
            $context['prompt_version'],
        );

        self::assertSame(
            'generate_metadata_suggestions',
            $context['task'],
        );

        self::assertSame(
            'MWT Store',
            $context['site']['name'],
        );

        self::assertSame(
            [
                'meta_title',
                'meta_description',
                'og_title',
                'og_description',
                'twitter_title',
                'twitter_description',
            ],
            array_keys(
                $context[
                    'target_fields'
                ],
            ),
        );

        self::assertSame(
            180,
            $context[
                'target_fields'
            ]['meta_title'][
                'max_length'
            ],
        );

        self::assertSame(
            320,
            $context[
                'target_fields'
            ]['meta_description'][
                'max_length'
            ],
        );

        $encoded =
            json_encode(
                $context,
                JSON_THROW_ON_ERROR,
            );

        self::assertStringNotContainsString(
            'canonical_url',
            $encoded,
        );

        self::assertStringNotContainsString(
            'robots_index',
            $encoded,
        );

        self::assertStringNotContainsString(
            'og_image',
            $encoded,
        );

        self::assertStringNotContainsString(
            'structured_data',
            $encoded,
        );
    }

    private function settings(): SeoSettingsData
    {
        return new SeoSettingsData(
            siteName: 'MWT Store',

            titleSeparator: '|',

            defaultTitleTemplate: '{page_title} {separator} {site_name}',

            defaultMetaDescription: null,

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
        );
    }
}
