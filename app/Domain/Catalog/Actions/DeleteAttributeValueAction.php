<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Models\AttributeValue;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DeleteAttributeValueAction
{
    public function __construct(
        private readonly StorefrontCatalogCache $storefrontCache,
    ) {}

    public function execute(
        AttributeValue $value,
    ): void {
        DB::transaction(
            static function () use ($value): void {
                if (
                    $value
                        ->variants()
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'attribute_value' => 'This attribute value is used by one or more product variants and cannot be deleted.',
                    ]);
                }

                $value->delete();
            },
        );

        $this->storefrontCache->invalidate();
    }
}
