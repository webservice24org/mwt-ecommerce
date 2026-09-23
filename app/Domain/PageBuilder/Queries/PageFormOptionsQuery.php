<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Queries;

use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\PageType;

final class PageFormOptionsQuery
{
    /**
     * @return array{
     *     types: list<array{
     *         value: string,
     *         label: string
     *     }>,
     *     statuses: list<array{
     *         value: string,
     *         label: string
     *     }>
     * }
     */
    public function get(): array
    {
        return [
            'types' => array_map(
                static fn (PageType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ],
                PageType::cases(),
            ),

            'statuses' => array_map(
                static fn (PageStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                PageStatus::cases(),
            ),

            'content_modes' => array_map(
                static fn (PageContentMode $mode): array => [
                    'value' => $mode->value,
                    'label' => $mode->label(),
                ],
                PageContentMode::cases(),
            ),
        ];
    }
}
