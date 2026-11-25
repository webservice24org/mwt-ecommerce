<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Workspace\Data;

final readonly class AiSeoSuggestionFreshnessData
{
    public function __construct(
        public bool $isStale,
        public string $state,
        public ?string $sourceSnapshotHash,
        public ?string $currentSnapshotHash,
    ) {}

    /**
     * @return array{
     *     is_stale: bool,
     *     state: string,
     *     source_snapshot_hash: string|null,
     *     current_snapshot_hash: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'is_stale' => $this->isStale,

            'state' => $this->state,

            'source_snapshot_hash' => $this->sourceSnapshotHash,

            'current_snapshot_hash' => $this->currentSnapshotHash,
        ];
    }
}
