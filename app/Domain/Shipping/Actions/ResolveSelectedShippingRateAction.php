<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Actions;

use App\Domain\Shipping\Data\ShippingDestinationData;
use App\Domain\Shipping\Data\ShippingRateQuote;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

final readonly class ResolveSelectedShippingRateAction
{
    public function execute(
        int $shippingRateId,
        int $orderSubtotal,
        ?ShippingDestinationData $destination = null,
    ): ShippingRateQuote {
        /*
         * ==================================================
         * MANUAL SHIPPING-ZONE MODE
         * ==================================================
         *
         * Address matching is intentionally not used here.
         *
         * $destination remains in the method signature for
         * compatibility with existing callers and future
         * automatic matching support.
         */
        unset($destination);

        $rate = ShippingRate::query()
            ->with([
                'shippingMethod',
                'zones',
            ])
            ->find(
                $shippingRateId,
            );

        if ($rate === null) {
            $this->unavailable();
        }

        /*
         * ==================================================
         * RATE STATUS
         * ==================================================
         */
        if (
            ! $rate->is_active
        ) {
            $this->unavailable();
        }

        $method =
            $rate->shippingMethod;

        /*
         * ==================================================
         * SHIPPING METHOD STATUS
         * ==================================================
         */
        if (
            $method === null ||
            ! $method->is_active
        ) {
            throw ValidationException::withMessages([
                'shipping_rate_id' => 'The selected shipping method is not available.',
            ]);
        }

        /*
         * ==================================================
         * MANUAL ZONE SECURITY
         * ==================================================
         */
        $activeZones =
            $rate
                ->zones
                ->filter(
                    static fn (
                        ShippingZone $zone,
                    ): bool => $zone->is_active,
                );

        /*
         * A zoned rate attached only to inactive zones
         * cannot be submitted manually.
         */
        if (
            $rate
                ->zones
                ->isNotEmpty() &&
            $activeZones
                ->isEmpty()
        ) {
            throw ValidationException::withMessages([
                'shipping_rate_id' => 'The selected shipping zone is not available.',
            ]);
        }

        /*
         * Once manual zoned shipping has been configured,
         * hidden global/unzoned rates must not be usable by
         * manually changing shipping_rate_id in the browser.
         *
         * Global rates remain supported for legacy/test
         * situations where no manual zones are configured.
         */
        if (
            $rate
                ->zones
                ->isEmpty() &&
            $this
                ->manualZoneCheckoutConfigured()
        ) {
            throw ValidationException::withMessages([
                'shipping_rate_id' => 'Please select an available shipping zone.',
            ]);
        }

        /*
         * ==================================================
         * SUBTOTAL CONDITIONS
         * ==================================================
         */
        if (
            $rate
                ->min_order_amount !==
            null &&
            $orderSubtotal <
            $rate
                ->min_order_amount
        ) {
            throw ValidationException::withMessages([
                'shipping_rate_id' => 'The selected shipping rate is not available for this order total.',
            ]);
        }

        if (
            $rate
                ->max_order_amount !==
            null &&
            $orderSubtotal >
            $rate
                ->max_order_amount
        ) {
            throw ValidationException::withMessages([
                'shipping_rate_id' => 'The selected shipping rate is not available for this order total.',
            ]);
        }

        /*
         * ==================================================
         * PREFERRED RATE SECURITY
         * ==================================================
         *
         * Checkout only exposes the lowest-priority eligible
         * rate for each zone.
         *
         * Therefore a customer must not be able to manually
         * submit another hidden rate attached to that zone.
         */
        if (
            $activeZones
                ->isNotEmpty()
        ) {
            $selectable =
                $activeZones
                    ->contains(
                        function (ShippingZone $zone) use ($rate, $orderSubtotal): bool {
                            $preferredRate =
                                $this
                                    ->preferredRateForZone(
                                        zone: $zone,

                                        orderSubtotal: $orderSubtotal,
                                    );

                            return
                                $preferredRate !==
                                null &&
                                $preferredRate
                                    ->is(
                                        $rate,
                                    );
                        },
                    );

            if (! $selectable) {
                throw ValidationException::withMessages([
                    'shipping_rate_id' => 'The selected shipping rate is not currently available.',
                ]);
            }
        }

        /*
         * ==================================================
         * AUTHORITATIVE DELIVERY PRICE
         * ==================================================
         *
         * Never trust a shipping amount coming from React.
         */
        $free =
            $rate
                ->free_shipping_threshold !==
            null &&
            $orderSubtotal >=
            $rate
                ->free_shipping_threshold;

        $amount =
            $free
            ? 0
            : (int) $rate
                ->amount;

        return new ShippingRateQuote(
            shippingMethodId: (int) $method
                ->id,

            shippingRateId: (int) $rate
                ->id,

            methodCode: $method
                ->code,

            methodName: $method
                ->name,

            rateCode: $rate
                ->code,

            rateName: $rate
                ->name,

            amount: $amount,

            free: $free,
        );
    }

    private function preferredRateForZone(
        ShippingZone $zone,
        int $orderSubtotal,
    ): ?ShippingRate {
        return $zone
            ->rates()
            ->where(
                'shipping_rates.is_active',
                true,
            )
            ->whereHas(
                'shippingMethod',
                static function (Builder $query): void {
                    $query->where(
                        'is_active',
                        true,
                    );
                },
            )
            ->where(
                static function (Builder $query) use ($orderSubtotal): void {
                    $query
                        ->whereNull(
                            'min_order_amount',
                        )
                        ->orWhere(
                            'min_order_amount',
                            '<=',
                            $orderSubtotal,
                        );
                },
            )
            ->where(
                static function (Builder $query) use ($orderSubtotal): void {
                    $query
                        ->whereNull(
                            'max_order_amount',
                        )
                        ->orWhere(
                            'max_order_amount',
                            '>=',
                            $orderSubtotal,
                        );
                },
            )
            ->orderBy(
                'shipping_rates.priority',
            )
            ->orderBy(
                'shipping_rates.id',
            )
            ->first();
    }

    private function manualZoneCheckoutConfigured(): bool
    {
        return ShippingZone::query()
            ->where(
                'is_active',
                true,
            )
            ->whereHas(
                'rates',
                static function (Builder $rateQuery): void {
                    $rateQuery
                        ->where(
                            'shipping_rates.is_active',
                            true,
                        )
                        ->whereHas(
                            'shippingMethod',
                            static function (Builder $methodQuery): void {
                                $methodQuery
                                    ->where(
                                        'is_active',
                                        true,
                                    );
                            },
                        );
                },
            )
            ->exists();
    }

    private function unavailable(): never
    {
        throw ValidationException::withMessages([
            'shipping_rate_id' => 'The selected shipping rate is not available.',
        ]);
    }
}
