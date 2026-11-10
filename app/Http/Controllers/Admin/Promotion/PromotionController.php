<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Promotion;

use App\Domain\Promotion\Enums\DiscountType;
use App\Domain\Promotion\Services\PromotionDeletionGuard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Promotion\IndexPromotionRequest;
use App\Http\Requests\Admin\Promotion\StorePromotionRequest;
use App\Http\Requests\Admin\Promotion\UpdatePromotionRequest;
use App\Models\Promotion;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;

final class PromotionController extends Controller
{
    public function index(
        IndexPromotionRequest $request,
    ): Response {
        Gate::authorize(
            'viewAny',
            Promotion::class,
        );

        $filters =
            $request->validated();

        $query =
            Promotion::query()
                ->withCount(
                    'promoCodes',
                );

        $search = trim(
            (string) (
                $filters['search'] ??
                ''
            ),
        );

        if ($search !== '') {
            $query->where(
                function ($builder) use ($search): void {
                    $builder
                        ->where(
                            'name',
                            'like',
                            "%{$search}%",
                        )
                        ->orWhere(
                            'slug',
                            'like',
                            "%{$search}%",
                        );
                },
            );
        }

        $status =
            $filters['status'] ??
            null;

        $now = now();

        match ($status) {
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

            default => null,
        };

        if (
            isset(
                $filters[
                    'discount_type'
                ],
            )
        ) {
            $query->where(
                'discount_type',
                $filters[
                    'discount_type'
                ],
            );
        }

        match (
            $filters[
                'stackable'
            ] ??
            null
        ) {
            'yes' => $query->where(
                'is_stackable',
                true,
            ),

            'no' => $query->where(
                'is_stackable',
                false,
            ),

            default => null,
        };

        $perPage =
            (int) (
                $filters[
                    'per_page'
                ] ??
                20
            );

        $paginator =
            $query
                ->latest('id')
                ->paginate(
                    $perPage,
                )
                ->withQueryString();

        $rows = array_map(
            function (Promotion $promotion): array {
                $promoCodesCount =
                    (int) $promotion
                        ->getAttribute(
                            'promo_codes_count',
                        );

                return [
                    'id' => $promotion->id,

                    'name' => $promotion->name,

                    'slug' => $promotion->slug,

                    'discount_type' => $this
                        ->discountType(
                            $promotion
                                ->getAttribute(
                                    'discount_type',
                                ),
                        )
                        ->value,

                    'discount_type_label' => $this
                        ->discountType(
                            $promotion
                                ->getAttribute(
                                    'discount_type',
                                ),
                        )
                        ->label(),

                    'discount_value' => $promotion
                        ->discount_value,

                    'maximum_discount' => $promotion
                        ->maximum_discount,

                    'minimum_subtotal' => $promotion
                        ->minimum_subtotal,

                    'starts_at' => $this->isoDate(
                        $promotion
                            ->getAttribute(
                                'starts_at',
                            ),
                    ),

                    'ends_at' => $this->isoDate(
                        $promotion
                            ->getAttribute(
                                'ends_at',
                            ),
                    ),

                    'is_active' => $promotion
                        ->is_active,

                    'is_stackable' => $promotion
                        ->is_stackable,

                    'promo_codes_count' => $promoCodesCount,

                    'can_delete' => $promoCodesCount ===
                        0,

                    'delete_block_reason' => $promoCodesCount ===
                        0
                        ? null
                        : sprintf(
                            'This promotion has %d linked promo code%s. Detach %s before deleting the promotion.',
                            $promoCodesCount,
                            $promoCodesCount ===
                            1
                            ? ''
                            : 's',
                            $promoCodesCount ===
                            1
                            ? 'it'
                            : 'them',
                        ),
                ];
            },
            $paginator->items(),
        );

        $promotions =
            $this->mappedPaginator(
                paginator: $paginator,

                items: $rows,
            );

        return Inertia::render(
            'Admin/Promotions/Index',
            [
                'promotions' => $promotions,

                'filters' => [
                    'search' => $filters[
                            'search'
                        ] ??
                        '',

                    'status' => $filters[
                            'status'
                        ] ??
                        '',

                    'discount_type' => $filters[
                            'discount_type'
                        ] ??
                        '',

                    'stackable' => $filters[
                            'stackable'
                        ] ??
                        '',

                    'per_page' => $perPage,
                ],

                'discountTypes' => $this
                    ->discountTypes(),
            ],
        );
    }

    public function create(): Response
    {
        Gate::authorize(
            'create',
            Promotion::class,
        );

        return Inertia::render(
            'Admin/Promotions/Create',
            [
                'discountTypes' => $this
                    ->discountTypes(),
            ],
        );
    }

