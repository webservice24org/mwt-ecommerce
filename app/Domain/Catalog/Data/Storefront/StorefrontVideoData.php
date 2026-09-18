<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data\Storefront;

final readonly class StorefrontVideoData
{
    public function __construct(
        public string $type,
        public ?string $url,
    ) {}

    /**
     * @return array{
     *     type: string,
     *     url: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'url' => $this->url,
        ];
    }
}
