<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Services;

use App\Domain\Seo\Ai\Analysis\Data\AiSeoAnalysisFindingData;
use App\Domain\Seo\Ai\Analysis\Data\AiSeoAnalysisResultData;
use App\Domain\Seo\Ai\Analysis\Data\AiSeoAnalysisRuleResultData;
use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisCategory;
use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisSeverity;
use App\Domain\Seo\Ai\Data\AiSeoEntitySnapshotData;
use App\Domain\Seo\Data\ResolvedSeoData;

final readonly class AiSeoAnalysisEngine
{
    public function __construct(
        private AiSeoAnalysisPolicy $policy,
    ) {}

    public function analyze(
        AiSeoEntitySnapshotData $snapshot,
        ResolvedSeoData $seo,
    ): AiSeoAnalysisResultData {
        $rules = [
            $this->titlePresence(
                $seo,
            ),

            $this->titleLength(
                $seo,
            ),

            $this->descriptionPresence(
                $seo,
            ),

            $this->descriptionLength(
                $seo,
            ),

            $this->contentPresence(
                $snapshot,
            ),

            $this->contentDepth(
                $snapshot,
            ),

            $this->socialTextPresence(
                ruleId: 'social.og_title',

                value: $seo->ogTitle,

                label: 'Open Graph title',

                recommendation: 'Ensure the authoritative SEO resolver can produce an Open Graph title.',

                maxPoints: 5,
            ),

            $this->socialTextPresence(
                ruleId: 'social.og_description',

                value: $seo->ogDescription,

                label: 'Open Graph description',

                recommendation: 'Provide enough descriptive source/meta content for an Open Graph description.',

                maxPoints: 5,
            ),

            $this->socialTextPresence(
                ruleId: 'social.twitter_title',

                value: $seo->twitterTitle,

                label: 'X/Twitter title',

                recommendation: 'Ensure the authoritative SEO resolver can produce an X/Twitter title.',

                maxPoints: 5,
            ),

            $this->socialTextPresence(
                ruleId: 'social.twitter_description',

                value: $seo->twitterDescription,

                label: 'X/Twitter description',

                recommendation: 'Provide enough descriptive source/meta content for an X/Twitter description.',

                maxPoints: 5,
            ),

            $this->socialImagePresence(
                $seo,
            ),
        ];

        $breakdown =
            $this->breakdown(
                $rules,
            );

        $score =
            array_sum(
                array_map(
                    static fn (
                        AiSeoAnalysisRuleResultData $rule,
                    ): int => $rule->points,
                    $rules,
                ),
            );

        $findings = [];

        foreach ($rules as $rule) {
            if (
                $rule->finding
                !== null
            ) {
                $findings[] =
                    $rule->finding;
            }
        }

        foreach (
            $this->indexabilityFindings(
                $seo,
            ) as $finding
        ) {
            $findings[] =
                $finding;
        }

        return new AiSeoAnalysisResultData(
            version: AiSeoAnalysisPolicy::VERSION,

            score: $score,

            grade: $this
                ->policy
                ->grade(
                    $score,
                ),

            breakdown: $breakdown,

            rules: $rules,

            findings: $findings,
        );
    }

    private function titlePresence(
        ResolvedSeoData $seo,
    ): AiSeoAnalysisRuleResultData {
        $value =
            trim(
                $seo->title,
            );

        if ($value !== '') {
            return $this->rule(
                'search.title_presence',
                AiSeoAnalysisCategory::SearchMetadata,
                15,
                15,
            );
        }

        return $this->rule(
            'search.title_presence',
            AiSeoAnalysisCategory::SearchMetadata,
            0,
            15,
            new AiSeoAnalysisFindingData(
                id: 'missing_resolved_title',

                category: AiSeoAnalysisCategory::SearchMetadata,

                severity: AiSeoAnalysisSeverity::Critical,

                title: 'Resolved SEO title is missing',

                message: 'The authoritative SEO resolver produced an empty title.',

                recommendation: 'Provide an entity title or meta title so the storefront resolves a meaningful search title.',
            ),
        );
    }

    private function titleLength(
        ResolvedSeoData $seo,
    ): AiSeoAnalysisRuleResultData {
        $value =
            trim(
                $seo->title,
            );

        if ($value === '') {
            return $this->rule(
                'search.title_length',
                AiSeoAnalysisCategory::SearchMetadata,
                0,
                10,
            );
        }

        $length =
            mb_strlen(
                $value,
            );

        if (
            $length >=
                AiSeoAnalysisPolicy::TITLE_IDEAL_MIN
            && $length <=
                AiSeoAnalysisPolicy::TITLE_IDEAL_MAX
        ) {
            return $this->rule(
                'search.title_length',
                AiSeoAnalysisCategory::SearchMetadata,
                10,
                10,
            );
        }

        if (
            $length >=
                AiSeoAnalysisPolicy::TITLE_ACCEPTABLE_MIN
            && $length <=
                AiSeoAnalysisPolicy::TITLE_ACCEPTABLE_MAX
        ) {
            return $this->rule(
                'search.title_length',
                AiSeoAnalysisCategory::SearchMetadata,
                6,
                10,
                new AiSeoAnalysisFindingData(
                    id: 'title_length_can_improve',

                    category: AiSeoAnalysisCategory::SearchMetadata,

                    severity: AiSeoAnalysisSeverity::Info,

                    title: 'Search title length can improve',

                    message: sprintf(
                        'Resolved title length is %d characters. The internal preferred band is %d–%d.',
                        $length,
                        AiSeoAnalysisPolicy::TITLE_IDEAL_MIN,
                        AiSeoAnalysisPolicy::TITLE_IDEAL_MAX,
                    ),

                    recommendation: 'Refine the title for concise, descriptive search presentation without changing factual meaning.',
                ),
            );
        }

        return $this->rule(
            'search.title_length',
            AiSeoAnalysisCategory::SearchMetadata,
            2,
            10,
            new AiSeoAnalysisFindingData(
                id: 'title_length_outside_preferred_range',

                category: AiSeoAnalysisCategory::SearchMetadata,

                severity: AiSeoAnalysisSeverity::Warning,

                title: 'Search title length needs attention',

                message: sprintf(
                    'Resolved title length is %d characters, outside the internal acceptable band of %d–%d.',
                    $length,
                    AiSeoAnalysisPolicy::TITLE_ACCEPTABLE_MIN,
                    AiSeoAnalysisPolicy::TITLE_ACCEPTABLE_MAX,
                ),

                recommendation: 'Rewrite the title so it remains accurate while fitting the application’s preferred title range.',
            ),
        );
    }

    private function descriptionPresence(
        ResolvedSeoData $seo,
    ): AiSeoAnalysisRuleResultData {
        $value =
            $this->nullableText(
                $seo->description,
            );

        if ($value !== null) {
            return $this->rule(
                'search.description_presence',
                AiSeoAnalysisCategory::SearchMetadata,
                15,
                15,
            );
        }

        return $this->rule(
            'search.description_presence',
            AiSeoAnalysisCategory::SearchMetadata,
            0,
            15,
            new AiSeoAnalysisFindingData(
                id: 'missing_resolved_description',

                category: AiSeoAnalysisCategory::SearchMetadata,

                severity: AiSeoAnalysisSeverity::Critical,

                title: 'Resolved SEO description is missing',

                message: 'The authoritative SEO resolver produced no description.',

                recommendation: 'Add a useful entity summary/body or meta description so a factual search description can resolve.',
            ),
        );
    }

    private function descriptionLength(
        ResolvedSeoData $seo,
    ): AiSeoAnalysisRuleResultData {
        $value =
            $this->nullableText(
                $seo->description,
            );

        if ($value === null) {
            return $this->rule(
                'search.description_length',
                AiSeoAnalysisCategory::SearchMetadata,
                0,
                10,
            );
        }

        $length =
            mb_strlen(
                $value,
            );

        if (
            $length >=
                AiSeoAnalysisPolicy::DESCRIPTION_IDEAL_MIN
            && $length <=
                AiSeoAnalysisPolicy::DESCRIPTION_IDEAL_MAX
        ) {
            return $this->rule(
                'search.description_length',
                AiSeoAnalysisCategory::SearchMetadata,
                10,
                10,
            );
        }

        if (
            $length >=
                AiSeoAnalysisPolicy::DESCRIPTION_ACCEPTABLE_MIN
            && $length <=
                AiSeoAnalysisPolicy::DESCRIPTION_ACCEPTABLE_MAX
        ) {
            return $this->rule(
                'search.description_length',
                AiSeoAnalysisCategory::SearchMetadata,
                6,
                10,
                new AiSeoAnalysisFindingData(
                    id: 'description_length_can_improve',

                    category: AiSeoAnalysisCategory::SearchMetadata,

                    severity: AiSeoAnalysisSeverity::Info,

                    title: 'Search description length can improve',

                    message: sprintf(
                        'Resolved description length is %d characters. The internal preferred band is %d–%d.',
                        $length,
                        AiSeoAnalysisPolicy::DESCRIPTION_IDEAL_MIN,
                        AiSeoAnalysisPolicy::DESCRIPTION_IDEAL_MAX,
                    ),

                    recommendation: 'Refine the description so it communicates the entity clearly and concisely.',
                ),
            );
        }

        return $this->rule(
            'search.description_length',
            AiSeoAnalysisCategory::SearchMetadata,
            2,
            10,
            new AiSeoAnalysisFindingData(
                id: 'description_length_outside_preferred_range',

                category: AiSeoAnalysisCategory::SearchMetadata,

                severity: AiSeoAnalysisSeverity::Warning,

                title: 'Search description length needs attention',

                message: sprintf(
                    'Resolved description length is %d characters, outside the internal acceptable band of %d–%d.',
                    $length,
                    AiSeoAnalysisPolicy::DESCRIPTION_ACCEPTABLE_MIN,
                    AiSeoAnalysisPolicy::DESCRIPTION_ACCEPTABLE_MAX,
                ),

                recommendation: 'Rewrite the description to fit the application’s preferred range while keeping only supported facts.',
            ),
        );
    }

    private function contentPresence(
        AiSeoEntitySnapshotData $snapshot,
    ): AiSeoAnalysisRuleResultData {
        $length =
            $this->contentLength(
                $snapshot,
            );

        if ($length > 0) {
            return $this->rule(
                'content.presence',
                AiSeoAnalysisCategory::SourceContent,
                15,
                15,
            );
        }

        return $this->rule(
            'content.presence',
            AiSeoAnalysisCategory::SourceContent,
            0,
            15,
            new AiSeoAnalysisFindingData(
                id: 'missing_source_content',

                category: AiSeoAnalysisCategory::SourceContent,

                severity: AiSeoAnalysisSeverity::Warning,

                title: 'Source content is missing',

                message: 'The AI-safe entity snapshot contains no descriptive source text.',

                recommendation: 'Add factual descriptive content to the entity before relying on generated SEO suggestions.',
            ),
        );
    }

    private function contentDepth(
        AiSeoEntitySnapshotData $snapshot,
    ): AiSeoAnalysisRuleResultData {
        $length =
            $this->contentLength(
                $snapshot,
            );

        if (
            $length >=
                AiSeoAnalysisPolicy::CONTENT_GOOD_MIN
        ) {
            return $this->rule(
                'content.depth',
                AiSeoAnalysisCategory::SourceContent,
                10,
                10,
            );
        }

        if (
            $length >=
                AiSeoAnalysisPolicy::CONTENT_ACCEPTABLE_MIN
        ) {
            return $this->rule(
                'content.depth',
                AiSeoAnalysisCategory::SourceContent,
                6,
                10,
                new AiSeoAnalysisFindingData(
                    id: 'source_content_can_be_deeper',

                    category: AiSeoAnalysisCategory::SourceContent,

                    severity: AiSeoAnalysisSeverity::Info,

                    title: 'Source content could be more descriptive',

                    message: sprintf(
                        'The normalized AI-safe source context contains %d characters.',
                        $length,
                    ),

                    recommendation: 'Add more useful factual detail where appropriate so SEO analysis and suggestions have stronger source context.',
                ),
            );
        }

        if ($length > 0) {
            return $this->rule(
                'content.depth',
                AiSeoAnalysisCategory::SourceContent,
                2,
                10,
                new AiSeoAnalysisFindingData(
                    id: 'source_content_is_thin',

                    category: AiSeoAnalysisCategory::SourceContent,

                    severity: AiSeoAnalysisSeverity::Warning,

                    title: 'Source content is thin',

                    message: sprintf(
                        'The normalized AI-safe source context contains only %d characters.',
                        $length,
                    ),

                    recommendation: 'Expand the entity with accurate descriptive content before optimizing metadata.',
                ),
            );
        }

        return $this->rule(
            'content.depth',
            AiSeoAnalysisCategory::SourceContent,
            0,
            10,
        );
    }

    private function socialTextPresence(
        string $ruleId,
        ?string $value,
        string $label,
        string $recommendation,
        int $maxPoints,
    ): AiSeoAnalysisRuleResultData {
        if (
            $this->nullableText(
                $value,
            ) !== null
        ) {
            return $this->rule(
                $ruleId,
                AiSeoAnalysisCategory::SocialCoverage,
                $maxPoints,
                $maxPoints,
            );
        }

        return $this->rule(
            $ruleId,
            AiSeoAnalysisCategory::SocialCoverage,
            0,
            $maxPoints,
            new AiSeoAnalysisFindingData(
                id: str_replace(
                    '.',
                    '_',
                    $ruleId,
                ).'_missing',

                category: AiSeoAnalysisCategory::SocialCoverage,

                severity: AiSeoAnalysisSeverity::Warning,

                title: "{$label} is missing",

                message: "The authoritative SEO resolver produced no {$label}.",

                recommendation: $recommendation,
            ),
        );
    }

    private function socialImagePresence(
        ResolvedSeoData $seo,
    ): AiSeoAnalysisRuleResultData {
        $image =
            $this->nullableText(
                $seo->ogImage,
            )
            ?? $this->nullableText(
                $seo->twitterImage,
            );

        if ($image !== null) {
            return $this->rule(
                'social.image_presence',
                AiSeoAnalysisCategory::SocialCoverage,
                5,
                5,
            );
        }

        return $this->rule(
            'social.image_presence',
            AiSeoAnalysisCategory::SocialCoverage,
            0,
            5,
            new AiSeoAnalysisFindingData(
                id: 'social_image_missing',

                category: AiSeoAnalysisCategory::SocialCoverage,

                severity: AiSeoAnalysisSeverity::Info,

                title: 'Social sharing image is missing',

                message: 'Neither the resolved Open Graph nor X/Twitter metadata contains an image.',

                recommendation: 'Configure an accurate entity image or global social fallback image where appropriate.',
            ),
        );
    }

    /**
     * @param  list<AiSeoAnalysisRuleResultData>  $rules
     * @return array<string, array{
     *     points: int,
     *     max_points: int
     * }>
     */
    private function breakdown(
        array $rules,
    ): array {
        /** @var array<string, array{points: int, max_points: int}> $breakdown */
        $breakdown = [];

        foreach ($rules as $rule) {
            $key =
                $rule
                    ->category
                    ->value;

            if (
                ! isset(
                    $breakdown[$key],
                )
            ) {
                $breakdown[$key] = [
                    'points' => 0,

                    'max_points' => 0,
                ];
            }

            $breakdown[$key]['points'] +=
                $rule->points;

            $breakdown[$key]['max_points'] +=
                $rule->maxPoints;
        }

        return $breakdown;
    }

    /**
     * @return list<AiSeoAnalysisFindingData>
     */
    private function indexabilityFindings(
        ResolvedSeoData $seo,
    ): array {
        $findings = [];

        if (! $seo->robotsIndex) {
            $findings[] =
                new AiSeoAnalysisFindingData(
                    id: 'resolved_noindex_notice',

                    category: AiSeoAnalysisCategory::SearchMetadata,

                    severity: AiSeoAnalysisSeverity::Info,

                    title: 'Page resolves to noindex',

                    message: 'The resolved SEO policy currently prevents indexing. This does not reduce the content/metadata score because noindex can be intentional.',

                    recommendation: 'Confirm the noindex state is intentional for this entity and route.',
                );
        }

        if (! $seo->robotsFollow) {
            $findings[] =
                new AiSeoAnalysisFindingData(
                    id: 'resolved_nofollow_notice',

                    category: AiSeoAnalysisCategory::SearchMetadata,

                    severity: AiSeoAnalysisSeverity::Info,

                    title: 'Page resolves to nofollow',

                    message: 'The resolved SEO policy currently prevents link following. This does not reduce the content/metadata score because nofollow can be intentional.',

                    recommendation: 'Confirm the nofollow state is intentional for this entity and route.',
                );
        }

        return $findings;
    }

    private function contentLength(
        AiSeoEntitySnapshotData $snapshot,
    ): int {
        $parts = [];

        foreach (
            $snapshot->content as $value
        ) {
            if (! is_string($value)) {
                continue;
            }

            $value =
                trim(
                    $value,
                );

            if ($value !== '') {
                $parts[] =
                    $value;
            }
        }

        return mb_strlen(
            implode(
                ' ',
                $parts,
            ),
        );
    }

    private function nullableText(
        ?string $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        return $value === ''
            ? null
            : $value;
    }

    private function rule(
        string $ruleId,
        AiSeoAnalysisCategory $category,
        int $points,
        int $maxPoints,
        ?AiSeoAnalysisFindingData $finding = null,
    ): AiSeoAnalysisRuleResultData {
        return new AiSeoAnalysisRuleResultData(
            ruleId: $ruleId,

            category: $category,

            points: $points,

            maxPoints: $maxPoints,

            finding: $finding,
        );
    }
}
