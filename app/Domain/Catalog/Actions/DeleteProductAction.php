<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

final class DeleteProductAction
{
    public function execute(Product $product): void
    {
        DB::transaction(
            static function () use ($product): void {
                $product->delete();
            },
        );
    }
}
