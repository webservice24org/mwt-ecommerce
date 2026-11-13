import { Link } from '@inertiajs/react'

import type {
    PublicProductReview,
    StorefrontProductReviews,
} from '@/types/product-review'

interface Props {
    reviews: StorefrontProductReviews
}

export default function ProductReviewsSection({
    reviews,
}: Props) {
    const {
        summary,
        reviews: paginator,
    } = reviews

    return (
        <section
            aria-labelledby="customer-reviews-heading"
            className="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6"
        >
            <div className="border-b border-neutral-200 pb-6">
                <h2
                    id="customer-reviews-heading"
                    className="text-2xl font-bold tracking-tight text-neutral-950"
                >
                    Customer reviews
                </h2>

                {summary.review_count > 0 ? (
                    <div className="mt-6 grid gap-8 md:grid-cols-[220px_minmax(0,1fr)]">
                        <div>
                            <div className="flex items-end gap-2">
                                <span className="text-5xl font-bold tracking-tight text-neutral-950">
                                    {summary.average_rating.toFixed(
                                        1,
                                    )}
                                </span>

                                <span className="pb-1 text-sm text-neutral-500">
                                    / 5
                                </span>
                            </div>

                            <div className="mt-3">
                                <RatingStars
                                    rating={
                                        summary.average_rating
                                    }
                                />
                            </div>

                            <p className="mt-2 text-sm text-neutral-500">
                                Based on{' '}
                                {summary.review_count}{' '}
                                {summary.review_count === 1
                                    ? 'review'
                                    : 'reviews'}
                            </p>
                        </div>

                        <div className="space-y-2.5">
                            {summary.distribution.map(
                                (item) => (
                                    <div
                                        key={
                                            item.rating
                                        }
                                        className="grid grid-cols-[42px_minmax(0,1fr)_42px] items-center gap-3"
                                    >
                                        <span className="text-sm font-medium text-neutral-700">
                                            {
                                                item.rating
                                            }{' '}
                                            ★
                                        </span>

                                        <div
                                            className="h-2.5 overflow-hidden rounded-full bg-neutral-100"
                                            aria-hidden="true"
                                        >
                                            <div
                                                className="h-full rounded-full bg-amber-400"
                                                style={{
                                                    width: `${item.percentage}%`,
                                                }}
                                            />
                                        </div>

                                        <span className="text-right text-sm text-neutral-500">
                                            {
                                                item.count
                                            }
                                        </span>
                                    </div>
                                ),
                            )}
                        </div>
                    </div>
                ) : (
                    <div className="mt-5 rounded-xl bg-neutral-50 p-5 text-sm leading-6 text-neutral-600">
                        No approved customer
                        reviews yet.
                    </div>
                )}
            </div>

            {paginator.data.length >
                0 && (
                <>
                    <div className="divide-y divide-neutral-100">
                        {paginator.data.map(
                            (review) => (
                                <ReviewCard
                                    key={
                                        review.id
                                    }
                                    review={
                                        review
                                    }
                                />
                            ),
                        )}
                    </div>

                    <ReviewPagination
                        currentPage={
                            paginator.current_page
                        }
                        lastPage={
                            paginator.last_page
                        }
                    />
                </>
            )}
        </section>
    )
}

function ReviewCard({
    review,
}: {
    review: PublicProductReview
}) {
    return (
        <article className="py-6 first:pt-6 last:pb-0">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="font-semibold text-neutral-950">
                            {
                                review.author_name
                            }
                        </p>

                        {review.is_verified_purchase && (
                            <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                Verified purchase
                            </span>
                        )}
                    </div>

                    <div className="mt-2">
                        <RatingStars
                            rating={
                                review.rating
                            }
                        />
                    </div>
                </div>

                {review.created_at && (
                    <time
                        dateTime={
                            review.created_at
                        }
                        className="shrink-0 text-xs text-neutral-400"
                    >
                        {formatReviewDate(
                            review.created_at,
                        )}
                    </time>
                )}
            </div>

            {review.title && (
                <h3 className="mt-4 text-base font-bold text-neutral-950">
                    {review.title}
                </h3>
            )}

            <p className="mt-2 whitespace-pre-line break-words text-sm leading-7 text-neutral-700">
                {review.body}
            </p>
        </article>
    )
}

function RatingStars({
    rating,
}: {
    rating: number
}) {
    return (
        <div
            className="flex items-center gap-0.5"
            aria-label={`${rating.toFixed(
                1,
            )} out of 5 stars`}
        >
            {[1, 2, 3, 4, 5].map(
                (star) => (
                    <span
                        key={star}
                        aria-hidden="true"
                        className={
                            rating >=
                            star - 0.5
                                ? 'text-amber-400'
                                : 'text-neutral-200'
                        }
                    >
                        ★
                    </span>
                ),
            )}
        </div>
    )
}

function ReviewPagination({
    currentPage,
    lastPage,
}: {
    currentPage: number
    lastPage: number
}) {
    if (lastPage <= 1) {
        return null
    }

    return (
        <nav
            aria-label="Review pagination"
            className="mt-6 flex items-center justify-between gap-4 border-t border-neutral-200 pt-5"
        >
            {currentPage > 1 ? (
                <Link
                    href={`?reviews_page=${
                        currentPage - 1
                    }`}
                    preserveScroll
                    preserveState
                    className="inline-flex min-h-10 items-center rounded-lg border border-neutral-300 px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50"
                >
                    Previous
                </Link>
            ) : (
                <span />
            )}

            <span className="text-sm text-neutral-500">
                Page {currentPage} of{' '}
                {lastPage}
            </span>

            {currentPage <
            lastPage ? (
                <Link
                    href={`?reviews_page=${
                        currentPage + 1
                    }`}
                    preserveScroll
                    preserveState
                    className="inline-flex min-h-10 items-center rounded-lg border border-neutral-300 px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50"
                >
                    Next
                </Link>
            ) : (
                <span />
            )}
        </nav>
    )
}

function formatReviewDate(
    value: string,
): string {
    return new Intl.DateTimeFormat(
        undefined,
        {
            dateStyle:
                'medium',
        },
    ).format(
        new Date(
            value,
        ),
    )
}