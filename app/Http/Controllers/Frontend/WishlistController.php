<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Wishlist\Queries\StorefrontWishlistQuery;
use App\Domain\Wishlist\Services\WishlistService;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class WishlistController extends Controller
{
    public function index(
        Request $request,
        StorefrontWishlistQuery $query,
        WishlistService $wishlist,
    ): Response {
        $user =
            $this->customer(
                $request,
            );

        if ($user !== null) {
            $wishlist->mergeGuestInto(
                $user,
            );
        }

        return Inertia::render(
            'Frontend/Wishlist/Index',
            [
                'items' => $query->get(
                    user: $user,
                ),
            ],
        );
    }

    public function toggle(
        Request $request,
        Product $product,
        WishlistService $wishlist,
    ): JsonResponse {
        $available =
            Product::query()
                ->published()
                ->whereKey(
                    $product->id,
                )
                ->exists();

        abort_unless(
            $available,
            404,
        );

        $user =
            $this->customer(
                $request,
            );

        if ($user !== null) {
            $wishlist->mergeGuestInto(
                $user,
            );
        }

        $active =
            $wishlist->toggle(
                product: $product,

                user: $user,
            );

        return response()->json([
            'active' => $active,

            'count' => $wishlist->count(
                user: $user,
            ),
        ]);
    }

    public function destroy(
        Request $request,
        Product $product,
        WishlistService $wishlist,
    ): RedirectResponse {
        $user =
            $this->customer(
                $request,
            );

        if ($user !== null) {
            $wishlist->mergeGuestInto(
                $user,
            );
        }

        $wishlist->remove(
            product: $product,

            user: $user,
        );

        return back();
    }

    public function clear(
        Request $request,
        WishlistService $wishlist,
    ): RedirectResponse {
        $user =
            $this->customer(
                $request,
            );

        if ($user !== null) {
            $wishlist->mergeGuestInto(
                $user,
            );
        }

        $wishlist->clear(
            user: $user,
        );

        return back();
    }

    private function customer(
        Request $request,
    ): ?User {
        $user =
            $request->user();

        return $user instanceof User
            ? $user
            : null;
    }
}
