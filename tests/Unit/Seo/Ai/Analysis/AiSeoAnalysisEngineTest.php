<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai\Analysis;

use App\Domain\Seo\Ai\Analysis\Data\AiSeoAnalysisFindingData;
use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisGrade;
use App\Domain\Seo\Ai\Analysis\Services\AiSeoAnalysisEngine;
use App\Domain\Seo\Ai\Analysis\Services\AiSeoAnalysisPolicy;
use App\Domain\Seo\Ai\Data\AiSeoEntitySnapshotData;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Data\ResolvedSeoData;
use PHPUnit\Framework\TestCase;

final class AiSeoAnalysisEngineTest extends TestCase
{
    private AiSeoAnalysisEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine =
            new AiSeoAnalysisEngine(
                new AiSeoAnalysisPolicy,
            );
    }

    public function test_well_formed_resolved_seo_and_source_content_score_one_hundred(): void
    {
        $result =
            $this->engine
                ->analyze(
                    $this->snapshot(
                        str_repeat(
                            'Useful factual product content. ',
                            12,
                        ),
                    ),
                    $this->seo(
                        title: 'Braided USB Charging Cable for Everyday Use',

                        description: 'Shop a reliable braided USB charging cable designed for everyday device charging, easy storage, and practical daily use.',

                        ogTitle: 'Braided USB Charging Cable for Everyday Use',

                        ogDescription: 'Reliable braided USB charging cable for practical everyday charging.',

                        ogImage: 'https://example.com/cable.jpg',

                        twitterTitle: 'Braided USB Charging Cable for Everyday Use',

                        twitterDescription: 'Reliable braided USB charging cable for practical everyday charging.',

                        twitterImage: 'https://example.com/cable.jpg',
                    ),
                );

        self::assertSame(
            100,
            $result->score,
        );

        self::assertSame(
            AiSeoAnalysisGrade::Excellent,
            $result->grade,
        );

        self::assertSame(
            50,
            $result->breakdown[
                'search_metadata'
            ]['points'],
        );

        self::assertSame(
            25,
            $result->breakdown[
                'source_content'
            ]['points'],
        );

        self::assertSame(
            25,
            $result->breakdown[
                'social_coverage'
            ]['points'],
        );

        self::assertSame(
            [],
            $result->findings,
        );
    }

    public function test_missing_description_thin_content_and_missing_social_image_reduce_score_deterministically(): void
    {
        $result =
            $this->engine
                ->analyze(
                    $this->snapshot(
                        'Short content.',
                    ),
                    $this->seo(
                        title: 'Useful Product Title for Search',

                        description: null,

                        ogTitle: 'Useful Product Title for Search',

                        ogDescription: null,

                        ogImage: null,

                        twitterTitle: 'Useful Product Title for Search',

                        twitterDescription: null,

                        twitterImage: null,
                    ),
                );

        self::assertSame(
            52,
            $result->score,
        );

        self::assertSame(
            AiSeoAnalysisGrade::NeedsWork,
            $result->grade,
        );

        $ids =
            array_map(
                static fn (
                    AiSeoAnalysisFindingData $finding,
                ): string => $finding->id,
                $result->findings,
            );

        self::assertContains(
            'missing_resolved_description',
            $ids,
        );

        self::assertContains(
            'source_content_is_thin',
            $ids,
        );

        self::assertContains(
            'social_image_missing',
            $ids,
        );
    }

    public function test_inherited_social_metadata_is_scored_from_resolved_output_not_override_presence(): void
    {
        $result =
            $this->engine
                ->analyze(
                    $this->snapshot(
                        str_repeat(
                            'Factual content. ',
                            20,
                        ),
                    ),
                    $this->seo(
                        title: 'Resolved Product Title for Search',

                        description: 'This resolved description comes from the authoritative fallback chain and remains valid for search and social presentation.',

                        ogTitle: 'Resolved Product Title for Search',

                        ogDescription: 'This resolved description comes from the authoritative fallback chain and remains valid for search and social presentation.',

                        ogImage: 'https://example.com/default-social.jpg',

                        twitterTitle: 'Resolved Product Title for Search',

                        twitterDescription: 'This resolved description comes from the authoritative fallback chain and remains valid for search and social presentation.',

                        twitterImage: 'https://example.com/default-social.jpg',
                    ),
                );

        self::assertSame(
            100,
            $result->score,
        );
    }

    public function test_noindex_and_nofollow_are_reported_without_score_penalty(): void
    {
        $result =
            $this->engine
                ->analyze(
                    $this->snapshot(
                        str_repeat(
                            'Factual content. ',
                            20,
                        ),
                    ),
                    $this->seo(
                        title: 'Resolved Product Title for Search',

                        description: 'This resolved description contains enough accurate detail for the configured internal scoring policy and social fallbacks.',

                        ogTitle: 'Resolved Product Title for Search',

                        ogDescription: 'This resolved description contains enough accurate detail for the configured internal scoring policy and social fallbacks.',

                        ogImage: 'https://example.com/social.jpg',

                        twitterTitle: 'Resolved Product Title for Search',

                        twitterDescription: 'This resolved description contains enough accurate detail for the configured internal scoring policy and social fallbacks.',

                        twitterImage: 'https://example.com/social.jpg',

                        robotsIndex: false,

                        robotsFollow: false,
                    ),
                );

        self::assertSame(
            100,
            $result->score,
        );

        $ids =
            array_map(
                static fn (
                    AiSeoAnalysisFindingData $finding,
                ): string => $finding->id,
                $result->findings,
            );

        self::assertContains(
            'resolved_noindex_notice',
            $ids,
        );

        self::assertContains(
            'resolved_nofollow_notice',
            $ids,
        );
    }

    public function test_extreme_title_and_description_lengths_receive_partial_points(): void
    {
        $result =
            $this->engine
                ->analyze(
                    $this->snapshot(
                        str_repeat(
                            'Factual content. ',
                            20,
                        ),
                    ),
                    $this->seo(
                        title: 'Tiny',

                        description: 'Too short.',

                        ogTitle: 'Tiny',

                        ogDescription: 'Too short.',

                        ogImage: 'https://example.com/social.jpg',

                        twitterTitle: 'Tiny',

                        twitterDescription: 'Too short.',

                        twitterImage: 'https://example.com/social.jpg',
                    ),
                );

        self::assertSame(
            84,
            $result->score,
        );

        self::assertSame(
            AiSeoAnalysisGrade::Good,
            $result->grade,
        );
    }

    private function snapshot(
        string $body,
    ): AiSeoEntitySnapshotData {
        return new AiSeoEntitySnapshotData(
            entityType: AiSeoEntityType::Product,

            entityId: 10,

            name: 'USB Cable',

            slug: 'usb-cable',

            content: [
                'summary' => null,

                'body' => $body,
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
        );
    }

    private function seo(
        string $title,
        ?string $description,
        string $ogTitle,
        ?string $ogDescription,
        ?string $ogImage,
        string $twitterTitle,
        ?string $twitterDescription,
        ?string $twitterImage,
        bool $robotsIndex = true,
        bool $robotsFollow = true,
    ): ResolvedSeoData {
        return new ResolvedSeoData(
            title: $title,

            description: $description,

            canonicalOverride: null,

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
}
