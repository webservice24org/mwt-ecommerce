<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Cart\Services\CartService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\AddCartItemRequest;
use App\Http\Requests\Frontend\UpdateCartItemRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;

final class CartController extends Controller
{
    public function show(
        CartService $cart,
    ): JsonResponse {
        return response()->json(
            $cart
                ->summary()
                ->toArray(),
        );
    }

    public function store(
        AddCartItemRequest $request,
        CartService $cart,
    ): JsonResponse {
        $product = Product::query()
            ->published()
            ->findOrFail(
                $request->integer(
                    'product_id',
                ),
            );

        $variant = null;

        if (
            $request->filled(
                'variant_id',
            )
        ) {
            $variant = ProductVariant::query()
                ->where(
                    'product_id',
                    $product->id,
                )
                ->where(
                    'is_active',
                    true,
                )
                ->findOrFail(
                    $request->integer(
                        'variant_id',
                    ),
                );
        }

        if (
            $product->type->value ===
            'variable'
            && $variant === null
        ) {
            abort(
                422,
                'A variant is required for this product.',
            );
        }

        if (
            $product->type->value ===
            'simple'
            && $variant !== null
        ) {
            abort(
                422,
                'A simple product cannot use a variant.',
            );
        }

        $summary = $cart->add(
            product: $product,
            variant: $variant,
            quantity: $request->integer(
                'quantity',
                1,
            ),
        );

        return response()->json(
            $summary->toArray(),
        );
    }

    public function update(
        string $key,
        UpdateCartItemRequest $request,
        CartService $cart,
    ): JsonResponse {
        $summary = $cart->update(
            key: $key,
            quantity: $request->integer(
                'quantity',
            ),
        );

        return response()->json(
            $summary->toArray(),
        );
    }

    public function destroy(
        string $key,
        CartService $cart,
    ): JsonResponse {
        return response()->json(
            $cart
                ->remove($key)
                ->toArray(),
        );
    }

    public function clear(
        CartService $cart,
    ): JsonResponse {
        return response()->json(
            $cart
                ->clear()
                ->toArray(),
        );
    }
}
