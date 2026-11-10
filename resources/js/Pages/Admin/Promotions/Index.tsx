import {
    Head,
    Link,
    router,
} from '@inertiajs/react'
import {
    useState,
    type ReactNode,
} from 'react'

import ConfirmDialog from '@/Components/Admin/ConfirmDialog'
import AdminLayout from '@/Layouts/Admin/AdminLayout'

type Promotion = {
    id: number
    name: string
    slug: string

    discount_type: string
    discount_type_label: string
    discount_value: number

    maximum_discount:
        number | null

    minimum_subtotal:
        number | null

    starts_at: string | null
    ends_at: string | null

    is_active: boolean
    is_stackable: boolean

    promo_codes_count: number

    can_delete: boolean

    delete_block_reason:
        | string
        | null
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type Props = {
    promotions: {
        data: Promotion[]
        links: PaginationLink[]
    }

    filters: {
        search: string
        status: string
        discount_type: string
        stackable: string
        per_page: number
    }

    discountTypes: Array<{
        value: string
        label: string
    }>
}

const discountLabel = (
    promotion: Promotion,
): string => {
    if (
        promotion.discount_type ===
        'percentage'
    ) {
        return `${promotion.discount_value / 100}%`
    }

    return `Tk ${promotion.discount_value}`
}

export default function Index({
    promotions,
    filters,
    discountTypes,
}: Props) {
    const [search, setSearch] =
        useState(
            filters.search,
        )

    const [status, setStatus] =
        useState(
            filters.status,
        )

    const [
        discountType,
        setDiscountType,
    ] = useState(
        filters.discount_type,
    )

    const [
        stackable,
        setStackable,
    ] = useState(
        filters.stackable,
    )

    const [
        perPage,
        setPerPage,
    ] = useState(
        String(
            filters.per_page,
        ),
    )

    const [
        promotionToDelete,
        setPromotionToDelete,
    ] =
        useState<Promotion | null>(
            null,
        )

    const [
        deleting,
        setDeleting,
    ] = useState(false)

    const applyFilters = () => {
        router.get(
            route(
                'admin.promotions.index',
            ),
            {
                search:
                    search ||
                    undefined,

                status:
                    status ||
                    undefined,

                discount_type:
                    discountType ||
                    undefined,

                stackable:
                    stackable ||
                    undefined,

                per_page:
                    perPage,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const resetFilters = () => {
        setSearch('')
        setStatus('')
        setDiscountType('')
        setStackable('')
        setPerPage('20')

        router.get(
            route(
                'admin.promotions.index',
            ),
            {},
            {
                replace: true,
            },
        )
    }

    const deletePromotion = () => {
        if (
            !promotionToDelete
        ) {
            return
        }

        if (
            !promotionToDelete.can_delete
        ) {
            setPromotionToDelete(
                null,
            )

            return
        }

        setDeleting(true)

        router.delete(
            route(
                'admin.promotions.destroy',
                promotionToDelete.id,
            ),
            {
                preserveScroll:
                    true,

                onSuccess: () => {
                    setPromotionToDelete(
                        null,
                    )
                },

                onFinish: () => {
                    setDeleting(
                        false,
                    )
                },
            },
        )
    }

    return (
        <AdminLayout
            title="Promotions"
            description="Manage promotional campaigns and default discount rules."
        >
            <Head title="Promotions" />

            <div className="space-y-6 p-4 sm:p-6">
                <div className="my-4 text-center sm:text-right">
                    <div className="inline-flex flex-wrap gap-2">
                        <Link
                            href={route(
                                'admin.promo-codes.index',
                            )}
                            className="rounded-lg border px-4 py-2 text-sm font-semibold"
                        >
                            Promo codes
                        </Link>

                        <Link
                            href={route(
                                'admin.promotions.create',
                            )}
                            className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
                        >
                            Add
                            promotion
                        </Link>
                    </div>
                </div>

                <div className="rounded-xl border bg-white p-4">
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                        <input
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
                            onKeyDown={(
                                event,
                            ) => {
                                if (
                                    event.key ===
                                    'Enter'
                                ) {
                                    applyFilters()
                                }
                            }}
                            placeholder="Search name or slug..."
                            className="rounded-lg border px-3 py-2 text-sm xl:col-span-2"
                        />

                        <select
                            value={
                                status
                            }
                            onChange={(
                                event,
                            ) =>
                                setStatus(
                                    event
                                        .target
                                        .value,
                                )
                            }
                            className="rounded-lg border px-3 py-2 text-sm"
                        >
                            <option value="">
                                All
                                statuses
                            </option>

                            <option value="active">
                                Active
                            </option>

                            <option value="scheduled">
                                Scheduled
                            </option>

                            <option value="expired">
                                Expired
                            </option>

                            <option value="inactive">
                                Inactive
                            </option>
                        </select>

                        <select
                            value={
                                discountType
                            }
                            onChange={(
                                event,
                            ) =>
                                setDiscountType(
                                    event
                                        .target
                                        .value,
                                )
                            }
                            className="rounded-lg border px-3 py-2 text-sm"
                        >
                            <option value="">
                                All
                                discount
                                types
                            </option>

                            {discountTypes.map(
                                (
                                    type,
                                ) => (
                                    <option
                                        key={
                                            type.value
                                        }
                                        value={
                                            type.value
                                        }
                                    >
                                        {
                                            type.label
                                        }
                                    </option>
                                ),
                            )}
                        </select>

                        <select
                            value={
                                stackable
                            }
                            onChange={(
                                event,
                            ) =>
                                setStackable(
                                    event
                                        .target
                                        .value,
                                )
                            }
                            className="rounded-lg border px-3 py-2 text-sm"
                        >
                            <option value="">
                                Any
                                stacking
                            </option>

                            <option value="yes">
                                Stackable
                            </option>

                            <option value="no">
                                Not
                                stackable
                            </option>
                        </select>

                        <select
                            value={
                                perPage
                            }
                            onChange={(
                                event,
                            ) =>
                                setPerPage(
                                    event
                                        .target
                                        .value,
                                )
                            }
                            className="rounded-lg border px-3 py-2 text-sm"
                        >
                            <option value="10">
                                10 /
                                page
                            </option>

                            <option value="20">
                                20 /
                                page
                            </option>

                            <option value="50">
                                50 /
                                page
                            </option>

                            <option value="100">
                                100 /
                                page
                            </option>
                        </select>
                    </div>

                    <div className="mt-3 flex flex-wrap justify-end gap-2">
                        <button
                            type="button"
                            onClick={
                                resetFilters
                            }
                            className="rounded-lg border px-4 py-2 text-sm font-medium"
                        >
                            Reset
                        </button>

                        <button
                            type="button"
                            onClick={
                                applyFilters
                            }
                            className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
                        >
                            Apply
                            filters
                        </button>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border bg-white">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y">
                            <thead className="bg-slate-50">
                                <tr>
                                    <Th>
                                        Promotion
                                    </Th>

                                    <Th>
                                        Discount
                                    </Th>

                                    <Th>
                                        Status
                                    </Th>

                                    <Th>
                                        Promo
                                        codes
                                    </Th>

                                    <Th>
                                        Schedule
                                    </Th>

                                    <Th />
                                </tr>
                            </thead>

                            <tbody className="divide-y">
                                {promotions.data.map(
                                    (
                                        promotion,
                                    ) => (
                                        <tr
                                            key={
                                                promotion.id
                                            }
                                        >
                                            <Td>
                                                <div className="font-semibold">
                                                    {
                                                        promotion.name
                                                    }
                                                </div>

                                                <div className="text-xs text-slate-500">
                                                    {
                                                        promotion.slug
                                                    }
                                                </div>
                                            </Td>

                                            <Td>
                                                <div className="font-medium">
                                                    {discountLabel(
                                                        promotion,
                                                    )}
                                                </div>

                                                {promotion.maximum_discount !==
                                                    null && (
                                                    <div className="text-xs text-slate-500">
                                                        Max
                                                        Tk{' '}
                                                        {
                                                            promotion.maximum_discount
                                                        }
                                                    </div>
                                                )}
                                            </Td>

                                            <Td>
                                                <StatusBadge
                                                    active={
                                                        promotion.is_active
                                                    }
                                                />
                                            </Td>

                                            <Td>
                                                <div className="space-y-1">
                                                    <div className="font-medium">
                                                        {
                                                            promotion.promo_codes_count
                                                        }
                                                    </div>

                                                    {!promotion.can_delete && (
                                                        <div className="max-w-52 whitespace-normal text-xs leading-5 text-amber-700">
                                                            Linked
                                                            promo
                                                            codes
                                                            must
                                                            be
                                                            detached
                                                            before
                                                            deleting
                                                            this
                                                            promotion.
                                                        </div>
                                                    )}
                                                </div>
                                            </Td>

                                            <Td>
                                                <Schedule
                                                    startsAt={
                                                        promotion.starts_at
                                                    }
                                                    endsAt={
                                                        promotion.ends_at
                                                    }
                                                />
                                            </Td>

                                            <Td>
                                                <div className="flex justify-end gap-3">
                                                    <Link
                                                        href={route(
                                                            'admin.promotions.edit',
                                                            promotion.id,
                                                        )}
                                                        className="text-sm font-semibold"
                                                    >
                                                        Edit
                                                    </Link>

                                                    {promotion.can_delete ? (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setPromotionToDelete(
                                                                    promotion,
                                                                )
                                                            }
                                                            className="text-sm font-semibold text-red-600"
                                                        >
                                                            Delete
                                                        </button>
                                                    ) : (
                                                        <span
                                                            className="cursor-not-allowed text-sm font-semibold text-slate-400"
                                                            title={
                                                                promotion.delete_block_reason ??
                                                                'This promotion cannot be deleted.'
                                                            }
                                                        >
                                                            Delete
                                                        </span>
                                                    )}
                                                </div>
                                            </Td>
                                        </tr>
                                    ),
                                )}

                                {promotions.data
                                    .length ===
                                    0 && (
                                    <tr>
                                        <td
                                            colSpan={
                                                6
                                            }
                                            className="p-10 text-center text-sm text-slate-500"
                                        >
                                            No
                                            promotions
                                            found.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <Pagination
                    links={
                        promotions.links
                    }
                />
            </div>

            <ConfirmDialog
                open={
                    promotionToDelete !==
                    null
                }
                title="Delete promotion?"
                description={
                    promotionToDelete
                        ? `Delete "${promotionToDelete.name}"? This action cannot be undone.`
                        : ''
                }
                confirmLabel="Delete Promotion"
                cancelLabel="Cancel"
                processing={
                    deleting
                }
                onConfirm={
                    deletePromotion
                }
                onCancel={() =>
                    setPromotionToDelete(
                        null,
                    )
                }
            />
        </AdminLayout>
    )
}

function Th({
    children,
}: {
    children?: ReactNode
}) {
    return (
        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
            {children}
        </th>
    )
}

function Td({
    children,
}: {
    children: ReactNode
}) {
    return (
        <td className="whitespace-nowrap px-4 py-4 align-top text-sm">
            {children}
        </td>
    )
}

function StatusBadge({
    active,
}: {
    active: boolean
}) {
    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${
                active
                    ? 'bg-emerald-50 text-emerald-700'
                    : 'bg-slate-100 text-slate-600'
            }`}
        >
            {active
                ? 'Active'
                : 'Inactive'}
        </span>
    )
}

function Schedule({
    startsAt,
    endsAt,
}: {
    startsAt:
        | string
        | null

    endsAt:
        | string
        | null
}) {
    if (
        !startsAt &&
        !endsAt
    ) {
        return (
            <span className="text-slate-500">
                Always
            </span>
        )
    }

    return (
        <div className="text-xs text-slate-600">
            {startsAt && (
                <div>
                    From{' '}
                    {new Date(
                        startsAt,
                    ).toLocaleString()}
                </div>
            )}

            {endsAt && (
                <div>
                    Until{' '}
                    {new Date(
                        endsAt,
                    ).toLocaleString()}
                </div>
            )}
        </div>
    )
}

function Pagination({
    links,
}: {
    links: PaginationLink[]
}) {
    return (
        <div className="flex flex-wrap gap-2">
            {links.map(
                (
                    link,
                    index,
                ) =>
                    link.url ? (
                        <Link
                            key={
                                index
                            }
                            href={
                                link.url
                            }
                            preserveScroll
                            className={`rounded border px-3 py-1.5 text-sm ${
                                link.active
                                    ? 'bg-slate-900 text-white'
                                    : 'bg-white'
                            }`}
                            dangerouslySetInnerHTML={{
                                __html:
                                    link.label,
                            }}
                        />
                    ) : (
                        <span
                            key={
                                index
                            }
                            className="rounded border px-3 py-1.5 text-sm text-slate-400"
                            dangerouslySetInnerHTML={{
                                __html:
                                    link.label,
                            }}
                        />
                    ),
            )}
        </div>
    )
}