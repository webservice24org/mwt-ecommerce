<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\UpdateAttributeValueData;
use App\Domain\Catalog\Services\AttributeValueSlugGenerator;
use App\Models\AttributeValue;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Support\Facades\DB;

final readonly class UpdateAttributeValueAction
{
    public function __construct(
        private AttributeValueSlugGenerator $slugGenerator,
        private StorefrontCatalogCache $storefrontCache,
    ) {}

    public function execute(
        AttributeValue $value,
        UpdateAttributeValueData $data,
    ): AttributeValue {
        $updatedValue = DB::transaction(
            function () use (
                $value,
                $data,
            ): AttributeValue {
                $slug = $this
                    ->slugGenerator
                    ->generate(
                        attributeId: $value->attribute_id,
                        value: $data->slug
                            ?? $data->name,
                        ignoreId: $value->id,
                    );

                $value->update([
                    'name' => $data->name,
                    'slug' => $slug,
                    'position' => $data->position,
                    'is_active' => $data->isActive,
                ]);

                return $value->refresh();
            },
        );

        $this->storefrontCache->invalidate();

        return $updatedValue;
    }
}
