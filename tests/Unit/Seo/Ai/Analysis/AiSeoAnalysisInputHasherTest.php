<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai\Analysis;

use App\Domain\Seo\Ai\Analysis\Services\AiSeoAnalysisInputHasher;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use PHPUnit\Framework\TestCase;

final class AiSeoAnalysisInputHasherTest extends TestCase
{
    public function test_hash_is_stable_for_equivalent_key_order(): void
    {
        $hasher =
            new AiSeoAnalysisInputHasher(
                new AiSeoSnapshotHasher,
            );

        $first =
            $hasher->hash(
                'ai-seo-analysis-v1',
                [
                    'entity' => [
                        'id' => 10,

                        'name' => 'Cable',
                    ],
                ],
                [
                    'title' => 'Cable | Store',

                    'robots_index' => true,
                ],
            );

        $second =
            $hasher->hash(
                'ai-seo-analysis-v1',
                [
                    'entity' => [
                        'name' => 'Cable',

                        'id' => 10,
                    ],
                ],
                [
                    'robots_index' => true,

                    'title' => 'Cable | Store',
                ],
            );

        self::assertSame(
            $first,
            $second,
        );
    }

    public function test_analysis_version_is_part_of_input_identity(): void
    {
        $hasher =
            new AiSeoAnalysisInputHasher(
                new AiSeoSnapshotHasher,
            );

        self::assertNotSame(
            $hasher->hash(
                'ai-seo-analysis-v1',
                [
                    'entity' => [
                        'id' => 1,
                    ],
                ],
                [
                    'title' => 'Title',
                ],
            ),
            $hasher->hash(
                'ai-seo-analysis-v2',
                [
                    'entity' => [
                        'id' => 1,
                    ],
                ],
                [
                    'title' => 'Title',
                ],
            ),
        );
    }
}
