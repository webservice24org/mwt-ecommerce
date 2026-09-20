<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\UpdateAttributeData;
use App\Models\ProductAttribute;
use App\Support\Cache\StorefrontCatalogCache;
use App\Support\Slugs\UniqueSlugGenerator;
use Illuminate\Support\Facades\DB;

final readonly class UpdateAttributeAction
{
    public function __construct(
        private UniqueSlugGenerator $slugGenerator,
        private StorefrontCatalogCache $storefrontCache,
    ) {}

    public function execute(
        ProductAttribute $attribute,
        UpdateAttributeData $data,
    ): ProductAttribute {
        $updatedAttribute = DB::transaction(
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

        $this->storefrontCache->invalidate();

        return $updatedAttribute;
    }
}
