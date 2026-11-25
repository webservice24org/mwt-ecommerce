<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Enrichment\Services;

use App\Domain\Seo\Ai\Analysis\Enrichment\Data\AiSeoAnalysisActionEnrichmentData;
use App\Domain\Seo\Ai\Analysis\Enrichment\Data\AiSeoAnalysisEnrichmentData;
use App\Domain\Seo\Ai\Analysis\Enrichment\Enums\AiSeoAnalysisActionPriority;
use App\Domain\Seo\Ai\Analysis\Enrichment\Exceptions\AiSeoAnalysisEnrichmentValidationException;
use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisSeverity;
use App\Models\AiSeoAnalysis;

final class AiSeoAnalysisEnrichmentValidator
{
    private const SUMMARY_MAX =
        1200;

    private const ACTION_TEXT_MAX =
        800;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function validate(
        array $payload,
        AiSeoAnalysis $analysis,
    ): AiSeoAnalysisEnrichmentData {
        $unexpectedTopLevel =
            array_diff(
                array_keys(
                    $payload,
                ),
                [
                    'summary',
                    'actions',
                ],
            );

        if ($unexpectedTopLevel !== []) {
            throw new AiSeoAnalysisEnrichmentValidationException(
                'AI SEO analysis enrichment contains unsupported top-level fields.',
            );
        }

        $summary =
            $this->requiredPlainText(
                $payload[
                    'summary'
                ]
                ?? null,
                self::SUMMARY_MAX,
                'AI SEO analysis enrichment summary',
            );

        $actions =
            $payload[
                'actions'
            ]
            ?? null;

        if (
            ! is_array(
                $actions,
            )
            || ! array_is_list(
                $actions,
            )
            || $actions === []
        ) {
            throw new AiSeoAnalysisEnrichmentValidationException(
                'AI SEO analysis enrichment must contain at least one action.',
            );
        }

        $allowed =
            $this->allowedFindings(
                $analysis,
            );

        if ($allowed === []) {
            throw new AiSeoAnalysisEnrichmentValidationException(
                'The deterministic analysis has no findings available for enrichment.',
            );
        }

        $maxActions =
            min(
                count(
                    $allowed,
                ),
                max(
                    1,
                    min(
                        (int) config(
                            'ai-seo-analysis.max_actions',
                            5,
                        ),
                        10,
                    ),
                ),
            );

        if (
            count(
                $actions,
            ) > $maxActions
        ) {
            throw new AiSeoAnalysisEnrichmentValidationException(
                sprintf(
                    'AI SEO analysis enrichment exceeds the maximum of %d actions.',
                    $maxActions,
                ),
            );
        }

        $seen = [];
        $validated = [];

        foreach ($actions as $item) {
            if (! is_array($item)) {
                throw new AiSeoAnalysisEnrichmentValidationException(
                    'AI SEO analysis enrichment action must be an object.',
                );
            }

            $unexpected =
                array_diff(
                    array_keys(
                        $item,
                    ),
                    [
                        'finding_id',
                        'explanation',
                        'action',
                    ],
                );

            if ($unexpected !== []) {
                throw new AiSeoAnalysisEnrichmentValidationException(
                    'AI SEO analysis enrichment action contains unsupported fields.',
                );
            }

            $findingId =
                $this->requiredPlainText(
                    $item[
                        'finding_id'
                    ]
                    ?? null,
                    160,
                    'AI SEO analysis finding ID',
                );

            if (
                ! array_key_exists(
                    $findingId,
                    $allowed,
                )
            ) {
                throw new AiSeoAnalysisEnrichmentValidationException(
                    sprintf(
                        'Unknown deterministic analysis finding ID: %s.',
                        $findingId,
                    ),
                );
            }

            if (
                isset(
                    $seen[
                        $findingId
                    ],
                )
            ) {
                throw new AiSeoAnalysisEnrichmentValidationException(
                    sprintf(
                        'Duplicate deterministic analysis finding ID: %s.',
                        $findingId,
                    ),
                );
            }

            $explanation =
                $this->requiredPlainText(
                    $item[
                        'explanation'
                    ]
                    ?? null,
                    self::ACTION_TEXT_MAX,
                    'AI SEO analysis action explanation',
                );

            $action =
                $this->requiredPlainText(
                    $item[
                        'action'
                    ]
                    ?? null,
                    self::ACTION_TEXT_MAX,
                    'AI SEO analysis action',
                );

            $validated[] =
                new AiSeoAnalysisActionEnrichmentData(
                    findingId: $findingId,

                    priority: AiSeoAnalysisActionPriority::fromSeverity(
                        $allowed[
                            $findingId
                        ],
                    ),

                    explanation: $explanation,

                    action: $action,
                );

            $seen[
                $findingId
            ] = true;
        }

        return new AiSeoAnalysisEnrichmentData(
            summary: $summary,

            actions: $validated,
        );
    }

    /**
     * @return array<string, AiSeoAnalysisSeverity>
     */
    private function allowedFindings(
        AiSeoAnalysis $analysis,
    ): array {
        $allowed = [];

        foreach (
            $analysis->findings as $finding
        ) {
            $id =
                $finding[
                    'id'
                ]
                ?? null;

            $severityValue =
                $finding[
                    'severity'
                ]
                ?? null;

            if (
                ! is_string(
                    $id,
                )
                || trim(
                    $id,
                ) === ''
                || ! is_string(
                    $severityValue,
                )
            ) {
                continue;
            }

            $severity =
                AiSeoAnalysisSeverity::tryFrom(
                    $severityValue,
                );

            if ($severity === null) {
                continue;
            }

            $allowed[
                trim(
                    $id,
                )
            ] =
                $severity;
        }

        return $allowed;
    }

    private function requiredPlainText(
        mixed $value,
        int $maxLength,
        string $label,
    ): string {
        if (! is_string($value)) {
            throw new AiSeoAnalysisEnrichmentValidationException(
                "{$label} must be a string.",
            );
        }

        $value =
            preg_replace(
                '/\s+/u',
                ' ',
                $value,
            )
            ?? $value;

        $value =
            trim(
                $value,
            );

        if ($value === '') {
            throw new AiSeoAnalysisEnrichmentValidationException(
                "{$label} cannot be blank.",
            );
        }

        if (
            strip_tags(
                $value,
            ) !== $value
        ) {
            throw new AiSeoAnalysisEnrichmentValidationException(
                "{$label} must not contain HTML.",
            );
        }

        if (
            preg_match(
                '/(?:https?:\/\/|www\.)/iu',
                $value,
            ) === 1
        ) {
            throw new AiSeoAnalysisEnrichmentValidationException(
                "{$label} must not contain URLs.",
            );
        }

        if (
            preg_match(
                '/```|`[^`]+`/u',
                $value,
            ) === 1
        ) {
            throw new AiSeoAnalysisEnrichmentValidationException(
                "{$label} must not contain code formatting.",
            );
        }

        if (
            preg_match(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
                $value,
            ) === 1
        ) {
            throw new AiSeoAnalysisEnrichmentValidationException(
                "{$label} contains unsupported control characters.",
            );
        }

        if (
            mb_strlen(
                $value,
            ) > $maxLength
        ) {
            throw new AiSeoAnalysisEnrichmentValidationException(
                sprintf(
                    '%s exceeds the %d character limit.',
                    $label,
                    $maxLength,
                ),
            );
        }

        return $value;
    }
}
