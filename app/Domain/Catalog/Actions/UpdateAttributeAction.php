<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\UpdateAttributeData;
use App\Models\ProductAttribute;
use App\Support\Slugs\UniqueSlugGenerator;
use Illuminate\Support\Facades\DB;

final readonly class UpdateAttributeAction
{
    public function __construct(
        private UniqueSlugGenerator $slugGenerator,
    ) {}

    public function execute(
        ProductAttribute $attribute,
        UpdateAttributeData $data,
    ): ProductAttribute {
        return DB::transaction(
            function () use (
                $attribute,
                $data,
            ): ProductAttribute {
                $slug = $this
                    ->slugGenerator
                    ->generate(
                        table: 'attributes',
                        column: 'slug',
                        value: $data->slug
                            ?? $data->name,
                        ignoreId: $attribute->id,
                    );

                $attribute->update([
                    'name' => $data->name,
                    'slug' => $slug,
                    'position' => $data->position,
                    'is_active' => $data->isActive,
                ]);

                return $attribute->refresh();
            },
        );
    }
}
