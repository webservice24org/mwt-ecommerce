import { useEffect, useRef, useState } from 'react'

import type {
    PublicProductReview,
    ProductReviewPaginator,
    StorefrontProductReviews,
} from '@/types/product-review'

interface Props {
    reviews: StorefrontProductReviews
    productSlug?: string
}

export default function ProductReviewsSection({
    reviews,
    productSlug,
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

            <ReviewFeed
                key={`${productSlug ?? ''}:${paginator.current_page}:${paginator.total}:${paginator.data.map((review) => review.id).join(',')}`}
                productSlug={productSlug}
                initial={paginator}
            />
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

function ReviewFeed({
    productSlug,
    initial,
}: {
    productSlug?: string
    initial: ProductReviewPaginator
}) {
    const [additional, setAdditional] = useState<PublicProductReview[]>([])
    const [page, setPage] = useState(initial.current_page)
    const [lastPage, setLastPage] = useState(initial.last_page)
    const [loading, setLoading] = useState(false)
    const [error, setError] = useState<string | null>(null)
    const request = useRef<AbortController | null>(null)

    useEffect(() => () => request.current?.abort(), [])

    const visible = Array.from(
        new Map([...initial.data, ...additional].map((review) => [review.id, review])).values(),
    )
    const hasMore = Boolean(productSlug) && page < lastPage && page < 10000

    async function loadMore() {
        if (!productSlug || request.current || !hasMore) return

        const controller = new AbortController()
        request.current = controller
        setLoading(true)
        setError(null)

        try {
            const nextPage = page + 1
            const response = await fetch(
                `/products/${encodeURIComponent(productSlug)}/reviews?page=${nextPage}`,
                {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    signal: controller.signal,
                },
            )
            if (!response.ok) throw new Error('Unable to load reviews.')

            const result: ProductReviewPaginator = await response.json()
            if (result.current_page !== nextPage || !Array.isArray(result.data)) {
                throw new Error('Unexpected review response.')
            }
            if (controller.signal.aborted) return

            setAdditional((previous) => [...previous, ...result.data])
            setPage(result.current_page)
            setLastPage(result.last_page)
        } catch {
            if (!controller.signal.aborted) {
                setError('Could not load more reviews. Please try again.')
            }
        } finally {
            if (request.current === controller) request.current = null
            if (!controller.signal.aborted) setLoading(false)
        }
    }

    return (
        <>
            <div id="customer-review-list" className="divide-y divide-neutral-100" aria-busy={loading}>
                {visible.map((review) => <ReviewCard key={review.id} review={review} />)}
            </div>
            {productSlug && initial.current_page > 1 && (
                <a href={`/products/${encodeURIComponent(productSlug)}#customer-reviews-heading`} className="mt-4 inline-block text-sm font-medium underline">
                    Back to newest reviews
                </a>
            )}
            {visible.length > 0 && (
                <div className="mt-6 flex flex-col items-center gap-3 border-t border-neutral-200 pt-5">
                    <p role="status" className="text-sm text-neutral-500">
                        {visible.length} {visible.length === 1 ? 'review' : 'reviews'} displayed
                    </p>
                    {hasMore && (
                        <button
                            type="button"
                            onClick={loadMore}
                            disabled={loading}
                            aria-controls="customer-review-list"
                            className="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full border border-neutral-300 bg-white px-8 py-2.5 text-sm font-semibold text-neutral-950 transition hover:bg-neutral-50 focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-wait disabled:opacity-60 sm:w-auto"
                        >
                            {loading ? 'Loading reviews…' : 'View more reviews'}
                            {!loading && <span aria-hidden="true">⌄</span>}
                        </button>
                    )}
                    {error && <p role="alert" className="text-sm text-red-600">{error}</p>}
                </div>
            )}
        </>
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