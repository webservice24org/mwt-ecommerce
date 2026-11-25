<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai;

use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use PHPUnit\Framework\TestCase;

final class AiSeoSnapshotHasherTest extends TestCase
{
    public function test_associative_key_order_does_not_change_hash(): void
    {
        $hasher =
            new AiSeoSnapshotHasher;

        $first =
            $hasher->hash([
                'name' => 'USB Cable',

                'seo' => [
                    'description' => 'Fast cable.',

                    'title' => 'Cable',
                ],
            ]);

        $second =
            $hasher->hash([
                'seo' => [
                    'title' => 'Cable',

                    'description' => 'Fast cable.',
                ],

                'name' => 'USB Cable',
            ]);

        self::assertSame(
            $first,
            $second,
        );
    }

    public function test_list_order_remains_part_of_snapshot_identity(): void
    {
        $hasher =
            new AiSeoSnapshotHasher;

        self::assertNotSame(
            $hasher->hash([
                'keywords' => [
                    'usb',
                    'cable',
                ],
            ]),
            $hasher->hash([
                'keywords' => [
                    'cable',
                    'usb',
                ],
            ]),
        );
    }
}
