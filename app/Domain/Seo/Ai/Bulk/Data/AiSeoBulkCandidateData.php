<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Data;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemState;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;

final readonly class AiSeoBulkCandidateData
{
    public function __construct(
        public AiSeoEntityType $entityType,
        public int $entityId,
        public string $name,
        public string $slug,
        public string $currentSnapshotHash,
        public ?int $latestRequestId,
        public ?string $latestRequestSnapshotHash,
        public AiSeoBulkItemState $state,
        public string $reason,
    ) {}

    /**
     * @return array{
     *     entity_type: string,
     *     entity_id: int,
     *     name: string,
     *     slug: string,
     *     current_snapshot_hash: string,
     *     latest_request_id: int|null,
     *     latest_request_snapshot_hash: string|null,
     *     state: string,
     *     eligible: bool,
     *     reason: string
     * }
     */
    public function toArray(): array
    {
        return [
            'entity_type' => $this->entityType->value,
            'entity_id' => $this->entityId,
            'name' => $this->name,
            'slug' => $this->slug,
            'current_snapshot_hash' => $this->currentSnapshotHash,
            'latest_request_id' => $this->latestRequestId,
            'latest_request_snapshot_hash' => $this->latestRequestSnapshotHash,
            'state' => $this->state->value,
            'eligible' => $this->state->isEligibleForGeneration(),
            'reason' => $this->reason,
        ];
    }
}
