<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data;

use App\Domain\Catalog\Enums\ProductVideoType;
use Illuminate\Http\UploadedFile;

final readonly class SaveProductVideoData
{
    public function __construct(
        public ProductVideoType $type,
        public ?UploadedFile $video,
        public ?string $url,
        public ?string $title,
    ) {}
}
