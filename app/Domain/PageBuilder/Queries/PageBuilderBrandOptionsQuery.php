<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Queries;

use App\Models\Brand;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

final class PageBuilderBrandOptionsQuery
{
    private const MAX_RESULTS = 20;

    private const MAX_SELECTED_BRANDS = 24;

    /**
     * @param  list<int>  $ids
     * @return list<array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     logo_url: string|null,
     *     is_active: bool
     * }>
     */
    public function get(
        ?string $search = null,
        array $ids = [],
    ): array {
        $ids =
            $this->normalizeIds(
                $ids,
            );

        /*
         * Explicit IDs are handled separately so
         * saved manual selections retain their
         * configured order and can still display
         * an inactive Brand in the editor.
         */
        if ($ids !== []) {
            return $this->selected(
                $ids,
            );
        }

        $search =
            trim(
                $search ?? '',
            );

        $query =
            Brand::query()
                ->where(
                    'is_active',
                    true,
                );

        if ($search !== '') {
            $query->where(
                'name',
                'like',
                '%'.$search.'%',
            );
        }

        return $query
            ->orderBy(
                'position',
            )
            ->orderBy(
                'name',
            )
            ->limit(
                self::MAX_RESULTS,
            )
            ->get([
                'id',
                'name',
                'slug',
                'logo_path',
                'is_active',
            ])
            ->map(
                fn (
                    Brand $brand,
                ): array => $this->option(
                    $brand,
                ),
            )
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $ids
     * @return list<array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     logo_url: string|null,
     *     is_active: bool
     * }>
     */
    private function selected(
        array $ids,
    ): array {
        $brands =
            Brand::query()
                ->whereIn(
                    'id',
                    $ids,
                )
                ->get([
                    'id',
                    'name',
                    'slug',
                    'logo_path',
                    'is_active',
                ])
                ->keyBy(
                    'id',
                );

        $result = [];

        foreach (
            $ids as $id
        ) {
            $brand =
                $brands->get(
                    $id,
                );

            if (
                ! $brand instanceof Brand
            ) {
                continue;
            }

            $result[] =
                $this->option(
                    $brand,
                );
        }

        return $result;
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     logo_url: string|null,
     *     is_active: bool
     * }
     */
    private function option(
        Brand $brand,
    ): array {
        return [
            'id' => $brand->id,

            'name' => $brand->name,

            'slug' => $brand->slug,

            'logo_url' => $this->logoUrl(
                $brand->logo_path,
            ),

            'is_active' => $brand->is_active,
        ];
    }

    private function logoUrl(
        ?string $path,
    ): ?string {
        if (
            $path === null ||
            $path === ''
        ) {
            return null;
        }

        /*
         * Explicit adapter typing keeps PHPStan
         * aware that url() exists on this disk.
         */
        /** @var FilesystemAdapter $disk */
        $disk =
            Storage::disk(
                'public',
            );

        return $disk->url(
            $path,
        );
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function normalizeIds(
        array $ids,
    ): array {
        $normalized = [];

        foreach (
            $ids as $id
        ) {
            if (
                $id < 1 ||
                in_array(
                    $id,
                    $normalized,
                    true,
                )
            ) {
                continue;
            }

            $normalized[] =
                $id;

            if (
                count(
                    $normalized,
                ) >=
                self::MAX_SELECTED_BRANDS
            ) {
                break;
            }
        }

        return $normalized;
    }
}
