<?php

declare(strict_types=1);

namespace App\Domain\Seo\Data;

final readonly class SeoMediaData
{
    public function __construct(
        public string $path,
        public string $url,
    ) {}

    /**
     * @return array{
     *     path: string,
     *     url: string
     * }
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,

            'url' => $this->url,
        ];
    }
}
