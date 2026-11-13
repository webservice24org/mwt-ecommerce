import {
    Link,
    useForm,
} from '@inertiajs/react'
import type {
    FormEvent,
} from 'react'

import type {
    ProductReviewFormData,
    StorefrontProductReviewState,
} from '@/types/product-review'

interface ProductReviewFormErrors {
    rating?: string
    title?: string
    body?: string
    review?: string
}

interface Props {
    productId: number
    state: StorefrontProductReviewState
}

export default function ProductReviewForm({
    productId,
    state,
}: Props) {
    const existing =
        state.review

    const form =
        useForm<ProductReviewFormData>({
            rating:
                existing?.rating ??
                5,

            title:
                existing?.title ??
                '',

            body:
                existing?.body ??
                '',
        })

        const formErrors =
    form.errors as ProductReviewFormErrors

    const submit = (
        event: FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        if (existing) {
            form.patch(
                route(
                    'products.reviews.update',
                    {
                        product:
                            productId,

                        review:
                            existing.id,
                    },
                ),
                {
                    preserveScroll:
                        true,
                },
            )

            return
        }

        form.post(
            route(
                'products.reviews.store',
                {
                    product:
                        productId,
                },
            ),
            {
                preserveScroll:
                    true,

                onSuccess: () => {
                    form.reset()
                },
            },
        )
    }

    if (
        ! state.authenticated
    ) {
        return (
            <section
                aria-labelledby="product-review-heading"
                className="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6"
            >
                <h2
                    id="product-review-heading"
                    className="text-xl font-bold text-neutral-950"
                >
                    Write a review
                </h2>

                <p className="mt-2 text-sm leading-6 text-neutral-600">
                    Sign in to review a
                    product you have
                    purchased and received.
                </p>

                <Link
                    href={route(
                        'login',
                    )}
                    className="mt-4 inline-flex min-h-11 items-center justify-center rounded-xl bg-neutral-950 px-5 text-sm font-semibold text-white transition hover:bg-neutral-800"
                >
                    Sign in
                </Link>
            </section>
        )
    }

    if (
        ! existing &&
        ! state.eligibility
            ?.eligible
    ) {
        return (
            <section
                aria-labelledby="product-review-heading"
                className="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6"
            >
                <h2
                    id="product-review-heading"
                    className="text-xl font-bold text-neutral-950"
                >
                    Write a review
                </h2>

                <div className="mt-4 rounded-xl bg-amber-50 p-4 text-sm leading-6 text-amber-800">
                    {state
                        .eligibility
                        ?.reason ??
                        'You are not currently eligible to review this product.'}
                </div>
            </section>
        )
    }

    return (
        <section
            aria-labelledby="product-review-heading"
            className="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6"
        >
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2
                        id="product-review-heading"
                        className="text-xl font-bold text-neutral-950"
                    >
                        {existing
                            ? 'Edit your review'
                            : 'Write a review'}
                    </h2>

                    <p className="mt-1 text-sm leading-6 text-neutral-600">
                        Your review is linked
                        to a verified purchase.
                    </p>
                </div>

                {existing && (
                    <ReviewStatusBadge
                        status={
                            existing.status
                        }
                        label={
                            existing.status_label
                        }
                    />
                )}
            </div>

            {existing &&
                existing.status !==
                    'pending' && (
                    <div className="mt-4 rounded-xl bg-amber-50 p-4 text-sm leading-6 text-amber-800">
                        Editing this review
                        will return it to
                        pending moderation.
                    </div>
                )}

            <form
                onSubmit={
                    submit
                }
                className="mt-6 space-y-5"
            >
                <fieldset>
                    <legend className="text-sm font-semibold text-neutral-900">
                        Rating
                    </legend>

                    <div className="mt-2 flex flex-wrap gap-2">
                        {[
                            1,
                            2,
                            3,
                            4,
                            5,
                        ].map(
                            (
                                rating,
                            ) => (
                                <button
                                    key={
                                        rating
                                    }
                                    type="button"
                                    onClick={() =>
                                        form.setData(
                                            'rating',
                                            rating,
                                        )
                                    }
                                    aria-label={`${rating} star${rating === 1 ? '' : 's'}`}
                                    aria-pressed={
                                        form
                                            .data
                                            .rating ===
                                        rating
                                    }
                                    className={[
                                        'flex size-11 items-center justify-center rounded-xl border text-xl transition',
                                        form
                                            .data
                                            .rating >=
                                        rating
                                            ? 'border-amber-300 bg-amber-50 text-amber-500'
                                            : 'border-neutral-200 bg-white text-neutral-300 hover:border-neutral-400',
                                    ].join(
                                        ' ',
                                    )}
                                >
                                    <span
                                        aria-hidden="true"
                                    >
                                        ★
                                    </span>
                                </button>
                            ),
                        )}
                    </div>

                    {form.errors
                        .rating && (
                        <p
                            role="alert"
                            className="mt-2 text-sm text-red-600"
                        >
                            {
                                form
                                    .errors
                                    .rating
                            }
                        </p>
                    )}
                </fieldset>

                <div>
                    <label
                        htmlFor="review-title"
                        className="text-sm font-semibold text-neutral-900"
                    >
                        Title{' '}
                        <span className="font-normal text-neutral-500">
                            (optional)
                        </span>
                    </label>

                    <input
                        id="review-title"
                        type="text"
                        value={
                            form.data
                                .title
                        }
                        onChange={(
                            event,
                        ) =>
                            form.setData(
                                'title',
                                event
                                    .target
                                    .value,
                            )
                        }
                        maxLength={
                            160
                        }
                        disabled={
                            form.processing
                        }
                        className="mt-2 block min-h-11 w-full rounded-xl border border-neutral-300 px-4 text-sm text-neutral-950 outline-none transition focus:border-neutral-950 focus:ring-1 focus:ring-neutral-950 disabled:bg-neutral-100"
                    />

                    {form.errors
                        .title && (
                        <p
                            role="alert"
                            className="mt-2 text-sm text-red-600"
                        >
                            {
                                form
                                    .errors
                                    .title
                            }
                        </p>
                    )}
                </div>

                <div>
                    <label
                        htmlFor="review-body"
                        className="text-sm font-semibold text-neutral-900"
                    >
                        Review
                    </label>

                    <textarea
                        id="review-body"
                        value={
                            form.data.body
                        }
                        onChange={(
                            event,
                        ) =>
                            form.setData(
                                'body',
                                event
                                    .target
                                    .value,
                            )
                        }
                        rows={6}
                        minLength={
                            10
                        }
                        maxLength={
                            5000
                        }
                        required
                        disabled={
                            form.processing
                        }
                        className="mt-2 block w-full resize-y rounded-xl border border-neutral-300 px-4 py-3 text-sm leading-6 text-neutral-950 outline-none transition focus:border-neutral-950 focus:ring-1 focus:ring-neutral-950 disabled:bg-neutral-100"
                    />

                    <div className="mt-2 flex items-start justify-between gap-4">
                        <div>
                            {form.errors
                                .body && (
                                <p
                                    role="alert"
                                    className="text-sm text-red-600"
                                >
                                    {
                                        form
                                            .errors
                                            .body
                                    }
                                </p>
                            )}

                            {formErrors.body && (
                                    <p
                                        role="alert"
                                        className="text-sm text-red-600"
                                    >
                                        {formErrors.body}
                                    </p>
                                )}

                                {formErrors.review && (
                                    <p
                                        role="alert"
                                        className="text-sm text-red-600"
                                    >
                                        {formErrors.review}
                                    </p>
                                )}
                        </div>

                        <span className="shrink-0 text-xs text-neutral-400">
                            {
                                form.data
                                    .body
                                    .length
                            }
                            /5000
                        </span>
                    </div>
                </div>

                <button
                    type="submit"
                    disabled={
                        form.processing
                    }
                    className="inline-flex min-h-11 items-center justify-center rounded-xl bg-neutral-950 px-5 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-wait disabled:bg-neutral-300 disabled:text-neutral-600"
                >
                    {form.processing
                        ? existing
                            ? 'Saving...'
                            : 'Submitting...'
                        : existing
                          ? 'Save review'
                          : 'Submit review'}
                </button>
            </form>
        </section>
    )
}

function ReviewStatusBadge({
    status,
    label,
}: {
    status:
        | 'pending'
        | 'approved'
        | 'rejected'
    label: string
}) {
    const classes = {
        pending:
            'bg-amber-50 text-amber-700',

        approved:
            'bg-emerald-50 text-emerald-700',

        rejected:
            'bg-red-50 text-red-700',
    }

    return (
        <span
            className={`inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold ${classes[status]}`}
        >
            {label}
        </span>
    )
}