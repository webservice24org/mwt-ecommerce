<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

final readonly class PageBuilderImageData
{
    public function __construct(
        public string $path,
        public string $url,
        public string $originalName,
        public string $mimeType,
        public int $fileSize,
        public ?int $width,
        public ?int $height,
    ) {}

    /**
     * @return array{
     *     path: string,
     *     url: string,
     *     original_name: string,
     *     mime_type: string,
     *     file_size: int,
     *     width: int|null,
     *     height: int|null
     * }
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'url' => $this->url,
            'original_name' => $this->originalName,
            'mime_type' => $this->mimeType,
            'file_size' => $this->fileSize,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
