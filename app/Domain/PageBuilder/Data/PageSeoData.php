<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

final readonly class PageSeoData
{
    public function __construct(
        public ?string $metaTitle,
        public ?string $metaDescription,
    ) {}

    /**
     * @return array{
     *     meta_title: string|null,
     *     meta_description: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
        ];
    }
}
