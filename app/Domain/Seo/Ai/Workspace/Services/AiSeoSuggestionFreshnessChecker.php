<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Workspace\Services;

use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Domain\Seo\Ai\Workspace\Data\AiSeoSuggestionFreshnessData;
use App\Models\AiSeoRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use JsonException;

final readonly class AiSeoSuggestionFreshnessChecker
{
    public function __construct(
        private AiSeoEntitySnapshotBuilder $snapshots,
        private AiSeoSnapshotHasher $hasher,
    ) {}

    /**
     * @throws JsonException
     */
    public function check(
        AiSeoRequest $request,
    ): AiSeoSuggestionFreshnessData {
        $sourceSnapshot =
            $request
                ->input_snapshot[
                    'entity_snapshot'
                ]
            ?? null;

        if (! is_array($sourceSnapshot)) {
            return new AiSeoSuggestionFreshnessData(
                isStale: true,

                state: 'source_snapshot_missing',

                sourceSnapshotHash: null,

                currentSnapshotHash: null,
            );
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
            return new AiSeoSuggestionFreshnessData(
                isStale: true,

                state: 'entity_missing',

                sourceSnapshotHash: $sourceHash,

                currentSnapshotHash: null,
            );
        }

        $currentHash =
            $this
                ->hasher
                ->hash(
                    $currentSnapshot,
                );

        $isStale =
            ! hash_equals(
                $sourceHash,
                $currentHash,
            );

        return new AiSeoSuggestionFreshnessData(
            isStale: $isStale,

            state: $isStale
                    ? 'stale'
                    : 'fresh',

            sourceSnapshotHash: $sourceHash,

            currentSnapshotHash: $currentHash,
        );
    }
}
