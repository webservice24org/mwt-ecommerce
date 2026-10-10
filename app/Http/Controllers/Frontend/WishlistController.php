<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Wishlist\Queries\StorefrontWishlistQuery;
use App\Domain\Wishlist\Services\WishlistService;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class WishlistController extends Controller
{
    public function index(
        StorefrontWishlistQuery $query,
    ): Response {
        return Inertia::render(
            'Frontend/Wishlist/Index',
            [
                'products' => $query->get(),
            ],
        );
    }

    public function toggle(
        Product $product,
        WishlistService $wishlist,
    ): JsonResponse {
        $available = Product::query()
            ->published()
            ->whereKey(
                $product->getKey(),
            )
            ->exists();

        abort_unless(
            $available,
            404,
        );

        $active = $wishlist->toggle(
            $product,
        );

        return response()->json([
            'active' => $active,
            'count' => $wishlist->count(),
        ]);
    }

    public function destroy(
        Product $product,
        WishlistService $wishlist,
    ): RedirectResponse {
        $wishlist->remove(
            $product,
        );

        return back();
    }

    public function clear(
        WishlistService $wishlist,
    ): RedirectResponse {
        $wishlist->clear();

        return back();
    }
}
