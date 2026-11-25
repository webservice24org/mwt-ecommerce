<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Services;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\CanonicalUrlResolver;
use App\Domain\Seo\StructuredData\Data\BreadcrumbStructuredDataItemData;
use App\Domain\Seo\StructuredData\Data\StructuredDataDocument;
use App\Domain\Seo\StructuredData\Nodes\BreadcrumbListStructuredDataNode;

final readonly class BreadcrumbStructuredDataResolver
{
    public function __construct(
        private StructuredDataResolver $structuredData,
        private CanonicalUrlResolver $canonical,
    ) {}

    /**
     * @param  list<BreadcrumbStructuredDataItemData>  $items
     */
    public function resolve(
        SeoSettingsData $settings,
        array $items,
    ): ?StructuredDataDocument {
        if (
            ! $settings->enableStructuredData
        ) {
            return null;
        }

        $items =
            $this->normalizeItems(
                settings: $settings,

                items: $items,
            );

        /*
         * A useful BreadcrumbList needs at least
         * an ancestor and the current page.
         */
        if (
            count($items) < 2
        ) {
            return null;
        }

        return $this
            ->structuredData
            ->resolve(
                settings: $settings,

                nodes: [
                    new BreadcrumbListStructuredDataNode(
                        $items,
                    ),
                ],
            );
    }

    /**
     * @param  list<BreadcrumbStructuredDataItemData>  $items
     * @return list<BreadcrumbStructuredDataItemData>
     */
    private function normalizeItems(
        SeoSettingsData $settings,
        array $items,
    ): array {
        $resolved = [];

        foreach ($items as $item) {
            $name =
                trim(
                    $item->name,
                );

            if ($name === '') {
                continue;
            }

            $resolved[] =
                new BreadcrumbStructuredDataItemData(
                    name: $name,

                    path: $item->path === null
                    ? null
                    : $this->resolvePath(
                        settings: $settings,

                        path: $item->path,
                    ),
                );
        }

        return $resolved;
    }

    private function resolvePath(
        SeoSettingsData $settings,
        string $path,
    ): ?string {
        $path =
            trim(
                $path,
            );

        if (
            $path === ''
            || ! str_starts_with(
                $path,
                '/',
            )
            || str_starts_with(
                $path,
                '//',
            )
        ) {
            return null;
        }

        $host =
            $this->canonicalHost(
                $settings,
            );

        if ($host === null) {
            return null;
        }

        return $this
            ->canonical
            ->resolve(
                canonicalOverride: null,

                path: $path,

                canonicalHost: $host,
            );
    }

    private function canonicalHost(
        SeoSettingsData $settings,
    ): ?string {
        $host =
            trim(
                (string) (
                    $settings
                        ->canonicalHost
                    ?? config(
                        'app.url',
                        '',
                    )
                ),
            );

        return $host === ''
            ? null
            : rtrim(
                $host,
                '/',
            );
    }
}
