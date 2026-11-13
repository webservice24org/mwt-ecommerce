<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Reviews\Actions\CreateProductReviewAction;
use App\Domain\Reviews\Actions\UpdateProductReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreProductReviewRequest;
use App\Http\Requests\Frontend\UpdateProductReviewRequest;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class ProductReviewController extends Controller
{
    public function store(
        StoreProductReviewRequest $request,
        Product $product,
        CreateProductReviewAction $action,
    ): RedirectResponse {
        $user =
            $request->user();

        abort_unless(
            $user instanceof User,
            401,
        );

        $validated =
            $request->validated();

        $action->execute(
            user: $user,

            product: $product,

            data: [
                'rating' => (int) $validated[
                        'rating'
                    ],

                'title' => isset(
                    $validated['title'],
                )
                    ? (string) $validated[
                        'title'
                    ]
                    : null,

                'body' => (string) $validated[
                        'body'
                    ],
            ],
        );

        return back()->with(
            'success',
            'Your review was submitted and is awaiting moderation.',
        );
    }

    public function update(
        UpdateProductReviewRequest $request,
        Product $product,
        ProductReview $review,
        UpdateProductReviewAction $action,
    ): RedirectResponse {
        $user =
            $request->user();

        abort_unless(
            $user instanceof User,
            401,
        );

        $validated =
            $request->validated();

        $action->execute(
            user: $user,

            product: $product,

            review: $review,

            data: [
                'rating' => (int) $validated[
                        'rating'
                    ],

                'title' => isset(
                    $validated['title'],
                )
                    ? (string) $validated[
                        'title'
                    ]
                    : null,

                'body' => (string) $validated[
                        'body'
                    ],
            ],
        );

        return back()->with(
            'success',
            'Your review was updated and returned to moderation.',
        );
    }
}
