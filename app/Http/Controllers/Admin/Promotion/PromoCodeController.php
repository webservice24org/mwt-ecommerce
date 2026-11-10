<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Promotion;

use App\Domain\Promotion\Enums\DiscountType;
use App\Domain\Promotion\Services\AdminPromoRulePresenter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Promotion\IndexPromoCodeRequest;
use App\Http\Requests\Admin\Promotion\StorePromoCodeRequest;
use App\Http\Requests\Admin\Promotion\UpdatePromoCodeRequest;
use App\Models\PromoCode;
use App\Models\Promotion;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;

final class PromoCodeController extends Controller
{
    public function index(
        IndexPromoCodeRequest $request,
        AdminPromoRulePresenter $presenter,
    ): Response {
        Gate::authorize(
            'viewAny',
            PromoCode::class,
        );

        $filters = $request->validated();

        $query = PromoCode::query()
            ->with([
                'promotion:id,name,discount_type,discount_value,maximum_discount,minimum_subtotal,starts_at,ends_at,is_active,is_stackable',
            ])
            ->withCount('usages');

        $search = trim(
            (string) ($filters['search'] ?? ''),
        );

        if ($search !== '') {
            $query->where(
                function ($builder) use ($search): void {
                    $builder
                        ->where(
                            'code',
                            'like',
                            "%{$search}%",
                        )
                        ->orWhere(
                            'name',
                            'like',
                            "%{$search}%",
                        );
                },
            );
        }

        $promotionFilter =
            $filters['promotion'] ?? null;

        if (
            $promotionFilter ===
            'standalone'
        ) {
            $query->whereNull(
                'promotion_id',
            );
        } elseif (
            is_string(
                $promotionFilter,
            ) &&
            ctype_digit(
                $promotionFilter,
            )
        ) {
            $query->where(
                'promotion_id',
                (int) $promotionFilter,
            );
        }

        if (
            isset(
                $filters['discount_type'],
            )
        ) {
            $query->where(
                'discount_type',
                $filters['discount_type'],
            );
        }

        match (
            $filters['source'] ??
            null
        ) {
            'standalone' => $query->whereNull(
                'promotion_id',
            ),

            'inherited' => $query
                ->whereNotNull(
                    'promotion_id',
                )
                ->whereNull(
                    'discount_type',
                )
                ->whereNull(
                    'discount_value',
                ),

            'override' => $query
                ->whereNotNull(
                    'promotion_id',
                )
                ->whereNotNull(
                    'discount_type',
                )
                ->whereNotNull(
                    'discount_value',
                ),

            default => null,
        };

        match (
            $filters['usage'] ??
            null
        ) {
            'unused' => $query->where(
                'redeemed_count',
                0,
            ),

            'used' => $query->where(
                'redeemed_count',
                '>',
                0,
            ),

            'exhausted' => $query
                ->whereNotNull(
                    'usage_limit',
                )
                ->whereColumn(
                    'redeemed_count',
                    '>=',
                    'usage_limit',
                ),

            default => null,
        };

        $status =
            $filters['status'] ?? null;

        $now = now();

        match ($status) {
            'inactive' => $query->where(
                'is_active',
                false,
            ),

            'scheduled' => $query
                ->where(
                    'is_active',
                    true,
                )
                ->where(
                    'starts_at',
                    '>',
                    $now,
                ),

            'expired' => $query
                ->whereNotNull(
                    'ends_at',
                )
                ->where(
                    'ends_at',
                    '<',
                    $now,
                ),

            'active' => $query
                ->where(
                    'is_active',
                    true,
                )
                ->where(
                    function ($builder) use ($now): void {
                        $builder
                            ->whereNull(
                                'starts_at',
                            )
                            ->orWhere(
                                'starts_at',
                                '<=',
                                $now,
                            );
                    },
                )
                ->where(
                    function ($builder) use ($now): void {
                        $builder
                            ->whereNull(
                                'ends_at',
                            )
                            ->orWhere(
                                'ends_at',
                                '>=',
                                $now,
                            );
                    },
                ),

            default => null,
        };

        $perPage =
            (int) (
                $filters['per_page'] ??
                20
            );

        $paginator = $query
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $rows = array_map(
            fn (PromoCode $promoCode): array => [
                'id' => $promoCode->id,

                'promotion' => $promoCode->promotion === null
                    ? null
                    : [
                        'id' => $promoCode
                            ->promotion
                            ->id,

                        'name' => $promoCode
                            ->promotion
                            ->name,
                    ],

                'code' => $promoCode->code,

                'name' => $promoCode->name,

                'discount_type' => $this
                    ->nullableDiscountType(
                        $promoCode->getAttribute(
                            'discount_type',
                        ),
                    )
                    ?->value,

                'discount_value' => $promoCode->discount_value,

                'maximum_discount' => $promoCode->maximum_discount,

                'minimum_subtotal' => $promoCode->minimum_subtotal,

                'usage_limit' => $promoCode->usage_limit,

                'usage_limit_per_customer' => $promoCode
                    ->usage_limit_per_customer,

                'redeemed_count' => $promoCode->redeemed_count,

                'usages_count' => (int) $promoCode->getAttribute(
                    'usages_count',
                ),

                'starts_at' => $this->isoDate(
                    $promoCode->getAttribute(
                        'starts_at',
                    ),
                ),

                'ends_at' => $this->isoDate(
                    $promoCode->getAttribute(
                        'ends_at',
                    ),
                ),

                'is_active' => $promoCode->is_active,

                'is_stackable' => $promoCode->is_stackable,

                'effective' => $presenter->present(
                    $promoCode,
                ),

                'can_delete' => (int) $promoCode->getAttribute(
                    'usages_count',
                ) === 0,
            ],
            $paginator->items(),
        );

        $promoCodes = $this->mappedPaginator(
            paginator: $paginator,
            items: $rows,
        );

        return Inertia::render(
            'Admin/PromoCodes/Index',
            [
                'promoCodes' => $promoCodes,
                'filters' => [
                    'search' => $filters['search'] ?? '',

                    'status' => $filters['status'] ?? '',

                    'promotion' => $filters['promotion'] ?? '',

                    'discount_type' => $filters['discount_type'] ?? '',

                    'source' => $filters['source'] ?? '',

                    'usage' => $filters['usage'] ?? '',

                    'per_page' => $perPage,
                ],
                'promotions' => $this->promotionOptions(),
                'discountTypes' => $this->discountTypes(),
            ],
        );
    }

