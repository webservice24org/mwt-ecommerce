<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\CreateAttributeValueData;
use App\Domain\Catalog\Services\AttributeValueSlugGenerator;
use App\Models\AttributeValue;
use App\Models\ProductAttribute;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Support\Facades\DB;

final readonly class CreateAttributeValueAction
{
    public function __construct(
        private AttributeValueSlugGenerator $slugGenerator,
        private StorefrontCatalogCache $storefrontCache,
    ) {}

    public function execute(
        ProductAttribute $attribute,
        CreateAttributeValueData $data,
    ): AttributeValue {
        $value = DB::transaction(
            function () use (
                $attribute,
                $data,
            ): AttributeValue {
                $slug = $this
                    ->slugGenerator
                    ->generate(
                        attributeId: $attribute->id,
                        value: $data->slug
                            ?? $data->name,
                    );

                return $attribute
                    ->values()
                    ->create([
                        'name' => $data->name,
                        'slug' => $slug,
                        'position' => $data->position,
                        'is_active' => $data->isActive,
                    ]);
            },
        );

        $this->storefrontCache->invalidate();

        return $value;
    }
}
