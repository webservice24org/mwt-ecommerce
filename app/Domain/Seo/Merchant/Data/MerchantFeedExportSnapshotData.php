<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Data;

use JsonException;

final readonly class MerchantFeedExportSnapshotData
{
    public const SCHEMA_VERSION =
        'merchant-tsv-v1';

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public string $schemaVersion,
        public string $readinessPolicyVersion,
        public int $merchantPolicyVersion,
        public int $productsConsidered,
        public int $itemCandidates,
        public int $exportableCount,
        public int $excludedCount,
        public int $outOfStockCount,
        public int $blockerCount,
        public int $warningCount,
        public int $infoCount,
        public bool $truncated,
        public int $maxItems,
        public array $snapshot,
        public string $hash,
    ) {}

    /**
     * @param  array<string, mixed>  $snapshot
     *
     * @throws JsonException
     */
    public static function hash(
        array $snapshot,
    ): string {
        return hash(
            'sha256',
            json_encode(
                $snapshot,
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE,
            ),
        );
    }
}
