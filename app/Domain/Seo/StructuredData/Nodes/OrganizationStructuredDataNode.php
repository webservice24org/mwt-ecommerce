<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Nodes;

use App\Domain\Seo\StructuredData\Contracts\StructuredDataNode;

final readonly class OrganizationStructuredDataNode implements StructuredDataNode
{
    /**
     * @param  list<string>  $sameAs
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $url,
        public ?string $logo = null,
        public array $sameAs = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toStructuredData(): array
    {
        $data = [
            '@type' => 'Organization',

            '@id' => $this->id,

            'name' => $this->name,

            'url' => $this->url,
        ];

        if ($this->logo !== null) {
            $data['logo'] = [
                '@type' => 'ImageObject',

                'url' => $this->logo,
            ];
        }

        if ($this->sameAs !== []) {
            $data['sameAs'] =
                $this->sameAs;
        }

        return $data;
    }
}
