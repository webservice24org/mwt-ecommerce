<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Services;

use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use JsonException;

final readonly class AiSeoBulkSuggestionFreshnessService
{
    public function __construct(
        private AiSeoEntitySnapshotBuilder $snapshots,
        private AiSeoSnapshotHasher $hasher,
    ) {}

    /**
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
    public function inspect(AiSeoSuggestion $suggestion): array
    {
        return $this->inspectRequest($suggestion->request);
    }

    /**
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
    public function inspectRequest(AiSeoRequest $request): array
    {
        $sourceSnapshot = $request->input_snapshot['entity_snapshot'] ?? null;

        if (! is_array($sourceSnapshot)) {
            return [
                'state' => 'source_snapshot_missing',
                'is_stale' => true,
                'source_snapshot_hash' => null,
                'current_snapshot_hash' => null,
                'current_seo' => [],
            ];
        }

        $sourceHash = $this->hasher->hash($sourceSnapshot);

        try {
            $currentSnapshot = $this->currentSnapshot($request);
        } catch (ModelNotFoundException) {
            return [
                'state' => 'entity_missing',
                'is_stale' => true,
                'source_snapshot_hash' => $sourceHash,
                'current_snapshot_hash' => null,
                'current_seo' => [],
            ];
        }

        $currentHash = $this->hasher->hash($currentSnapshot);
        $currentSeo = $currentSnapshot['current_seo'] ?? [];

        if (! is_array($currentSeo)) {
            $currentSeo = [];
        }

        /** @var array<string, string|null> $currentSeo */
        $stale = ! hash_equals($sourceHash, $currentHash);

        return [
            'state' => $stale ? 'stale' : 'fresh',
            'is_stale' => $stale,
            'source_snapshot_hash' => $sourceHash,
            'current_snapshot_hash' => $currentHash,
            'current_seo' => $currentSeo,
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ModelNotFoundException
     */
    private function currentSnapshot(AiSeoRequest $request): array
    {
        return $this
            ->snapshots
            ->build(
                $request->entity_type,
                $request->entity_id,
            )
            ->toArray();
    }
}
