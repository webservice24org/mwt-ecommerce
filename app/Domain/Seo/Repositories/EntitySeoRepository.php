<?php

declare(strict_types=1);

namespace App\Domain\Seo\Repositories;

use App\Domain\Seo\Contracts\Seoable;
use App\Domain\Seo\Data\EntitySeoData;
use App\Domain\Seo\Services\EntitySeoMapper;
use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

final readonly class EntitySeoRepository
{
    public function __construct(
        private EntitySeoMapper $mapper,
    ) {}

    public function get(
        Seoable&Model $entity,
    ): EntitySeoData {
        $meta =
            $entity
                ->morphOne(
                    SeoMeta::class,
                    'seoable',
                )
                ->first();

        if (
            $meta === null
        ) {
            return EntitySeoData::empty();
        }

        return $this
            ->mapper
            ->fromModel(
                $meta,
            );
    }

    public function save(
        Seoable&Model $entity,
        EntitySeoData $data,
    ): EntitySeoData {
        if (
            ! $entity->exists
        ) {
            throw new LogicException(
                'SEO metadata cannot be saved for an unsaved entity.',
            );
        }

        return DB::transaction(
            function () use ($entity, $data): EntitySeoData {
                $relation =
                    $entity->morphOne(
                        SeoMeta::class,
                        'seoable',
                    );

                $meta =
                    $relation
                        ->lockForUpdate()
                        ->first();

                if (
                    ! $data->hasOverrides()
                ) {
                    if (
                        $meta !== null
                    ) {
                        $meta->delete();
                    }

                    return EntitySeoData::empty();
                }

                if (
                    $meta === null
                ) {
                    $meta =
                        $relation
                            ->make();
                }

                $meta->fill(
                    $data->toArray(),
                );

                $meta->save();

                return $this
                    ->mapper
                    ->fromModel(
                        $meta,
                    );
            },
        );
    }

    public function delete(
        Seoable&Model $entity,
    ): void {
        DB::transaction(
            function () use ($entity): void {
                $entity
                    ->morphOne(
                        SeoMeta::class,
                        'seoable',
                    )
                    ->delete();
            },
        );
    }
}
