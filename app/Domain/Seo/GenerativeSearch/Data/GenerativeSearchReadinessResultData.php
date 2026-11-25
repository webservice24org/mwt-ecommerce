<?php

declare(strict_types=1);

namespace App\Domain\Seo\GenerativeSearch\Data;

use App\Domain\Seo\GenerativeSearch\Enums\GenerativeSearchReadinessStatus;

final readonly class GenerativeSearchReadinessResultData
{
    /**
     * @param  list<GenerativeSearchFindingData>  $findings
     * @param array{
     *     entity_type: string,
     *     content_characters: int,
     *     context_text_items: int,
     *     canonical_present: bool,
     *     robots_index: bool,
     *     robots_follow: bool,
     *     structured_data_types: list<string>,
     *     primary_image_present: bool
     * } $signals
     */
    public function __construct(
        public string $policyVersion,
        public GenerativeSearchReadinessStatus $status,
        public bool $eligibleForIndexing,
        public array $signals,
        public array $findings,
    ) {}

    /**
     * @return array{
     *     policy_version: string,
     *     status: string,
     *     eligible_for_indexing: bool,
     *     signals: array{
     *         entity_type: string,
     *         content_characters: int,
     *         context_text_items: int,
     *         canonical_present: bool,
     *         robots_index: bool,
     *         robots_follow: bool,
     *         structured_data_types: list<string>,
     *         primary_image_present: bool
     *     },
     *     findings: list<array{
     *         id: string,
     *         severity: string,
     *         category: string,
     *         message: string,
     *         action: string
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'policy_version' => $this->policyVersion,
            'status' => $this->status->value,
            'eligible_for_indexing' => $this->eligibleForIndexing,
            'signals' => $this->signals,
            'findings' => array_map(
                static fn (GenerativeSearchFindingData $finding): array => $finding->toArray(),
                $this->findings,
            ),
        ];
    }
}
