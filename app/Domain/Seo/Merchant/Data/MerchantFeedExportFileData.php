<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Data;

final readonly class MerchantFeedExportFileData
{
    public function __construct(
        public string $disk,
        public string $path,
        public string $name,
        public string $mimeType,
        public int $size,
        public string $sha256,
    ) {}
}
