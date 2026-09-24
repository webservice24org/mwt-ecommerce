<?php

namespace App\Domain\PageBuilder\Data;

final readonly class SectionTemplateData
{
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public string $category,
    ) {}

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     category: string
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'description' => $this->description,
            'category' => $this->category,
        ];
    }
}
