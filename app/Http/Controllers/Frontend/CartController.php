<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Cart\Data\CartSummaryData;
use App\Domain\Cart\Services\CartPromoPricingService;
use App\Domain\Cart\Services\CartService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\AddCartItemRequest;
use App\Http\Requests\Frontend\UpdateCartItemRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class CartController extends Controller
{
    public function show(
        CartService $cart,
        CartPromoPricingService $pricing,
    ): JsonResponse {
        return $this->cartResponse(
            summary: $cart->summary(),

            pricing: $pricing,
        );
    }

    public function store(
        AddCartItemRequest $request,
        CartService $cart,
        CartPromoPricingService $pricing,
    ): JsonResponse {
        $product =
            Product::query()
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
            $variant =
                ProductVariant::query()
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

        $summary =
            $cart->add(
                product: $product,

                variant: $variant,

                quantity: $request->integer(
                    'quantity',
                    1,
                ),
            );

        return $this->cartResponse(
            summary: $summary,

            pricing: $pricing,
        );
    }

    public function update(
        string $key,
        UpdateCartItemRequest $request,
        CartService $cart,
        CartPromoPricingService $pricing,
    ): JsonResponse {
        $summary =
            $cart->update(
                key: $key,

                quantity: $request->integer(
                    'quantity',
                ),
            );

        return $this->cartResponse(
            summary: $summary,

            pricing: $pricing,
        );
    }

    public function destroy(
        string $key,
        CartService $cart,
        CartPromoPricingService $pricing,
    ): JsonResponse {
        $summary =
            $cart->remove(
                $key,
            );

        return $this->cartResponse(
            summary: $summary,

            pricing: $pricing,
        );
    }

    public function clear(
        CartService $cart,
        CartPromoPricingService $pricing,
    ): JsonResponse {
        /*
         * CartService::clear() also removes
         * the selected promo session state.
         */
        $summary =
            $cart->clear();

        return $this->cartResponse(
            summary: $summary,

            pricing: $pricing,
        );
    }

    private function cartResponse(
        CartSummaryData $summary,
        CartPromoPricingService $pricing,
    ): JsonResponse {
        return response()->json(
            $pricing
                ->calculate(
                    cart: $summary,

                    userId: $this->customerId(),
                )
                ->toArray(),
        );
    }

    private function customerId(): ?int
    {
        $id =
            Auth::guard(
                'web',
            )->id();

        if (is_int($id)) {
            return $id;
        }

        if (
            is_string($id)
            && ctype_digit($id)
        ) {
            return (int) $id;
        }

        return null;
    }
}