    public function store(
        StorePromotionRequest $request,
    ): RedirectResponse {
        Gate::authorize(
            'create',
            Promotion::class,
        );

        $data =
            $request->validated();

        $data['slug'] =
            filled(
                $data['slug'] ??
                null,
            )
            ? Str::slug(
                (string) $data[
                    'slug'
                ],
            )
            : Str::slug(
                (string) $data[
                    'name'
                ],
            );

        Promotion::query()
            ->create(
                $data,
            );

        return to_route(
            'admin.promotions.index',
        )->with(
            'success',
            'Promotion created successfully.',
        );
    }

    public function edit(
        Promotion $promotion,
    ): Response {
        Gate::authorize(
            'update',
            $promotion,
        );

        return Inertia::render(
            'Admin/Promotions/Edit',
            [
                'promotion' => [
                    'id' => $promotion->id,

                    'name' => $promotion->name,

                    'slug' => $promotion->slug,

                    'description' => $promotion
                        ->description,

                    'discount_type' => $this
                        ->discountType(
                            $promotion
                                ->getAttribute(
                                    'discount_type',
                                ),
                        )
                        ->value,

                    'discount_value' => $promotion
                        ->discount_value,

                    'maximum_discount' => $promotion
                        ->maximum_discount,

                    'minimum_subtotal' => $promotion
                        ->minimum_subtotal,

                    'starts_at' => $this->isoDate(
                        $promotion
                            ->getAttribute(
                                'starts_at',
                            ),
                    ),

                    'ends_at' => $this->isoDate(
                        $promotion
                            ->getAttribute(
                                'ends_at',
                            ),
                    ),

                    'is_active' => $promotion
                        ->is_active,

                    'is_stackable' => $promotion
                        ->is_stackable,
                ],

                'discountTypes' => $this
                    ->discountTypes(),
            ],
        );
    }

    public function update(
        UpdatePromotionRequest $request,
        Promotion $promotion,
    ): RedirectResponse {
        Gate::authorize(
            'update',
            $promotion,
        );

        $data =
            $request->validated();

        $data['slug'] =
            filled(
                $data['slug'] ??
                null,
            )
            ? Str::slug(
                (string) $data[
                    'slug'
                ],
            )
            : Str::slug(
                (string) $data[
                    'name'
                ],
            );

        $promotion->update(
            $data,
        );

        return to_route(
            'admin.promotions.index',
        )->with(
            'success',
            'Promotion updated successfully.',
        );
    }

    public function destroy(
        Promotion $promotion,
        PromotionDeletionGuard $deletionGuard,
    ): RedirectResponse {
        Gate::authorize(
            'delete',
            $promotion,
        );

        $linkedPromoCodeCount =
            $deletionGuard
                ->linkedPromoCodeCount(
                    $promotion,
                );

        if (
            $linkedPromoCodeCount >
            0
        ) {
            return back()->with(
                'error',
                $deletionGuard
                    ->blockReason(
                        $linkedPromoCodeCount,
                    ),
            );
        }

        $promotion->delete();

        return to_route(
            'admin.promotions.index',
        )->with(
            'success',
            'Promotion deleted successfully.',
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

    private function discountType(
        mixed $value,
    ): DiscountType {
        if (
            $value instanceof DiscountType
        ) {
            return $value;
        }

        if (
            is_string(
                $value,
            )
        ) {
            return DiscountType::from(
                $value,
            );
        }

        throw new LogicException(
            'Promotion discount type is invalid.',
        );
    }

    private function isoDate(
        mixed $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (
            $value instanceof CarbonInterface
        ) {
            return $value
                ->toISOString();
        }

        if (
            is_string(
                $value,
            )
        ) {
            return CarbonImmutable::parse(
                $value,
            )->toISOString();
        }

        throw new LogicException(
            'Promotion date value is invalid.',
        );
    }

    /**
     * @param  LengthAwarePaginator<int, Promotion>  $paginator
     * @param  array<int, array<string, mixed>>  $items
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function mappedPaginator(
        LengthAwarePaginator $paginator,
        array $items,
    ): LengthAwarePaginator {
        $mapped =
            new LengthAwarePaginator(
                $items,
                $paginator->total(),
                $paginator
                    ->perPage(),
                $paginator
                    ->currentPage(),
                [
                    'path' => $paginator
                        ->path(),

                    'pageName' => $paginator
                        ->getPageName(),
                ],
            );

        return $mapped
            ->withQueryString();
    }
}
