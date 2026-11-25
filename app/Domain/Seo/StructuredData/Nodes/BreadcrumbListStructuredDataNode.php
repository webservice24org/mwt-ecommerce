<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Nodes;

use App\Domain\Seo\StructuredData\Contracts\StructuredDataNode;
use App\Domain\Seo\StructuredData\Data\BreadcrumbStructuredDataItemData;

final readonly class BreadcrumbListStructuredDataNode implements StructuredDataNode
{
    /**
     * @param  list<BreadcrumbStructuredDataItemData>  $items
     */
    public function __construct(
        public array $items,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toStructuredData(): array
    {
        $elements = [];

        foreach (
            $this->items as $index => $item
        ) {
            $element = [
                '@type' => 'ListItem',

                'position' => $index + 1,

                'name' => $item->name,
            ];

            if ($item->path !== null) {
                $element['item'] =
                    $item->path;
            }

            $elements[] =
                $element;
        }

        return [
            '@type' => 'BreadcrumbList',

            'itemListElement' => $elements,
        ];
    }
}
