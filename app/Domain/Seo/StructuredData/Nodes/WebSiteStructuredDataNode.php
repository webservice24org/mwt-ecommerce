<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Nodes;

use App\Domain\Seo\StructuredData\Contracts\StructuredDataNode;

final readonly class WebSiteStructuredDataNode implements StructuredDataNode
{
    public function __construct(
        public string $id,
        public string $name,
        public string $url,
        public ?string $publisherId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toStructuredData(): array
    {
        $data = [
            '@type' => 'WebSite',

            '@id' => $this->id,

            'name' => $this->name,

            'url' => $this->url,
        ];

        if (
            $this->publisherId !==
            null
        ) {
            $data['publisher'] = [
                '@id' => $this->publisherId,
            ];
        }

        return $data;
    }
}
