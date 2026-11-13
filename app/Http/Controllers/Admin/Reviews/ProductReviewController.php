<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reviews;

use App\Domain\Reviews\Actions\ModerateProductReviewAction;
use App\Domain\Reviews\Enums\ProductReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reviews\IndexProductReviewRequest;
use App\Http\Requests\Admin\Reviews\ModerateProductReviewRequest;
use App\Models\Admin;
use App\Models\ProductReview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class ProductReviewController extends Controller
{
    public function index(
        IndexProductReviewRequest $request,
    ): Response {
        Gate::authorize(
            'viewAny',
            ProductReview::class,
        );

        $filters =
            $request->validated();

        $query =
            ProductReview::query()
                ->with([
                    'product:id,name,sku,slug',
                    'user:id,name,email',
                    'moderator:id,name',
                ]);

        $search =
            trim(
                (string) (
                    $filters['search'] ??
                    ''
                ),
            );

        if (
            $search !== ''
        ) {
            $query->where(
                function (Builder $query) use ($search): void {
                    $query
                        ->where(
                            'title',
                            'like',
                            "%{$search}%",
                        )
                        ->orWhere(
                            'body',
                            'like',
                            "%{$search}%",
                        )
                        ->orWhereHas(
                            'product',
                            function (Builder $productQuery) use ($search): void {
                                $productQuery
                                    ->where(
                                        'name',
                                        'like',
                                        "%{$search}%",
                                    )
                                    ->orWhere(
                                        'sku',
                                        'like',
                                        "%{$search}%",
                                    );
                            },
                        )
                        ->orWhereHas(
                            'user',
                            function (Builder $userQuery) use ($search): void {
                                $userQuery
                                    ->where(
                                        'name',
                                        'like',
                                        "%{$search}%",
                                    )
                                    ->orWhere(
                                        'email',
                                        'like',
                                        "%{$search}%",
                                    );
                            },
                        );
                },
            );
        }

        if (
            isset(
                $filters['status'],
            )
        ) {
            $query->where(
                'status',
                $filters['status'],
            );
        }

        if (
            isset(
                $filters['rating'],
            )
        ) {
            $query->where(
                'rating',
                (int) $filters[
                    'rating'
                ],
            );
        }

        match (
            $filters['verified'] ??
            null
        ) {
            'yes' => $query->where(
                'is_verified_purchase',
                true,
            ),

            'no' => $query->where(
                'is_verified_purchase',
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

        $reviews =
            $paginator->through(
                static function (ProductReview $review): array {
                    return [
                        'id' => $review->id,

                        'rating' => $review->rating,

                        'title' => $review->title,

                        'body' => $review->body,

                        'status' => $review
                            ->status
                            ->value,

                        'status_label' => $review
                            ->status
                            ->label(),

                        'is_verified_purchase' => $review
                            ->is_verified_purchase,

                        'created_at' => $review
                            ->created_at
                            ?->toISOString(),

                        'moderated_at' => $review
                            ->moderated_at
                            ?->toISOString(),

                        'moderation_note' => $review
                            ->moderation_note,

                        'product' => [
                            'id' => $review
                                ->product
                                ->id,

                            'name' => $review
                                ->product
                                ->name,

                            'sku' => $review
                                ->product
                                ->sku,

                            'slug' => $review
                                ->product
                                ->slug,
                        ],

                        'customer' => [
                            'id' => $review
                                ->user
                                ->id,

                            'name' => $review
                                ->user
                                ->name,

                            'email' => $review
                                ->user
                                ->email,
                        ],

                        'moderator' => $review
                            ->moderator !==
                            null
                            ? [
                                'id' => $review
                                    ->moderator
                                    ->id,

                                'name' => $review
                                    ->moderator
                                    ->name,
                            ]
                            : null,
                    ];
                },
            );

        return Inertia::render(
            'Admin/Reviews/Index',
            [
                'reviews' => $reviews,

                'filters' => [
                    'search' => $search,

                    'status' => $filters[
                            'status'
                        ] ??
                        '',

                    'rating' => isset(
                        $filters[
                            'rating'
                        ],
                    )
                        ? (string) $filters[
                            'rating'
                        ]
                        : '',

                    'verified' => $filters[
                            'verified'
                        ] ??
                        '',

                    'per_page' => $perPage,
                ],

                'statusOptions' => array_map(
                    static fn (
                        ProductReviewStatus $status,
                    ): array => [
                        'value' => $status
                            ->value,

                        'label' => $status
                            ->label(),
                    ],
                    ProductReviewStatus::cases(),
                ),
            ],
        );
    }

    public function approve(
        ModerateProductReviewRequest $request,
        ProductReview $review,
        ModerateProductReviewAction $action,
    ): RedirectResponse {
        Gate::authorize(
            'update',
            $review,
        );

        $admin =
            $request->user(
                'admin',
            );

        abort_unless(
            $admin instanceof Admin,
            401,
        );

        $validated =
            $request->validated();

        $action->approve(
            review: $review,

            admin: $admin,

            note: isset(
                $validated['note'],
            )
            ? (string) $validated[
                'note'
            ]
            : null,
        );

        return back()->with(
            'success',
            'Review approved successfully.',
        );
    }

    public function reject(
        ModerateProductReviewRequest $request,
        ProductReview $review,
        ModerateProductReviewAction $action,
    ): RedirectResponse {
        Gate::authorize(
            'update',
            $review,
        );

        $admin =
            $request->user(
                'admin',
            );

        abort_unless(
            $admin instanceof Admin,
            401,
        );

        $validated =
            $request->validated();

        $action->reject(
            review: $review,

            admin: $admin,

            note: isset(
                $validated['note'],
            )
            ? (string) $validated[
                'note'
            ]
            : null,
        );

        return back()->with(
            'success',
            'Review rejected successfully.',
        );
    }
}