    public function create(): Response
    {
        Gate::authorize(
            'create',
            PromoCode::class,
        );

        return Inertia::render(
            'Admin/PromoCodes/Create',
            [
                'discountTypes' => $this->discountTypes(),

                'promotions' => $this->promotionOptions(),
            ],
        );
    }

    public function store(
        StorePromoCodeRequest $request,
    ): RedirectResponse {
        Gate::authorize(
            'create',
            PromoCode::class,
        );

        PromoCode::query()->create(
            $request->validated(),
        );

        return to_route(
            'admin.promo-codes.index',
        )->with(
            'success',
            'Promo code created successfully.',
        );
    }

    public function edit(
        PromoCode $promoCode,
        AdminPromoRulePresenter $presenter,
    ): Response {
        Gate::authorize(
            'update',
            $promoCode,
        );

        $promoCode->loadMissing([
            'promotion:id,name,discount_type,discount_value,maximum_discount,minimum_subtotal,starts_at,ends_at,is_active,is_stackable',
        ]);

        return Inertia::render(
            'Admin/PromoCodes/Edit',
            [
                'promoCode' => [
                    'id' => $promoCode->id,

                    'promotion_id' => $promoCode->promotion_id,

                    'code' => $promoCode->code,

                    'name' => $promoCode->name,

                    'description' => $promoCode->description,

                    'discount_type' => $this
                        ->nullableDiscountType(
                            $promoCode->getAttribute(
                                'discount_type',
                            ),
                        )
                        ?->value,

                    'discount_value' => $promoCode->discount_value,

                    'maximum_discount' => $promoCode->maximum_discount,

                    'minimum_subtotal' => $promoCode->minimum_subtotal,

                    'usage_limit' => $promoCode->usage_limit,

                    'usage_limit_per_customer' => $promoCode
                        ->usage_limit_per_customer,

                    'redeemed_count' => $promoCode->redeemed_count,

                    'starts_at' => $this->isoDate(
                        $promoCode->getAttribute(
                            'starts_at',
                        ),
                    ),

                    'ends_at' => $this->isoDate(
                        $promoCode->getAttribute(
                            'ends_at',
                        ),
                    ),

                    'is_active' => $promoCode->is_active,

                    'is_stackable' => $promoCode->is_stackable,
                ],

                'discountTypes' => $this->discountTypes(),

                'promotions' => $this->promotionOptions(),
                'effective' => $presenter->present(
                    $promoCode,
                ),

                'canDelete' => ! $promoCode
                    ->usages()
                    ->exists(),
            ],
        );
    }

    public function update(
        UpdatePromoCodeRequest $request,
        PromoCode $promoCode,
    ): RedirectResponse {
        Gate::authorize(
            'update',
            $promoCode,
        );

        $promoCode->update(
            $request->validated(),
        );

        return to_route(
            'admin.promo-codes.index',
        )->with(
            'success',
            'Promo code updated successfully.',
        );
    }

    public function destroy(
        PromoCode $promoCode,
    ): RedirectResponse {
        Gate::authorize(
            'delete',
            $promoCode,
        );

        if (
            $promoCode
                ->usages()
                ->exists()
        ) {
            return back()->with(
                'error',
                'A promo code with redemption history cannot be deleted. Disable it instead.',
            );
        }

        $promoCode->delete();

        return to_route(
            'admin.promo-codes.index',
        )->with(
            'success',
            'Promo code deleted successfully.',
        );
    }

    /**
     * @return array<int, array{
     *     value: string,
     *     label: string
     * }>
     */
    private function discountTypes(): array
    {
        return array_map(
            static fn (
                DiscountType $type,
            ): array => [
                'value' => $type->value,

                'label' => $type->label(),
            ],
            DiscountType::cases(),
        );
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     name: string
     * }>
     */
    private function promotionOptions(): array
    {
        return Promotion::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ])
            ->map(
                static fn (
                    Promotion $promotion,
                ): array => [
                    'id' => $promotion->id,

                    'name' => $promotion->name,
                ],
            )
            ->all();
    }

    private function nullableDiscountType(
        mixed $value,
    ): ?DiscountType {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DiscountType) {
            return $value;
        }

        if (is_string($value)) {
            return DiscountType::tryFrom(
                $value,
            );
        }

        return null;
    }

    private function isoDate(
        mixed $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->toISOString();
        }

        if (is_string($value)) {
            return CarbonImmutable::parse(
                $value,
            )->toISOString();
        }

        throw new LogicException(
            'Promo code date value is invalid.',
        );
    }

    /**
     * @param  LengthAwarePaginator<int, PromoCode>  $paginator
     * @param  array<int, array<string, mixed>>  $items
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function mappedPaginator(
        LengthAwarePaginator $paginator,
        array $items,
    ): LengthAwarePaginator {
        $mapped = new LengthAwarePaginator(
            $items,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            [
                'path' => $paginator->path(),

                'pageName' => $paginator->getPageName(),
            ],
        );

        return $mapped->withQueryString();
    }
}
