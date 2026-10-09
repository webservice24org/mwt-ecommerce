<?php

declare(strict_types=1);

namespace App\Domain\FooterBuilder\Data;

use App\Domain\FooterBuilder\Enums\FooterTemplate;

final readonly class FooterTemplateData
{
    public function __construct(
        public FooterTemplate $template,
        public string $key,
        public string $label,
        public string $description,
    ) {}

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     description: string
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,

            'label' => $this->label,

            'description' => $this->description,
        ];
    }
}
