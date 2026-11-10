<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Shipping;

use App\Domain\Shipping\Enums\ShippingZoneRuleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Shipping\StoreShippingZoneRequest;
use App\Http\Requests\Admin\Shipping\UpdateShippingZoneRequest;
use App\Models\ShippingZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class ShippingZoneController extends Controller
{
    public function index(): Response
    {
        $this->authorize(
            'viewAny',
            ShippingZone::class,
        );

        $zones = ShippingZone::query()
            ->withCount([
                'rules',
                'rates',
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
                    ShippingZone $zone,
                ): array => [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'code' => $zone->code,

                    'is_active' => $zone->is_active,

                    'is_catch_all' => $zone->is_catch_all,

                    'priority' => $zone->priority,

                    'rules_count' => (int) $zone
                        ->getAttribute(
                            'rules_count',
                        ),

                    'rates_count' => (int) $zone
                        ->getAttribute(
                            'rates_count',
                        ),
                ],
            )
            ->values();

        return Inertia::render(
            'Admin/Shipping/Zones/Index',
            [
                'zones' => $zones,
            ],
        );
    }

    public function create(): Response
    {
        $this->authorize(
            'create',
            ShippingZone::class,
        );

        return Inertia::render(
            'Admin/Shipping/Zones/Create',
            [
                'ruleTypes' => $this->ruleTypes(),
            ],
        );
    }

    public function store(
        StoreShippingZoneRequest $request,
    ): RedirectResponse {
        $this->authorize(
            'create',
            ShippingZone::class,
        );

        $validated = $request->validated();

        $zone = DB::transaction(
            function () use ($validated, $request): ShippingZone {
                $zone = ShippingZone::query()
                    ->create([
                        'name' => $validated['name'],

                        'code' => $validated['code'],

                        'is_active' => $validated['is_active'],

                        'is_catch_all' => $validated['is_catch_all'],

                        'priority' => $validated['priority'],

                        'metadata' => null,
                    ]);

                foreach (
                    $request->zoneRules() as $rule
                ) {
                    $zone
                        ->rules()
                        ->create([
                            'type' => $rule['type'],

                            'value' => $rule['value'],
                        ]);
                }

                return $zone;
            },
        );

        return to_route(
            'admin.shipping.zones.edit',
            $zone,
        )->with(
            'success',
            'Shipping zone created successfully.',
        );
    }

    public function edit(
        ShippingZone $zone,
    ): Response {
        $this->authorize(
            'update',
            $zone,
        );

        $zone->load([
            'rules' => static fn (
                $query,
            ) => $query
                ->orderBy('type')
                ->orderBy('value'),
        ]);

        return Inertia::render(
            'Admin/Shipping/Zones/Edit',
            [
                'zone' => [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'code' => $zone->code,

                    'is_active' => $zone->is_active,

                    'is_catch_all' => $zone->is_catch_all,

                    'priority' => $zone->priority,

                    'rules' => $zone
                        ->rules
                        ->map(
                            static fn (
                                $rule,
                            ): array => [
                                'id' => $rule->id,

                                'type' => $rule
                                    ->ruleType()
                                    ->value,

                                'value' => $rule->value,
                            ],
                        )
                        ->values()
                        ->all(),
                ],

                'ruleTypes' => $this->ruleTypes(),
            ],
        );
    }

    public function update(
        UpdateShippingZoneRequest $request,
        ShippingZone $zone,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $zone,
        );

        $validated = $request->validated();

        DB::transaction(
            function () use ($zone, $validated, $request): void {
                $zone->update([
                    'name' => $validated['name'],

                    'code' => $validated['code'],

                    'is_active' => $validated['is_active'],

                    'is_catch_all' => $validated['is_catch_all'],

                    'priority' => $validated['priority'],
                ]);

                $zone
                    ->rules()
                    ->delete();

                foreach (
                    $request->zoneRules() as $rule
                ) {
                    $zone
                        ->rules()
                        ->create([
                            'type' => $rule['type'],

                            'value' => $rule['value'],
                        ]);
                }
            },
        );

        return to_route(
            'admin.shipping.zones.edit',
            $zone,
        )->with(
            'success',
            'Shipping zone updated successfully.',
        );
    }

    public function destroy(
        ShippingZone $zone,
    ): RedirectResponse {
        $this->authorize(
            'delete',
            $zone,
        );

        /*
         * Important:
         *
         * Removing the final zone from a rate would turn
         * that rate into a global rate. Never allow a zone
         * with attached rates to be deleted accidentally.
         */
        if (
            $zone
                ->rates()
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'zone' => 'Remove this zone from all shipping rates before deleting it.',
            ]);
        }

        $zone->delete();

        return to_route(
            'admin.shipping.zones.index',
        )->with(
            'success',
            'Shipping zone deleted successfully.',
        );
    }

    /**
     * @return list<array{
     *     value: string,
     *     label: string
     * }>
     */
    private function ruleTypes(): array
    {
        return array_map(
            static fn (
                ShippingZoneRuleType $type,
            ): array => [
                'value' => $type->value,

                'label' => str($type->value)
                    ->replace('_', ' ')
                    ->title()
                    ->toString(),
            ],
            ShippingZoneRuleType::cases(),
        );
    }
}
