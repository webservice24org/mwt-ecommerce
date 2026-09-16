<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data;

use Illuminate\Http\UploadedFile;

final readonly class CreateProductImageData
{
    public function __construct(
        public UploadedFile $image,
        public ?string $altText,
        public bool $isPrimary = false,
    ) {}
}
