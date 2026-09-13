<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries;

use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Collection;

final class ProductVariantOptionsQuery
{
    /**
     * @return Collection<int, ProductAttribute>
     */
    public function get(): Collection
    {
        return ProductAttribute::query()
            ->with([
                'values' => static fn ($query) => $query
                    ->orderBy('position')
                    ->orderBy('name'),
            ])
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }
}
