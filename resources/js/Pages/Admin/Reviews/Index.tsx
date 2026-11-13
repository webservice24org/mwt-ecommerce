import {
    Head,
    router,
} from '@inertiajs/react'
import {
    FormEvent,
    useState,
    ReactNode,
} from 'react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'

type ReviewStatus =
    | 'pending'
    | 'approved'
    | 'rejected'

interface Review {
    id: number
    rating: number
    title: string | null
    body: string
    status: ReviewStatus
    status_label: string
    is_verified_purchase: boolean
    created_at: string | null
    moderated_at: string | null
    moderation_note: string | null

    product: {
        id: number
        name: string
        sku: string | null
        slug: string
    }

    customer: {
        id: number
        name: string
        email: string
    }

    moderator: {
        id: number
        name: string
    } | null
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface ReviewsPaginator {
    data: Review[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    links: PaginationLink[]
}

interface Filters {
    search: string
    status: string
    rating: string
    verified: string
    per_page: number
}

interface StatusOption {
    value: ReviewStatus
    label: string
}

interface Props {
    reviews: ReviewsPaginator
    filters: Filters
    statusOptions: StatusOption[]
}

export default function Index({
    reviews,
    filters,
    statusOptions,
}: Props) {
    const [search, setSearch] =
        useState(
            filters.search,
        )

    const [status, setStatus] =
        useState(
            filters.status,
        )

    const [rating, setRating] =
        useState(
            filters.rating,
        )

    const [verified, setVerified] =
        useState(
            filters.verified,
        )

    const [perPage, setPerPage] =
        useState(
            String(
                filters.per_page,
            ),
        )

    const applyFilters = (
        event?: FormEvent<HTMLFormElement>,
    ) => {
        event?.preventDefault()

        router.get(
            route(
                'admin.reviews.index',
            ),
            {
                search:
                    search ||
                    undefined,

                status:
                    status ||
                    undefined,

                rating:
                    rating ||
                    undefined,

                verified:
                    verified ||
                    undefined,

                per_page:
                    perPage,
            },
            {
                preserveState:
                    true,

                replace:
                    true,
            },
        )
    }

    const clearFilters = () => {
        setSearch('')
        setStatus('')
        setRating('')
        setVerified('')
        setPerPage('20')

        router.get(
            route(
                'admin.reviews.index',
            ),
            {},
            {
                replace:
                    true,
            },
        )
    }

    return (
        <AdminLayout
            title="Product reviews"
            description="Moderate customer product reviews"
        >
            <Head title="Product Reviews" />

            <div className="space-y-6 p-4 sm:p-6">
                <form
                    onSubmit={
                        applyFilters
                    }
                    className="rounded-xl border bg-white p-4"
                >
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <div className="xl:col-span-2">
                            <label
                                htmlFor="review-search"
                                className="mb-1 block text-sm font-medium text-slate-700"
                            >
                                Search
                            </label>

                            <input
                                id="review-search"
                                type="search"
                                value={
                                    search
                                }
                                onChange={(
                                    event,
                                ) =>
                                    setSearch(
                                        event
                                            .target
                                            .value,
                                    )
                                }
                                placeholder="Product, SKU, customer, email, review..."
                                className="w-full rounded-lg border-slate-300 text-sm"
                            />
                        </div>

                        <FilterSelect
                            label="Status"
                            value={
                                status
                            }
                            onChange={
                                setStatus
                            }
                        >
                            <option value="">
                                All statuses
                            </option>

                            {statusOptions.map(
                                (
                                    option,
                                ) => (
                                    <option
                                        key={
                                            option.value
                                        }
                                        value={
                                            option.value
                                        }
                                    >
                                        {
                                            option.label
                                        }
                                    </option>
                                ),
                            )}
                        </FilterSelect>

                        <FilterSelect
                            label="Rating"
                            value={
                                rating
                            }
                            onChange={
                                setRating
                            }
                        >
                            <option value="">
                                All ratings
                            </option>

                            {[5, 4, 3, 2, 1].map(
                                (
                                    value,
                                ) => (
                                    <option
                                        key={
                                            value
                                        }
                                        value={
                                            value
                                        }
                                    >
                                        {
                                            value
                                        }{' '}
                                        star
                                    </option>
                                ),
                            )}
                        </FilterSelect>

                        <FilterSelect
                            label="Verified"
                            value={
                                verified
                            }
                            onChange={
                                setVerified
                            }
                        >
                            <option value="">
                                All reviews
                            </option>

                            <option value="yes">
                                Verified
                                purchase
                            </option>

                            <option value="no">
                                Unverified
                            </option>
                        </FilterSelect>
                    </div>

                    <div className="mt-4 flex flex-wrap items-end justify-between gap-3">
                        <div className="w-32">
                            <FilterSelect
                                label="Per page"
                                value={
                                    perPage
                                }
                                onChange={
                                    setPerPage
                                }
                            >
                                <option value="10">
                                    10
                                </option>
                                <option value="20">
                                    20
                                </option>
                                <option value="50">
                                    50
                                </option>
                                <option value="100">
                                    100
                                </option>
                            </FilterSelect>
                        </div>

                        <div className="flex gap-2">
                            <button
                                type="button"
                                onClick={
                                    clearFilters
                                }
                                className="rounded-lg border px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Clear
                            </button>

                            <button
                                type="submit"
                                className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                            >
                                Apply filters
                            </button>
                        </div>
                    </div>
                </form>

                <div className="overflow-hidden rounded-xl border bg-white">
                    <div className="border-b px-4 py-3">
                        <p className="text-sm text-slate-600">
                            {
                                reviews.total
                            }{' '}
                            reviews
                        </p>
                    </div>

                    {reviews.data.length ===
                    0 ? (
                        <div className="p-8 text-center text-sm text-slate-500">
                            No reviews match
                            the selected
                            filters.
                        </div>
                    ) : (
                        <div className="divide-y">
                            {reviews.data.map(
                                (
                                    review,
                                ) => (
                                    <ReviewRow
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
                    )}

                    <Pagination
                        links={
                            reviews.links
                        }
                    />
                </div>
            </div>
        </AdminLayout>
    )
}

function ReviewRow({
    review,
}: {
    review: Review
}) {
    const [note, setNote] =
        useState(
            review
                .moderation_note ??
                '',
        )

    const [
        processing,
        setProcessing,
    ] =
        useState(false)

    const moderate = (
        action:
            | 'approve'
            | 'reject',
    ) => {
        if (processing) {
            return
        }

        setProcessing(true)

        router.post(
            route(
                `admin.reviews.${action}`,
                review.id,
            ),
            {
                note:
                    note.trim() ||
                    null,
            },
            {
                preserveScroll:
                    true,

                onFinish: () => {
                    setProcessing(
                        false,
                    )
                },
            },
        )
    }

    return (
        <article className="p-4 sm:p-5">
            <div className="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <StatusBadge
                            status={
                                review.status
                            }
                            label={
                                review
                                    .status_label
                            }
                        />

                        {review.is_verified_purchase && (
                            <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                Verified
                                purchase
                            </span>
                        )}

                        <span className="text-sm font-semibold text-amber-500">
                            {'★'.repeat(
                                review.rating,
                            )}

                            <span className="ml-1 text-slate-500">
                                {
                                    review.rating
                                }
                                /5
                            </span>
                        </span>
                    </div>

                    <h2 className="mt-3 text-base font-semibold text-slate-950">
                        {review.title ??
                            'Untitled review'}
                    </h2>

                    <p className="mt-2 whitespace-pre-line break-words text-sm leading-6 text-slate-700">
                        {review.body}
                    </p>

                    <div className="mt-4 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                        <div>
                            <span className="font-semibold text-slate-700">
                                Product:
                            </span>{' '}
                            {
                                review
                                    .product
                                    .name
                            }

                            {review.product
                                .sku && (
                                <span className="text-slate-400">
                                    {' '}
                                    (
                                    {
                                        review
                                            .product
                                            .sku
                                    }
                                    )
                                </span>
                            )}
                        </div>

                        <div>
                            <span className="font-semibold text-slate-700">
                                Customer:
                            </span>{' '}
                            {
                                review
                                    .customer
                                    .name
                            }{' '}
                            <span className="text-slate-400">
                                {
                                    review
                                        .customer
                                        .email
                                }
                            </span>
                        </div>
                    </div>

                    {review.moderator && (
                        <p className="mt-3 text-xs text-slate-500">
                            Last moderated
                            by{' '}
                            <strong>
                                {
                                    review
                                        .moderator
                                        .name
                                }
                            </strong>

                            {review.moderated_at &&
                                ` on ${formatDate(review.moderated_at)}`}
                        </p>
                    )}
                </div>

                <div className="w-full shrink-0 rounded-xl bg-slate-50 p-4 xl:w-80">
                    <label
                        htmlFor={`review-note-${review.id}`}
                        className="block text-sm font-semibold text-slate-700"
                    >
                        Moderation note
                    </label>

                    <textarea
                        id={`review-note-${review.id}`}
                        value={
                            note
                        }
                        onChange={(
                            event,
                        ) =>
                            setNote(
                                event
                                    .target
                                    .value,
                            )
                        }
                        rows={3}
                        maxLength={
                            2000
                        }
                        disabled={
                            processing
                        }
                        className="mt-2 w-full rounded-lg border-slate-300 text-sm"
                        placeholder="Optional internal note..."
                    />

                    <div className="mt-3 grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            disabled={
                                processing
                            }
                            onClick={() =>
                                moderate(
                                    'reject',
                                )
                            }
                            className="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50"
                        >
                            Reject
                        </button>

                        <button
                            type="button"
                            disabled={
                                processing
                            }
                            onClick={() =>
                                moderate(
                                    'approve',
                                )
                            }
                            className="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                        >
                            Approve
                        </button>
                    </div>
                </div>
            </div>
        </article>
    )
}

function StatusBadge({
    status,
    label,
}: {
    status: ReviewStatus
    label: string
}) {
    const classes: Record<
        ReviewStatus,
        string
    > = {
        pending:
            'bg-amber-50 text-amber-700',

        approved:
            'bg-emerald-50 text-emerald-700',

        rejected:
            'bg-red-50 text-red-700',
    }

    return (
        <span
            className={`rounded-full px-2.5 py-1 text-xs font-semibold ${classes[status]}`}
        >
            {label}
        </span>
    )
}

function FilterSelect({
    label,
    value,
    onChange,
    children,
}: {
    label: string
    value: string
    onChange: (
        value: string,
    ) => void
    children: ReactNode
}) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">
                {label}
            </label>

            <select
                value={
                    value
                }
                onChange={(
                    event,
                ) =>
                    onChange(
                        event
                            .target
                            .value,
                    )
                }
                className="w-full rounded-lg border-slate-300 text-sm"
            >
                {children}
            </select>
        </div>
    )
}

function Pagination({
    links,
}: {
    links: PaginationLink[]
}) {
    if (
        links.length <= 3
    ) {
        return null
    }

    return (
        <nav className="flex flex-wrap items-center justify-center gap-1 border-t p-4">
            {links.map(
                (
                    link,
                    index,
                ) => (
                    <button
                        key={
                            `${link.label}-${index}`
                        }
                        type="button"
                        disabled={
                            !link.url
                        }
                        onClick={() => {
                            if (
                                link.url
                            ) {
                                router.get(
                                    link.url,
                                    {},
                                    {
                                        preserveState:
                                            true,

                                        preserveScroll:
                                            true,
                                    },
                                )
                            }
                        }}
                        dangerouslySetInnerHTML={{
                            __html:
                                link.label,
                        }}
                        className={[
                            'min-h-9 rounded-lg border px-3 text-sm',
                            link.active
                                ? 'border-slate-900 bg-slate-900 text-white'
                                : 'border-slate-200 bg-white text-slate-700',
                            !link.url
                                ? 'cursor-not-allowed opacity-40'
                                : 'hover:bg-slate-50',
                        ].join(
                            ' ',
                        )}
                    />
                ),
            )}
        </nav>
    )
}

function formatDate(
    value: string,
): string {
    return new Intl.DateTimeFormat(
        undefined,
        {
            dateStyle:
                'medium',

            timeStyle:
                'short',
        },
    ).format(
        new Date(
            value,
        ),
    )
}