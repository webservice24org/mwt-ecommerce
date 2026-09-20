<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Models\ProductAttribute;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DeleteAttributeAction
{
    public function __construct(
        private readonly StorefrontCatalogCache $storefrontCache,
    ) {}

    public function execute(
        ProductAttribute $attribute,
    ): void {
        DB::transaction(
            static function () use (
                $attribute,
            ): void {
                $isInUse = $attribute
                    ->values()
                    ->whereHas('variants')
                    ->exists();

                if ($isInUse) {
                    throw ValidationException::withMessages([
                        'attribute' => 'This attribute is used by one or more product variants and cannot be deleted.',
                    ]);
                }

                $attribute->delete();
            },
        );

        $this->storefrontCache->invalidate();
    }
}
