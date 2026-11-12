<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Cart\Services\CartPromoSession;
use App\Domain\Cart\Services\CartService;
use App\Domain\Promotion\Data\PromoValidationContext;
use App\Domain\Promotion\Services\PromoValidationService;
use App\Domain\Promotion\Support\PromoValidationMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\Cart\ApplyCartPromoCodeRequest;
use App\Models\PromoCode;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;

final class CartPromoCodeController extends Controller
{
    public function store(
        ApplyCartPromoCodeRequest $request,
        CartService $cartService,
        PromoValidationService $validationService,
        CartPromoSession $promoSession,
    ): RedirectResponse {
        $code =
            (string) $request->validated(
                'code',
            );

        $cart =
            $cartService->summary();

        if ($cart->itemCount === 0) {
            $promoSession->forget();

            return back()->withErrors([
                'promo_code' => 'Add an item to your cart before applying a promo code.',
            ]);
        }

        $promoCode =
            PromoCode::query()
                ->where(
                    'code',
                    $code,
                )
                ->first();

        if (
            ! $promoCode instanceof PromoCode
        ) {
            $promoSession->forget();

            return back()->withErrors([
                'promo_code' => PromoValidationMessage::forReason(
                    null,
                ),
            ]);
        }

        $userId =
            auth('web')->id();

        $validation =
            $validationService->validate(
                $promoCode,
                new PromoValidationContext(
                    subtotal: $cart->subtotal,

                    userId: is_int($userId)
                    ? $userId
                    : null,

                    now: CarbonImmutable::now(),
                ),
            );

        if (! $validation->eligible) {
            $promoSession->forget();

            return back()->withErrors([
                'promo_code' => PromoValidationMessage::forReason(
                    $validation->reason,
                ),
            ]);
        }

        $promoSession->put(
            $promoCode->id,
        );

        return back()->with(
            'success',
            sprintf(
                'Promo code %s applied successfully.',
                $promoCode->code,
            ),
        );
    }

    public function destroy(
        CartPromoSession $promoSession,
    ): RedirectResponse {
        $promoSession->forget();

        return back()->with(
            'success',
            'Promo code removed successfully.',
        );
    }
}
