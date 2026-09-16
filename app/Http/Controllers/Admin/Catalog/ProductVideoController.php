<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Catalog;

use App\Domain\Catalog\Actions\DeleteProductVideoAction;
use App\Domain\Catalog\Actions\SaveProductVideoAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\SaveProductVideoRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;

final class ProductVideoController extends Controller
{
    public function store(
        SaveProductVideoRequest $request,
        Product $product,
        SaveProductVideoAction $action,
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
            'Product video saved successfully.',
        );
    }

    public function destroy(
        Product $product,
        DeleteProductVideoAction $action,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $product,
        );

        $action->execute(
            $product,
        );

        return back()->with(
            'success',
            'Product video removed successfully.',
        );
    }
}
