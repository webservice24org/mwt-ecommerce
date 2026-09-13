<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\CreateAttributeData;
use App\Models\ProductAttribute;
use App\Support\Slugs\UniqueSlugGenerator;
use Illuminate\Support\Facades\DB;

final readonly class CreateAttributeAction
{
    public function __construct(
        private UniqueSlugGenerator $slugGenerator,
    ) {}

    public function execute(
        CreateAttributeData $data,
    ): ProductAttribute {
        return DB::transaction(
            function () use ($data): ProductAttribute {
                $slug = $this
                    ->slugGenerator
                    ->generate(
                        table: 'attributes',
                        column: 'slug',
                        value: $data->slug
                            ?? $data->name,
                    );

                return ProductAttribute::query()
                    ->create([
                        'name' => $data->name,
                        'slug' => $slug,
                        'position' => $data->position,
                        'is_active' => $data->isActive,
                    ]);
            },
        );
    }
}
