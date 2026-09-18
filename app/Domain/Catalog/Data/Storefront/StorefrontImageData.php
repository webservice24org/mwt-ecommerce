<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data\Storefront;

final readonly class StorefrontImageData
{
    public function __construct(
        public int $id,
        public string $url,
        public ?string $alt,
        public ?int $width,
        public ?int $height,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     url: string,
     *     alt: string|null,
     *     width: int|null,
     *     height: int|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'alt' => $this->alt,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
