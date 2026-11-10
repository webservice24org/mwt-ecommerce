<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Queries;

use App\Domain\Shipping\Data\ShippingDestinationData;
use App\Domain\Shipping\Data\ShippingRateQuote;
use App\Domain\Shipping\Services\ShippingRateCalculator;
use App\Domain\Shipping\Services\ShippingZoneResolver;
use App\Models\ShippingRate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class AvailableShippingRatesQuery
{
    public function __construct(
        private ShippingRateCalculator $calculator,
        private ShippingZoneResolver $zoneResolver,
    ) {}

    /**
     * @return Collection<int, ShippingRateQuote>
     */
    public function get(
        int $orderSubtotal,
        ?ShippingDestinationData $destination = null,
    ): Collection {
        $zone = $destination !== null
            ? $this->zoneResolver->resolve(
                $destination,
            )
            : null;

        $query = ShippingRate::query()
            ->with([
                'shippingMethod',
                'zones',
            ])
            ->where('is_active', true)
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
                        ->whereNull('min_order_amount')
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
                        ->whereNull('max_order_amount')
                        ->orWhere(
                            'max_order_amount',
                            '>=',
                            $orderSubtotal,
                        );
                },
            );

        if ($destination === null || $zone === null) {
            $query->whereDoesntHave('zones');
        } else {
            $query->where(
                static function (Builder $query) use ($zone): void {
                    $query
                        ->whereDoesntHave('zones')
                        ->orWhereHas(
                            'zones',
                            static function (Builder $zoneQuery) use ($zone): void {
                                $zoneQuery->whereKey(
                                    $zone->getKey(),
                                );
                            },
                        );
                },
            );
        }

        return $query
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->map(
                fn (
                    ShippingRate $rate,
                ): ShippingRateQuote => $this->calculator->quote(
                    rate: $rate,
                    orderSubtotal: $orderSubtotal,
                ),
            );
    }
}
