<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Shipping;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Shipping\StoreShippingRateRequest;
use App\Http\Requests\Admin\Shipping\UpdateShippingRateRequest;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class ShippingRateController extends Controller
{
    public function index(): Response
    {
        $this->authorize(
            'viewAny',
            ShippingRate::class,
        );

        $rates = ShippingRate::query()
            ->with([
                'shippingMethod:id,name,code,is_active',

                'zones:id,name,code',
            ])
            ->orderBy(
                'priority',
            )
            ->orderBy(
                'name',
            )
            ->get()
            ->map(
                static fn (
                    ShippingRate $rate,
                ): array => [
                    'id' => $rate->id,

                    'name' => $rate->name,

                    'code' => $rate->code,

                    'amount' => $rate->amount,

                    'min_order_amount' => $rate->min_order_amount,

                    'max_order_amount' => $rate->max_order_amount,

                    'free_shipping_threshold' => $rate->free_shipping_threshold,

                    'is_active' => $rate->is_active,

                    'priority' => $rate->priority,

                    'shipping_method' => [
                        'id' => $rate
                            ->shippingMethod
                            ->id,

                        'name' => $rate
                            ->shippingMethod
                            ->name,

                        'code' => $rate
                            ->shippingMethod
                            ->code,

                        'is_active' => $rate
                            ->shippingMethod
                            ->is_active,
                    ],

                    'zones' => $rate
                        ->zones
                        ->map(
                            static fn (
                                ShippingZone $zone,
                            ): array => [
                                'id' => $zone->id,

                                'name' => $zone->name,

                                'code' => $zone->code,
                            ],
                        )
                        ->values()
                        ->all(),

                    'is_global' => $rate
                        ->zones
                        ->isEmpty(),
                ],
            )
            ->values();

        return Inertia::render(
            'Admin/Shipping/Rates/Index',
            [
                'rates' => $rates,
            ],
        );
    }

    public function create(): Response
    {
        $this->authorize(
            'create',
            ShippingRate::class,
        );

        return Inertia::render(
            'Admin/Shipping/Rates/Create',
            $this->formOptions(),
        );
    }

    public function store(
        StoreShippingRateRequest $request,
    ): RedirectResponse {
        $this->authorize(
            'create',
            ShippingRate::class,
        );

        $validated = $request->validated();

        $rate = DB::transaction(
            function () use ($validated, $request): ShippingRate {
                $rate = ShippingRate::query()
                    ->create([
                        'shipping_method_id' => $validated[
                                'shipping_method_id'
                            ],

                        'name' => $validated['name'],

                        'code' => $validated['code'],

                        'amount' => $validated['amount'],

                        'min_order_amount' => $validated[
                                'min_order_amount'
                            ] ?? null,

                        'max_order_amount' => $validated[
                                'max_order_amount'
                            ] ?? null,

                        'free_shipping_threshold' => $validated[
                                'free_shipping_threshold'
                            ] ?? null,

                        'is_active' => $validated[
                                'is_active'
                            ],

                        'priority' => $validated[
                                'priority'
                            ],

                        'metadata' => null,
                    ]);

                $rate
                    ->zones()
                    ->sync(
                        $request->zoneIds(),
                    );

                return $rate;
            },
        );

        return to_route(
            'admin.shipping.rates.edit',
            $rate,
        )->with(
            'success',
            'Shipping rate created successfully.',
        );
    }

    public function edit(
        ShippingRate $rate,
    ): Response {
        $this->authorize(
            'update',
            $rate,
        );

        $rate->load([
            'zones:id,name,code',
        ]);

        return Inertia::render(
            'Admin/Shipping/Rates/Edit',
            [
                ...$this->formOptions(),

                'rate' => [
                    'id' => $rate->id,

                    'shipping_method_id' => $rate->shipping_method_id,

                    'name' => $rate->name,

                    'code' => $rate->code,

                    'amount' => $rate->amount,

                    'min_order_amount' => $rate->min_order_amount,

                    'max_order_amount' => $rate->max_order_amount,

                    'free_shipping_threshold' => $rate->free_shipping_threshold,

                    'is_active' => $rate->is_active,

                    'priority' => $rate->priority,

                    'zone_ids' => $rate
                        ->zones
                        ->pluck('id')
                        ->map(
                            static fn (
                                mixed $id,
                            ): int => (int) $id,
                        )
                        ->values()
                        ->all(),
                ],
            ],
        );
    }

    public function update(
        UpdateShippingRateRequest $request,
        ShippingRate $rate,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $rate,
        );

        $validated = $request->validated();

        DB::transaction(
            function () use ($rate, $validated, $request): void {
                $rate->update([
                    'shipping_method_id' => $validated[
                            'shipping_method_id'
                        ],

                    'name' => $validated['name'],

                    'code' => $validated['code'],

                    'amount' => $validated['amount'],

                    'min_order_amount' => $validated[
                            'min_order_amount'
                        ] ?? null,

                    'max_order_amount' => $validated[
                            'max_order_amount'
                        ] ?? null,

                    'free_shipping_threshold' => $validated[
                            'free_shipping_threshold'
                        ] ?? null,

                    'is_active' => $validated[
                            'is_active'
                        ],

                    'priority' => $validated[
                            'priority'
                        ],
                ]);

                $rate
                    ->zones()
                    ->sync(
                        $request->zoneIds(),
                    );
            },
        );

        return to_route(
            'admin.shipping.rates.edit',
            $rate,
        )->with(
            'success',
            'Shipping rate updated successfully.',
        );
    }

    public function destroy(
        ShippingRate $rate,
    ): RedirectResponse {
        $this->authorize(
            'delete',
            $rate,
        );

        $rate->delete();

        return to_route(
            'admin.shipping.rates.index',
        )->with(
            'success',
            'Shipping rate deleted successfully.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'shippingMethods' => ShippingMethod::query()
                ->select([
                    'id',
                    'name',
                    'code',
                    'driver',
                    'is_active',
                    'sort_order',
                ])
                ->orderBy(
                    'sort_order',
                )
                ->orderBy(
                    'name',
                )
                ->get(),

            'zones' => ShippingZone::query()
                ->select([
                    'id',
                    'name',
                    'code',
                    'is_active',
                    'is_catch_all',
                    'priority',
                ])
                ->orderBy(
                    'priority',
                )
                ->orderBy(
                    'name',
                )
                ->get(),
        ];
    }
}
