<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Services;

use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestionApplication;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use JsonException;

final readonly class AiSeoBulkApplicationFreshnessService
{
    public function __construct(
        private AiSeoEntitySnapshotBuilder $snapshots,
        private AiSeoSnapshotHasher $hasher,
    ) {}

    /**
     * K.4 allows a request's remaining fields to stay fresh after
     * K.4 itself applied another field from the same request.
     *
     * For hash comparison only, a previously-applied field is
     * normalized back to its generation baseline when the live
     * field still equals exactly the value K.4 recorded as applied.
     *
     * If a human later edits that field, normalization is not
     * performed and the old request becomes stale as expected.
     *
     * @return array{
     *     state: string,
     *     is_stale: bool,
     *     source_snapshot_hash: string|null,
     *     current_snapshot_hash: string|null,
     *     current_seo: array<string, string|null>
     * }
     *
     * @throws JsonException
     */
    public function inspectRequest(
        AiSeoRequest $request,
    ): array {
        $sourceSnapshot =
            $request
                ->input_snapshot[
                    'entity_snapshot'
                ]
            ?? null;

        if (! is_array($sourceSnapshot)) {
            return [
                'state' => 'source_snapshot_missing',

                'is_stale' => true,

                'source_snapshot_hash' => null,

                'current_snapshot_hash' => null,

                'current_seo' => [],
            ];
        }

        $sourceHash =
            $this
                ->hasher
                ->hash(
                    $sourceSnapshot,
                );

        try {
            $currentSnapshot =
                $this
                    ->snapshots
                    ->build(
                        $request
                            ->entity_type,

                        $request
                            ->entity_id,
                    )
                    ->toArray();
        } catch (
            ModelNotFoundException
        ) {
            return [
                'state' => 'entity_missing',

                'is_stale' => true,

                'source_snapshot_hash' => $sourceHash,

                'current_snapshot_hash' => null,

                'current_seo' => [],
            ];
        }

        /*
         * AiSeoEntitySnapshotData::toArray() guarantees this key
         * and its array<string, string|null> shape.
         */
        $actualCurrentSeo =
            $currentSnapshot[
                'current_seo'
            ];

        $normalizedSnapshot =
            $currentSnapshot;

        $normalizedSeo =
            $actualCurrentSeo;

        $applications =
            AiSeoSuggestionApplication::query()
                ->where(
                    'ai_seo_request_id',
                    $request->id,
                )
                ->orderBy('id')
                ->get();

        foreach (
            $applications as $application
        ) {
            $field =
                $application
                    ->field;

            $liveValue =
                $actualCurrentSeo[
                    $field
                ]
                ?? null;

            if (
                $liveValue ===
                $application
                    ->applied_value
            ) {
                $normalizedSeo[
                    $field
                ] =
                    $application
                        ->baseline_value;
            }
        }

        $normalizedSnapshot[
            'current_seo'
        ] =
            $normalizedSeo;

        $currentHash =
            $this
                ->hasher
                ->hash(
                    $normalizedSnapshot,
                );

        $stale =
            ! hash_equals(
                $sourceHash,
                $currentHash,
            );

        return [
            'state' => $stale
                    ? 'stale'
                    : 'fresh',

            'is_stale' => $stale,

            'source_snapshot_hash' => $sourceHash,

            'current_snapshot_hash' => $currentHash,

            'current_seo' => $actualCurrentSeo,
        ];
    }
}
