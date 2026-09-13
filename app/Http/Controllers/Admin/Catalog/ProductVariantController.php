<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Catalog;

use App\Domain\Catalog\Actions\CreateProductVariantAction;
use App\Domain\Catalog\Actions\DeleteProductVariantAction;
use App\Domain\Catalog\Actions\UpdateProductVariantAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\StoreProductVariantRequest;
use App\Http\Requests\Admin\Catalog\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;

final class ProductVariantController extends Controller
{
    public function store(
        StoreProductVariantRequest $request,
        Product $product,
        CreateProductVariantAction $action,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $product,
        );

        $action->execute(
            $product,
            $request->toData(),
        );

        return back()->with(
            'success',
            'Product variant created successfully.',
        );
    }

    public function update(
        UpdateProductVariantRequest $request,
        Product $product,
        ProductVariant $variant,
        UpdateProductVariantAction $action,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $product,
        );

        $this->ensureVariantBelongsToProduct(
            $product,
            $variant,
        );

        $action->execute(
            $variant,
            $request->toData(),
        );

        return back()->with(
            'success',
            'Product variant updated successfully.',
        );
    }

    public function destroy(
        Product $product,
        ProductVariant $variant,
        DeleteProductVariantAction $action,
    ): RedirectResponse {
        /*
         * Variant deletion is destructive, so use the
         * product delete permission. Editors can edit
         * variants but cannot delete them.
         */
        $this->authorize(
            'delete',
            $product,
        );

        $this->ensureVariantBelongsToProduct(
            $product,
            $variant,
        );

        $action->execute($variant);

        return back()->with(
            'success',
            'Product variant deleted successfully.',
        );
    }

    private function ensureVariantBelongsToProduct(
        Product $product,
        ProductVariant $variant,
    ): void {
        abort_unless(
            $variant->product_id === $product->id,
            404,
        );
    }
}
