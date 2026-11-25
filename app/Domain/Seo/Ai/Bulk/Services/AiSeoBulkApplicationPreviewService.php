<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Services;

use App\Domain\Seo\Ai\Workspace\Enums\AiSeoSuggestionReviewDecision;
use App\Models\AiSeoSuggestion;
use App\Models\AiSeoSuggestionApplication;
use JsonException;

final readonly class AiSeoBulkApplicationPreviewService
{
    public function __construct(
        private AiSeoBulkApplicationFreshnessService $freshness,
    ) {}

    /**
     * @return array{
     *     status: string,
     *     can_apply: bool,
     *     review_status: string,
     *     baseline_value: string|null,
     *     current_value: string|null,
     *     source_snapshot_hash: string|null,
     *     current_snapshot_hash: string|null,
     *     application_id: int|null,
     *     applied_at: string|null,
     *     reason: string
     * }
     *
     * @throws JsonException
     */
    public function inspect(
        AiSeoSuggestion $suggestion,
        ?AiSeoSuggestionApplication $application = null,
    ): array {
        $review =
            $suggestion
                ->latestReview;

        $reviewStatus =
            $review === null
                ? 'pending'
                : $review
                    ->decision
                    ->value;

        $freshness =
            $this
                ->freshness
                ->inspectRequest(
                    $suggestion
                        ->request,
                );

        $field =
            $suggestion
                ->field
                ->value;

        $sourceSnapshot =
            $suggestion
                ->request
                ->input_snapshot[
                    'entity_snapshot'
                ]
            ?? [];

        if (! is_array($sourceSnapshot)) {
            $sourceSnapshot = [];
        }

        $sourceSeo =
            $sourceSnapshot[
                'current_seo'
            ]
            ?? [];

        if (! is_array($sourceSeo)) {
            $sourceSeo = [];
        }

        $baselineValue =
            $this->nullableString(
                $sourceSeo[
                    $field
                ]
                ?? null,
            );

        $currentValue =
            $freshness[
                'current_seo'
            ][
                $field
            ]
            ?? null;

        if (! is_string($currentValue)) {
            $currentValue =
                null;
        }

        if ($application !== null) {
            $unchangedSinceApply =
                $currentValue ===
                $application
                    ->applied_value;

            return [
                'status' => 'already_applied',

                'can_apply' => false,

                'review_status' => $reviewStatus,

                'baseline_value' => $application
                    ->baseline_value,

                'current_value' => $currentValue,

                'source_snapshot_hash' => $freshness[
                        'source_snapshot_hash'
                    ],

                'current_snapshot_hash' => $freshness[
                        'current_snapshot_hash'
                    ],

                'application_id' => $application->id,

                'applied_at' => $application
                    ->applied_at
                    ->toIso8601String(),

                'reason' => $unchangedSinceApply
                        ? 'This AI suggestion has already been applied.'
                        : 'This AI suggestion was already applied, but the live field changed afterward. The current live value is preserved.',
            ];
        }

        if (
            $review === null
            || $review
                ->decision !==
                AiSeoSuggestionReviewDecision::Approved
        ) {
            return [
                'status' => 'not_approved',

                'can_apply' => false,

                'review_status' => $reviewStatus,

                'baseline_value' => $baselineValue,

                'current_value' => $currentValue,

                'source_snapshot_hash' => $freshness[
                        'source_snapshot_hash'
                    ],

                'current_snapshot_hash' => $freshness[
                        'current_snapshot_hash'
                    ],

                'application_id' => null,

                'applied_at' => null,

                'reason' => 'The latest human review decision is not approved.',
            ];
        }

        if (
            $currentValue !==
            $baselineValue
        ) {
            return [
                'status' => 'conflict',

                'can_apply' => false,

                'review_status' => $reviewStatus,

                'baseline_value' => $baselineValue,

                'current_value' => $currentValue,

                'source_snapshot_hash' => $freshness[
                        'source_snapshot_hash'
                    ],

                'current_snapshot_hash' => $freshness[
                        'current_snapshot_hash'
                    ],

                'application_id' => null,

                'applied_at' => null,

                'reason' => 'The live field value changed after AI generation. K.4 will not overwrite it.',
            ];
        }

        if (
            $freshness[
                'is_stale'
            ]
        ) {
            return [
                'status' => 'stale',

                'can_apply' => false,

                'review_status' => $reviewStatus,

                'baseline_value' => $baselineValue,

                'current_value' => $currentValue,

                'source_snapshot_hash' => $freshness[
                        'source_snapshot_hash'
                    ],

                'current_snapshot_hash' => $freshness[
                        'current_snapshot_hash'
                    ],

                'application_id' => null,

                'applied_at' => null,

                'reason' => 'The entity changed after generation. Generate and review fresh suggestions before applying.',
            ];
        }

        return [
            'status' => 'ready',

            'can_apply' => true,

            'review_status' => $reviewStatus,

            'baseline_value' => $baselineValue,

            'current_value' => $currentValue,

            'source_snapshot_hash' => $freshness[
                    'source_snapshot_hash'
                ],

            'current_snapshot_hash' => $freshness[
                    'current_snapshot_hash'
                ],

            'application_id' => null,

            'applied_at' => null,

            'reason' => 'Approved, fresh and conflict-free.',
        ];
    }

    private function nullableString(
        mixed $value,
    ): ?string {
        return is_string(
            $value,
        )
            ? $value
            : null;
    }
}
