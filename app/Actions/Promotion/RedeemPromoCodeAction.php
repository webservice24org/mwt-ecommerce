<?php

declare(strict_types=1);

namespace App\Actions\Promotion;

use App\Domain\Promotion\Data\PromoRedemptionResult;
use App\Domain\Promotion\Data\PromoValidationContext;
use App\Domain\Promotion\Exceptions\PromoRedemptionException;
use App\Domain\Promotion\Services\PromoCodeResolver;
use App\Domain\Promotion\Services\PromoDiscountCalculator;
use App\Domain\Promotion\Services\PromoValidationService;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class RedeemPromoCodeAction
{
    public function __construct(
        private readonly PromoValidationService $validationService,
        private readonly PromoCodeResolver $resolver,
        private readonly PromoDiscountCalculator $discountCalculator,
    ) {}

    public function execute(
        PromoCode $promoCode,
        Order $order,
        int $subtotal,
        ?int $userId = null,
        ?CarbonImmutable $now = null,
    ): PromoRedemptionResult {
        if ($subtotal < 0) {
            throw new PromoRedemptionException(
                'Promo redemption subtotal cannot be negative.',
            );
        }

        if (
            $userId !== null &&
            (int) $order->user_id !== $userId
        ) {
            throw new PromoRedemptionException(
                'Promo redemption customer does not match the order.',
            );
        }

        $now ??= CarbonImmutable::now();

        return DB::transaction(
            function () use ($promoCode, $order, $subtotal, $userId, $now): PromoRedemptionResult {
                /*
                 * This row lock serializes every redemption
                 * attempt for the same promo code.
                 *
                 * Global and customer usage checks below are
                 * therefore evaluated against stable state.
                 */
                $lockedPromoCode = PromoCode::query()
                    ->whereKey($promoCode->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * Idempotency:
                 *
                 * Checkout/order retries must never redeem the
                 * same promo twice for the same order.
                 */
                $existingUsage = PromoCodeUsage::query()
                    ->where(
                        'promo_code_id',
                        $lockedPromoCode->id,
                    )
                    ->where(
                        'order_id',
                        $order->id,
                    )
                    ->first();

                if ($existingUsage !== null) {
                    return new PromoRedemptionResult(
                        usage: $existingUsage,
                        discountAmount: $existingUsage->discount_amount,
                        redeemedNow: false,
                    );
                }

                /*
                 * Revalidate only after obtaining the row lock.
                 *
                 * Validation done before this transaction would
                 * not be enough for usage-limit correctness.
                 */
                $validation =
                    $this->validationService->validate(
                        promoCode: $lockedPromoCode,
                        context: new PromoValidationContext(
                            subtotal: $subtotal,
                            userId: $userId,
                            now: $now,
                        ),
                    );

                if (! $validation->eligible) {
                    throw new PromoRedemptionException(
                        $validation->reason
                        ?? 'This promo code cannot be redeemed.',
                    );
                }

                $resolved =
                    $this->resolver->resolve(
                        $lockedPromoCode,
                    );

                $calculation =
                    $this->discountCalculator->calculate(
                        promo: $resolved,
                        subtotal: $subtotal,
                    );

                $usage = PromoCodeUsage::query()->create([
                    'promo_code_id' => $lockedPromoCode->id,

                    'user_id' => $userId,

                    'order_id' => $order->id,

                    'code' => $lockedPromoCode->code,

                    'discount_amount' => $calculation->discountAmount,

                    'used_at' => $now,
                ]);

                /*
                 * Counter update happens while holding the same
                 * promo-code row lock.
                 */
                $lockedPromoCode->increment(
                    'redeemed_count',
                );

                return new PromoRedemptionResult(
                    usage: $usage,
                    discountAmount: $calculation->discountAmount,
                    redeemedNow: true,
                );
            },
            attempts: 3,
        );
    }
}
