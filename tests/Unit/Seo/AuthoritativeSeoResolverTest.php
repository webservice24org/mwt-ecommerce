<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use App\Domain\Seo\Data\EntitySeoData;
use App\Domain\Seo\Data\SeoEntitySourceData;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\AuthoritativeSeoResolver;
use App\Domain\Seo\Services\SeoTitleTemplateResolver;
use PHPUnit\Framework\TestCase;

final class AuthoritativeSeoResolverTest extends TestCase
{
    private AuthoritativeSeoResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver =
            new AuthoritativeSeoResolver(
                new SeoTitleTemplateResolver,
            );
    }

    public function test_explicit_meta_title_wins_over_entity_page_title(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    pageTitle: 'Product Name',

                    metaTitle: 'Custom Product SEO Title',
                ),
                $this->settings(),
            );

        $this->assertSame(
            'Custom Product SEO Title | MWT Store',
            $resolved->title,
        );

        $this->assertSame(
            'Custom Product SEO Title | MWT Store',
            $resolved->ogTitle,
        );

        $this->assertSame(
            'Custom Product SEO Title | MWT Store',
            $resolved->twitterTitle,
        );
    }

    public function test_entity_page_title_is_used_when_meta_title_is_missing(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    pageTitle: 'Premium Laptop',
                ),
                $this->settings(),
            );

        $this->assertSame(
            'Premium Laptop | MWT Store',
            $resolved->title,
        );
    }

    public function test_meta_description_wins_over_entity_and_global_fallbacks(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    metaDescription: 'Explicit SEO description.',

                    fallbackDescription: 'Product short description.',
                ),
                $this->settings(
                    defaultMetaDescription: 'Global default description.',
                ),
            );

        $this->assertSame(
            'Explicit SEO description.',
            $resolved->description,
        );

        $this->assertSame(
            'Explicit SEO description.',
            $resolved->ogDescription,
        );

        $this->assertSame(
            'Explicit SEO description.',
            $resolved->twitterDescription,
        );
    }

    public function test_entity_description_is_used_before_global_description(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    fallbackDescription: 'Entity fallback description.',
                ),
                $this->settings(
                    defaultMetaDescription: 'Global default description.',
                ),
            );

        $this->assertSame(
            'Entity fallback description.',
            $resolved->description,
        );
    }

    public function test_global_description_is_used_when_entity_descriptions_are_missing(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(),
                $this->settings(
                    defaultMetaDescription: 'Global default description.',
                ),
            );

        $this->assertSame(
            'Global default description.',
            $resolved->description,
        );

        $this->assertSame(
            'Global default description.',
            $resolved->ogDescription,
        );

        $this->assertSame(
            'Global default description.',
            $resolved->twitterDescription,
        );
    }

    public function test_null_robot_values_inherit_global_settings(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    overrides: $this->overrides(
                        robotsIndex: null,

                        robotsFollow: null,
                    ),
                ),
                $this->settings(
                    defaultRobotsIndex: false,

                    defaultRobotsFollow: true,
                ),
            );

        $this->assertFalse(
            $resolved->robotsIndex,
        );

        $this->assertTrue(
            $resolved->robotsFollow,
        );
    }

    public function test_explicit_false_robots_override_global_true(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    overrides: $this->overrides(
                        robotsIndex: false,

                        robotsFollow: false,
                    ),
                ),
                $this->settings(
                    defaultRobotsIndex: true,

                    defaultRobotsFollow: true,
                ),
            );

        $this->assertFalse(
            $resolved->robotsIndex,
        );

        $this->assertFalse(
            $resolved->robotsFollow,
        );
    }

    public function test_explicit_true_robots_override_global_false(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    overrides: $this->overrides(
                        robotsIndex: true,

                        robotsFollow: true,
                    ),
                ),
                $this->settings(
                    defaultRobotsIndex: false,

                    defaultRobotsFollow: false,
                ),
            );

        $this->assertTrue(
            $resolved->robotsIndex,
        );

        $this->assertTrue(
            $resolved->robotsFollow,
        );
    }

    public function test_open_graph_overrides_win_over_resolved_base_metadata(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    metaTitle: 'Search Title',

                    metaDescription: 'Search description.',

                    overrides: $this->overrides(
                        ogTitle: 'Custom Open Graph Title',

                        ogDescription: 'Custom Open Graph description.',
                    ),
                ),
                $this->settings(),
            );

        $this->assertSame(
            'Search Title | MWT Store',
            $resolved->title,
        );

        $this->assertSame(
            'Custom Open Graph Title',
            $resolved->ogTitle,
        );

        $this->assertSame(
            'Custom Open Graph description.',
            $resolved->ogDescription,
        );
    }

    public function test_entity_image_is_used_when_open_graph_override_is_missing(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    fallbackImage: 'https://example.com/storage/products/product.jpg',
                ),
                $this->settings(
                    defaultOgImage: 'https://example.com/storage/global-og.jpg',
                ),
            );

        $this->assertSame(
            'https://example.com/storage/products/product.jpg',
            $resolved->ogImage,
        );

        $this->assertSame(
            'https://example.com/storage/products/product.jpg',
            $resolved->twitterImage,
        );
    }

    public function test_global_open_graph_image_is_used_when_entity_image_is_missing(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(),
                $this->settings(
                    defaultOgImage: 'https://example.com/storage/global-og.jpg',
                ),
            );

        $this->assertSame(
            'https://example.com/storage/global-og.jpg',
            $resolved->ogImage,
        );

        $this->assertSame(
            'https://example.com/storage/global-og.jpg',
            $resolved->twitterImage,
        );
    }

    public function test_explicit_open_graph_image_wins_over_entity_and_global_images(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    fallbackImage: 'https://example.com/entity.jpg',

                    overrides: $this->overrides(
                        ogImage: 'https://example.com/custom-og.jpg',
                    ),
                ),
                $this->settings(
                    defaultOgImage: 'https://example.com/global.jpg',
                ),
            );

        $this->assertSame(
            'https://example.com/custom-og.jpg',
            $resolved->ogImage,
        );

        $this->assertSame(
            'https://example.com/custom-og.jpg',
            $resolved->twitterImage,
        );
    }

    public function test_explicit_twitter_values_win_over_open_graph_fallbacks(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    overrides: $this->overrides(
                        ogTitle: 'OG Title',

                        ogDescription: 'OG description.',

                        ogImage: 'https://example.com/og.jpg',

                        twitterTitle: 'Twitter Title',

                        twitterDescription: 'Twitter description.',

                        twitterImage: 'https://example.com/twitter.jpg',
                    ),
                ),
                $this->settings(),
            );

        $this->assertSame(
            'OG Title',
            $resolved->ogTitle,
        );

        $this->assertSame(
            'Twitter Title',
            $resolved->twitterTitle,
        );

        $this->assertSame(
            'Twitter description.',
            $resolved->twitterDescription,
        );

        $this->assertSame(
            'https://example.com/twitter.jpg',
            $resolved->twitterImage,
        );
    }

    public function test_twitter_metadata_falls_back_to_open_graph_metadata(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    overrides: $this->overrides(
                        ogTitle: 'Open Graph Title',

                        ogDescription: 'Open Graph description.',

                        ogImage: 'https://example.com/og.jpg',

                        twitterTitle: null,

                        twitterDescription: null,

                        twitterImage: null,
                    ),
                ),
                $this->settings(),
            );

        $this->assertSame(
            'Open Graph Title',
            $resolved->twitterTitle,
        );

        $this->assertSame(
            'Open Graph description.',
            $resolved->twitterDescription,
        );

        $this->assertSame(
            'https://example.com/og.jpg',
            $resolved->twitterImage,
        );
    }

    public function test_canonical_override_is_forwarded_without_generating_final_canonical(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    overrides: $this->overrides(
                        canonicalUrl: 'https://example.com/canonical-product',
                    ),
                ),
                $this->settings(),
            );

        $this->assertSame(
            'https://example.com/canonical-product',
            $resolved->canonicalOverride,
        );
    }

    public function test_blank_canonical_override_becomes_null(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    overrides: $this->overrides(
                        canonicalUrl: '   ',
                    ),
                ),
                $this->settings(),
            );

        $this->assertNull(
            $resolved->canonicalOverride,
        );
    }

    public function test_blank_optional_values_are_skipped_during_resolution(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    pageTitle: 'Fallback Product',

                    metaTitle: '   ',

                    metaDescription: '  ',

                    fallbackDescription: 'Fallback description.',

                    overrides: $this->overrides(
                        ogTitle: '  ',

                        ogDescription: '  ',

                        twitterTitle: '  ',

                        twitterDescription: '  ',
                    ),
                ),
                $this->settings(),
            );

        $this->assertSame(
            'Fallback Product | MWT Store',
            $resolved->title,
        );

        $this->assertSame(
            'Fallback description.',
            $resolved->description,
        );

        $this->assertSame(
            'Fallback Product | MWT Store',
            $resolved->ogTitle,
        );

        $this->assertSame(
            'Fallback description.',
            $resolved->ogDescription,
        );

        $this->assertSame(
            'Fallback Product | MWT Store',
            $resolved->twitterTitle,
        );

        $this->assertSame(
            'Fallback description.',
            $resolved->twitterDescription,
        );
    }

    public function test_title_template_respects_configured_separator_and_site_name(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    pageTitle: 'About Us',
                ),
                $this->settings(
                    siteName: 'MicroWeb Technology',

                    titleSeparator: '—',

                    defaultTitleTemplate: '{page_title} {separator} {site_name}',
                ),
            );

        $this->assertSame(
            'About Us — MicroWeb Technology',
            $resolved->title,
        );
    }

    private function source(
        string $pageTitle = 'Test Product',
        ?string $metaTitle = null,
        ?string $metaDescription = null,
        ?string $fallbackDescription = null,
        ?string $fallbackImage = null,
        ?EntitySeoData $overrides = null,
    ): SeoEntitySourceData {
        return new SeoEntitySourceData(
            pageTitle: $pageTitle,

            metaTitle: $metaTitle,

            metaDescription: $metaDescription,

            fallbackDescription: $fallbackDescription,

            fallbackImage: $fallbackImage,

            overrides: $overrides ??
            $this->overrides(),
        );
    }

    private function overrides(
        ?string $canonicalUrl = null,
        ?bool $robotsIndex = null,
        ?bool $robotsFollow = null,
        ?string $ogTitle = null,
        ?string $ogDescription = null,
        ?string $ogImage = null,
        ?string $twitterTitle = null,
        ?string $twitterDescription = null,
        ?string $twitterImage = null,
    ): EntitySeoData {
        return new EntitySeoData(
            title: null,

            description: null,

            canonicalUrl: $canonicalUrl,

            robotsIndex: $robotsIndex,

            robotsFollow: $robotsFollow,

            ogTitle: $ogTitle,

            ogDescription: $ogDescription,

            ogImage: $ogImage,

            twitterTitle: $twitterTitle,

            twitterDescription: $twitterDescription,

            twitterImage: $twitterImage,
        );
    }

    /**
     * @param  array<int, string>  $organizationSocialProfiles
     */
    private function settings(
        ?string $siteName = 'MWT Store',
        string $titleSeparator = '|',
        string $defaultTitleTemplate = '{page_title} {separator} {site_name}',
        ?string $defaultMetaDescription = 'Default store description.',
        ?string $canonicalHost = 'https://example.com',
        bool $defaultRobotsIndex = true,
        bool $defaultRobotsFollow = true,
        ?string $defaultOgImage = null,
        string $twitterCard = 'summary_large_image',
        ?string $organizationName = 'MWT Store',
        ?string $organizationLogo = null,
        array $organizationSocialProfiles = [],
        ?string $googleSiteVerification = null,
        ?string $bingSiteVerification = null,
        bool $enableStructuredData = true,
        bool $enableOpenGraph = true,
        bool $enableTwitterCards = true,
        bool $enableSitemap = true,
    ): SeoSettingsData {
        return new SeoSettingsData(
            siteName: $siteName,

            titleSeparator: $titleSeparator,

            defaultTitleTemplate: $defaultTitleTemplate,

            defaultMetaDescription: $defaultMetaDescription,

            canonicalHost: $canonicalHost,

            defaultRobotsIndex: $defaultRobotsIndex,

            defaultRobotsFollow: $defaultRobotsFollow,

            defaultOgImage: $defaultOgImage,

            twitterCard: $twitterCard,

            organizationName: $organizationName,

            organizationLogo: $organizationLogo,

            organizationSocialProfiles: $organizationSocialProfiles,

            googleSiteVerification: $googleSiteVerification,

            bingSiteVerification: $bingSiteVerification,

            enableStructuredData: $enableStructuredData,

            enableOpenGraph: $enableOpenGraph,

            enableTwitterCards: $enableTwitterCards,

            enableSitemap: $enableSitemap,
        );
    }

    public function test_open_graph_title_falls_back_to_resolved_page_title(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    pageTitle: 'Gaming Laptop',
                ),
                $this->settings(),
            );

        $this->assertSame(
            'Gaming Laptop | MWT Store',
            $resolved->ogTitle,
        );
    }

    public function test_open_graph_description_falls_back_to_resolved_description(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    fallbackDescription: 'Gaming laptop description.',
                ),
                $this->settings(),
            );

        $this->assertSame(
            'Gaming laptop description.',
            $resolved->ogDescription,
        );
    }

    public function test_twitter_title_falls_back_to_explicit_open_graph_title(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    overrides: $this->overrides(
                        ogTitle: 'Custom OG Title',
                    ),
                ),
                $this->settings(),
            );

        $this->assertSame(
            'Custom OG Title',
            $resolved->twitterTitle,
        );
    }

    public function test_twitter_description_falls_back_to_explicit_open_graph_description(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    overrides: $this->overrides(
                        ogDescription: 'Custom OG Description',
                    ),
                ),
                $this->settings(),
            );

        $this->assertSame(
            'Custom OG Description',
            $resolved->twitterDescription,
        );
    }

    public function test_twitter_image_prefers_explicit_twitter_image_over_open_graph_image(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    overrides: $this->overrides(
                        ogImage: 'https://example.com/og.jpg',

                        twitterImage: 'https://example.com/twitter.jpg',
                    ),
                ),
                $this->settings(),
            );

        $this->assertSame(
            'https://example.com/twitter.jpg',
            $resolved->twitterImage,
        );
    }

    public function test_twitter_image_falls_back_to_open_graph_image(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    overrides: $this->overrides(
                        ogImage: 'https://example.com/og.jpg',
                    ),
                ),
                $this->settings(),
            );

        $this->assertSame(
            'https://example.com/og.jpg',
            $resolved->twitterImage,
        );
    }

    public function test_entity_image_wins_over_global_open_graph_image(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    fallbackImage: 'https://example.com/entity.jpg',
                ),
                $this->settings(
                    defaultOgImage: 'https://example.com/global.jpg',
                ),
            );

        $this->assertSame(
            'https://example.com/entity.jpg',
            $resolved->ogImage,
        );
    }

    public function test_explicit_open_graph_image_wins_over_entity_image(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    fallbackImage: 'https://example.com/entity.jpg',

                    overrides: $this->overrides(
                        ogImage: 'https://example.com/custom-og.jpg',
                    ),
                ),
                $this->settings(
                    defaultOgImage: 'https://example.com/global.jpg',
                ),
            );

        $this->assertSame(
            'https://example.com/custom-og.jpg',
            $resolved->ogImage,
        );
    }

    public function test_global_open_graph_image_is_last_image_fallback(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(),
                $this->settings(
                    defaultOgImage: 'https://example.com/global.jpg',
                ),
            );

        $this->assertSame(
            'https://example.com/global.jpg',
            $resolved->ogImage,
        );

        $this->assertSame(
            'https://example.com/global.jpg',
            $resolved->twitterImage,
        );
    }

    public function test_no_image_sources_results_in_null_social_images(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(),
                $this->settings(
                    defaultOgImage: null,
                ),
            );

        $this->assertNull(
            $resolved->ogImage,
        );

        $this->assertNull(
            $resolved->twitterImage,
        );
    }

    public function test_title_template_can_use_site_name_without_separator(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    pageTitle: 'About Us',
                ),
                $this->settings(
                    siteName: 'MicroWeb Technology',

                    defaultTitleTemplate: '{page_title} - {site_name}',
                ),
            );

        $this->assertSame(
            'About Us - MicroWeb Technology',
            $resolved->title,
        );
    }

    public function test_title_template_normalizes_extra_whitespace(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    pageTitle: 'About Us',
                ),
                $this->settings(
                    siteName: 'MWT',

                    defaultTitleTemplate: '  {page_title}   {separator}   {site_name}  ',
                ),
            );

        $this->assertSame(
            'About Us | MWT',
            $resolved->title,
        );
    }

    public function test_empty_site_name_does_not_leave_repeated_whitespace(): void
    {
        $resolved =
            $this->resolver->resolve(
                $this->source(
                    pageTitle: 'About Us',
                ),
                $this->settings(
                    siteName: null,
                ),
            );

        $this->assertSame(
            'About Us',
            $resolved->title,
        );
    }
}
