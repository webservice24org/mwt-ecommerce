<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Catalog;

use App\Domain\Catalog\Actions\DeleteProductImageAction;
use App\Domain\Catalog\Actions\ReorderProductImagesAction;
use App\Domain\Catalog\Actions\SetFeaturedProductImageAction;
use App\Domain\Catalog\Actions\UpdateProductImageAction;
use App\Domain\Catalog\Actions\UploadProductImageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\ReorderProductImagesRequest;
use App\Http\Requests\Admin\Catalog\StoreProductImageRequest;
use App\Http\Requests\Admin\Catalog\UpdateProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;

final class ProductImageController extends Controller
{
    public function store(
        StoreProductImageRequest $request,
        Product $product,
        UploadProductImageAction $action,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $product,
        );

        $action->execute(
            product: $product,
            data: $request->toData(),
        );

        return back()->with(
            'success',
            'Product image uploaded successfully.',
        );
    }

    public function update(
        UpdateProductImageRequest $request,
        Product $product,
        ProductImage $image,
        UpdateProductImageAction $action,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $product,
        );

        $this->ensureOwnership(
            $product,
            $image,
        );

        $action->execute(
            product: $product,
            image: $image,
            data: $request->toData(),
        );

        return back()->with(
            'success',
            'Product image updated successfully.',
        );
    }

    public function featured(
        Product $product,
        ProductImage $image,
        SetFeaturedProductImageAction $action,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $product,
        );

        $this->ensureOwnership(
            $product,
            $image,
        );

        $action->execute(
            product: $product,
            image: $image,
        );

        return back()->with(
            'success',
            'Featured image updated successfully.',
        );
    }

    public function reorder(
        ReorderProductImagesRequest $request,
        Product $product,
        ReorderProductImagesAction $action,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $product,
        );

        $action->execute(
            product: $product,
            imageIds: $request->imageIds(),
        );

        return back()->with(
            'success',
            'Product gallery reordered successfully.',
        );
    }

    public function destroy(
        Product $product,
        ProductImage $image,
        DeleteProductImageAction $action,
    ): RedirectResponse {
        $this->authorize(
            'delete',
            $product,
        );

        $this->ensureOwnership(
            $product,
            $image,
        );

        $action->execute(
            product: $product,
            image: $image,
        );

        return back()->with(
            'success',
            'Product image deleted successfully.',
        );
    }

    private function ensureOwnership(
        Product $product,
        ProductImage $image,
    ): void {
        abort_unless(
            $image->product_id === $product->id,
            404,
        );
    }
}
