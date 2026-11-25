<?php

declare(strict_types=1);

namespace App\Domain\Seo\GenerativeSearch\Services;

use App\Domain\Seo\Data\ResolvedSeoData;
use App\Domain\Seo\GenerativeSearch\Data\GenerativeSearchFindingData;
use App\Domain\Seo\GenerativeSearch\Data\GenerativeSearchReadinessResultData;
use App\Domain\Seo\GenerativeSearch\Enums\GenerativeSearchFindingSeverity;
use App\Domain\Seo\GenerativeSearch\Enums\GenerativeSearchReadinessStatus;
use App\Domain\Seo\GenerativeSearch\GenerativeSearchReadinessPolicy;
use DomainException;

final readonly class GenerativeSearchReadinessAnalyzer
{
    /**
     * @param array{
     *     schema_version: string,
     *     entity: array{
     *         type: string,
     *         id: int,
     *         name: string,
     *         slug: string
     *     },
     *     content: array<string, mixed>,
     *     context: array<string, mixed>,
     *     current_seo: array<string, string|null>
     * } $entitySnapshot
     * @param  list<string>  $structuredDataTypes
     */
    public function analyze(
        array $entitySnapshot,
        ResolvedSeoData $resolvedSeo,
        ?string $canonicalUrl,
        array $structuredDataTypes = [],
        bool $hasPrimaryImage = false,
    ): GenerativeSearchReadinessResultData {
        $entityType =
            $entitySnapshot[
                'entity'
            ][
                'type'
            ];

        if (
            ! in_array(
                $entityType,
                GenerativeSearchReadinessPolicy::recognizedEntityTypes(),
                true,
            )
        ) {
            throw new DomainException(
                sprintf(
                    'Unsupported generative-search entity type: %s.',
                    $entityType,
                ),
            );
        }

        $contentText =
            $this->collectText(
                $entitySnapshot[
                    'content'
                ],
            );

        $contextText =
            $this->collectText(
                $entitySnapshot[
                    'context'
                ],
            );

        $contentCharacters =
            mb_strlen(
                implode(
                    ' ',
                    $contentText,
                ),
            );

        $canonicalPresent =
            $canonicalUrl !== null
            && trim(
                $canonicalUrl,
            ) !== '';

        $structuredDataTypes =
            $this->normalizeTypes(
                $structuredDataTypes,
            );

        $findings = [];

        if (! $resolvedSeo->robotsIndex) {
            $findings[] =
                $this->finding(
                    id: 'indexing_blocked',

                    severity: GenerativeSearchFindingSeverity::Blocker,

                    category: 'indexability',

                    message: 'The resolved robots policy is noindex, so this URL is not eligible for normal search indexing.',

                    action: 'Keep noindex when intentional; otherwise correct the authoritative robots policy before optimizing for generative search.',
                );
        }

        if (! $resolvedSeo->robotsFollow) {
            $findings[] =
                $this->finding(
                    id: 'link_following_disabled',

                    severity: GenerativeSearchFindingSeverity::Warning,

                    category: 'discovery',

                    message: 'The resolved robots policy disables link following.',

                    action: 'Use nofollow only when intentional; otherwise allow normal link discovery and relationship traversal.',
                );
        }

        if (! $canonicalPresent) {
            $findings[] =
                $this->finding(
                    id: 'canonical_missing',

                    severity: GenerativeSearchFindingSeverity::Warning,

                    category: 'canonicalization',

                    message: 'No canonical URL was supplied to the readiness analysis.',

                    action: 'Resolve one stable canonical URL for the public entity page.',
                );
        }

        if (
            trim(
                $resolvedSeo->title,
            ) === ''
        ) {
            $findings[] =
                $this->finding(
                    id: 'resolved_title_missing',

                    severity: GenerativeSearchFindingSeverity::Warning,

                    category: 'entity_clarity',

                    message: 'The resolved search title is empty.',

                    action: 'Provide a clear authoritative title that identifies the entity.',
                );
        }

        if (
            $resolvedSeo->description ===
            null
            || trim(
                $resolvedSeo->description,
            ) === ''
        ) {
            $findings[] =
                $this->finding(
                    id: 'resolved_description_missing',

                    severity: GenerativeSearchFindingSeverity::Warning,

                    category: 'entity_clarity',

                    message: 'The resolved search description is empty.',

                    action: 'Provide a concise factual description that reflects visible page content.',
                );
        }

        if ($contentCharacters === 0) {
            $findings[] =
                $this->finding(
                    id: 'visible_content_missing',

                    severity: GenerativeSearchFindingSeverity::Warning,

                    category: 'answerability',

                    message: 'The H.2 entity snapshot contains no usable source-content text.',

                    action: 'Add useful visible content that explains the entity for people first.',
                );
        } elseif (
            $contentCharacters <
            GenerativeSearchReadinessPolicy::THIN_CONTENT_CHARACTERS
        ) {
            $findings[] =
                $this->finding(
                    id: 'visible_content_thin',

                    severity: GenerativeSearchFindingSeverity::Warning,

                    category: 'answerability',

                    message: 'The available source-content text is very limited.',

                    action: 'Expand the visible content with accurate, differentiated facts that answer likely user questions.',
                );
        } elseif (
            $contentCharacters <
            GenerativeSearchReadinessPolicy::SUBSTANTIVE_CONTENT_CHARACTERS
        ) {
            $findings[] =
                $this->finding(
                    id: 'visible_content_brief',

                    severity: GenerativeSearchFindingSeverity::Info,

                    category: 'answerability',

                    message: 'The entity has usable source content, but it is relatively brief.',

                    action: 'Add more useful detail only where it improves the page for users; do not pad content for AI systems.',
                );
        }

        if ($contextText === []) {
            $findings[] =
                $this->finding(
                    id: 'entity_context_missing',

                    severity: GenerativeSearchFindingSeverity::Warning,

                    category: 'entity_context',

                    message: 'The H.2 snapshot contains no textual relationship or context information.',

                    action: 'Expose relevant relationships such as brand, category, parent, or other real entity context where applicable.',
                );
        }

        $expectedTypes =
            GenerativeSearchReadinessPolicy::expectedStructuredDataTypes(
                $entityType,
            );

        if (
            $expectedTypes !== []
            && ! $this->containsAny(
                haystack: $structuredDataTypes,

                needles: $expectedTypes,
            )
        ) {
            $findings[] =
                $this->finding(
                    id: 'structured_evidence_missing',

                    severity: GenerativeSearchFindingSeverity::Info,

                    category: 'structured_evidence',

                    message: 'No expected structured-data type was reported for this entity.',

                    action: 'Keep structured data accurate and consistent with visible content; do not add unsupported markup solely for AI visibility.',
                );
        }

        if (
            in_array(
                $entityType,
                [
                    'product',
                    'brand',
                ],
                true,
            )
            && ! $hasPrimaryImage
        ) {
            $findings[] =
                $this->finding(
                    id: 'primary_image_missing',

                    severity: GenerativeSearchFindingSeverity::Info,

                    category: 'multimodal_clarity',

                    message: 'No primary image was reported for this entity.',

                    action: 'Provide a relevant high-quality image when the entity is inherently visual.',
                );
        }

        return new GenerativeSearchReadinessResultData(
            policyVersion: GenerativeSearchReadinessPolicy::VERSION,

            status: $this->status(
                $findings,
            ),

            eligibleForIndexing: $resolvedSeo
                ->robotsIndex,

            signals: [
                'entity_type' => $entityType,

                'content_characters' => $contentCharacters,

                'context_text_items' => count(
                    $contextText,
                ),

                'canonical_present' => $canonicalPresent,

                'robots_index' => $resolvedSeo
                    ->robotsIndex,

                'robots_follow' => $resolvedSeo
                    ->robotsFollow,

                'structured_data_types' => $structuredDataTypes,

                'primary_image_present' => $hasPrimaryImage,
            ],

            findings: $findings,
        );
    }

    /**
     * @param  list<GenerativeSearchFindingData>  $findings
     */
    private function status(
        array $findings,
    ): GenerativeSearchReadinessStatus {
        foreach (
            $findings as $finding
        ) {
            if (
                $finding->severity ===
                GenerativeSearchFindingSeverity::Blocker
            ) {
                return GenerativeSearchReadinessStatus::Blocked;
            }
        }

        foreach (
            $findings as $finding
        ) {
            if (
                $finding->severity ===
                GenerativeSearchFindingSeverity::Warning
            ) {
                return GenerativeSearchReadinessStatus::NeedsWork;
            }
        }

        return GenerativeSearchReadinessStatus::Ready;
    }

    private function finding(
        string $id,
        GenerativeSearchFindingSeverity $severity,
        string $category,
        string $message,
        string $action,
    ): GenerativeSearchFindingData {
        return new GenerativeSearchFindingData(
            id: $id,

            severity: $severity,

            category: $category,

            message: $message,

            action: $action,
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @return list<string>
     */
    private function collectText(
        array $values,
    ): array {
        $text = [];

        foreach (
            $values as $value
        ) {
            if (
                is_string(
                    $value,
                )
            ) {
                $value =
                    trim(
                        $value,
                    );

                if ($value !== '') {
                    $text[] =
                        $value;
                }

                continue;
            }

            if (
                is_array(
                    $value,
                )
            ) {
                /** @var array<string, mixed> $value */
                $text = [
                    ...$text,
                    ...$this->collectText(
                        $value,
                    ),
                ];
            }
        }

        return $text;
    }

    /**
     * @param  list<string>  $types
     * @return list<string>
     */
    private function normalizeTypes(
        array $types,
    ): array {
        $normalized = [];

        foreach (
            $types as $type
        ) {
            $type =
                trim(
                    $type,
                );

            if (
                $type === ''
                || in_array(
                    $type,
                    $normalized,
                    true,
                )
            ) {
                continue;
            }

            $normalized[] =
                $type;
        }

        sort(
            $normalized,
            SORT_STRING,
        );

        return $normalized;
    }

    /**
     * @param  list<string>  $haystack
     * @param  list<string>  $needles
     */
    private function containsAny(
        array $haystack,
        array $needles,
    ): bool {
        foreach (
            $needles as $needle
        ) {
            if (
                in_array(
                    $needle,
                    $haystack,
                    true,
                )
            ) {
                return true;
            }
        }

        return false;
    }
}
