<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\CreateAttributeValueData;
use App\Domain\Catalog\Services\AttributeValueSlugGenerator;
use App\Models\AttributeValue;
use App\Models\ProductAttribute;
use Illuminate\Support\Facades\DB;

final readonly class CreateAttributeValueAction
{
    public function __construct(
        private AttributeValueSlugGenerator $slugGenerator,
    ) {}

    public function execute(
        ProductAttribute $attribute,
        CreateAttributeValueData $data,
    ): AttributeValue {
        return DB::transaction(
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
    }
}
