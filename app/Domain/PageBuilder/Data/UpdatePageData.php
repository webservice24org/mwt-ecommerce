<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\PageType;
use Carbon\CarbonInterface;

final readonly class UpdatePageData
{
    public function __construct(
        public PageType $type,
        public string $title,
        public ?string $slug,
        public PageStatus $status,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public ?CarbonInterface $publishedAt,
    ) {}
}
