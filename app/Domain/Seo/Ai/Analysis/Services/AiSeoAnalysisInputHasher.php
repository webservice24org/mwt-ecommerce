<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Services;

use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use JsonException;

final readonly class AiSeoAnalysisInputHasher
{
    public function __construct(
        private AiSeoSnapshotHasher $hasher,
    ) {}

    /**
     * @param  array<string, mixed>  $sourceSnapshot
     * @param  array<string, mixed>  $resolvedSeo
     *
     * @throws JsonException
     */
    public function hash(
        string $analysisVersion,
        array $sourceSnapshot,
        array $resolvedSeo,
    ): string {
        return $this
            ->hasher
            ->hash([
                'analysis_version' => $analysisVersion,

                'source_snapshot' => $sourceSnapshot,

                'resolved_seo' => $resolvedSeo,
            ]);
    }
}
