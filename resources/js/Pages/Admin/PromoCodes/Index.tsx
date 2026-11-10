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

type RuleSource =
    | 'promo_code'
    | 'promotion'
    | 'none'

type PromoStatus =
    | 'active'
    | 'scheduled'
    | 'expired'
    | 'inactive'

type EffectiveRules = {
    discount_type: string
    discount_type_label: string
    discount_value: number
    discount_display: string
    maximum_discount: number | null
    minimum_subtotal: number | null
    starts_at: string | null
    ends_at: string | null
    is_active: boolean
    is_stackable: boolean
    discount_source:
        | 'promo_code'
        | 'promotion'
    maximum_discount_source: RuleSource
    minimum_subtotal_source: RuleSource
    starts_at_source: RuleSource
    ends_at_source: RuleSource
    status: PromoStatus
    status_label: string
}

type PromoCode = {
    id: number

    promotion: {
        id: number
        name: string
    } | null

    code: string
    name: string

    discount_type: string | null
    discount_value: number | null

    maximum_discount: number | null
    minimum_subtotal: number | null

    usage_limit: number | null
    usage_limit_per_customer: number | null

    redeemed_count: number
    usages_count: number

    starts_at: string | null
    ends_at: string | null

    is_active: boolean
    is_stackable: boolean

    effective: EffectiveRules

    can_delete: boolean
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type Props = {
    promoCodes: {
        data: PromoCode[]
        links: PaginationLink[]
    }

    filters: {
        search: string
        status: string
        promotion: string
        discount_type: string
        source: string
        usage: string
        per_page: number
    }

    promotions: Array<{
        id: number
        name: string
    }>

    discountTypes: Array<{
        value: string
        label: string
    }>
}

export default function Index({
    promoCodes,
    filters,
    promotions,
    discountTypes,
}: Props) {
    const [search, setSearch] =
        useState(filters.search)

    const [status, setStatus] =
        useState(filters.status)

    const [promotion, setPromotion] =
        useState(filters.promotion)

    const [source, setSource] =
        useState(filters.source)

    const [
        discountType,
        setDiscountType,
    ] = useState(
        filters.discount_type,
    )

    const [usage, setUsage] =
        useState(filters.usage)

    const [perPage, setPerPage] =
        useState(
            String(
                filters.per_page,
            ),
        )

    const applyFilters = () => {
        router.get(
            route(
                'admin.promo-codes.index',
            ),
            {
                search:
                    search || undefined,

                status:
                    status || undefined,

                promotion:
                    promotion ||
                    undefined,

                source:
                    source || undefined,

                discount_type:
                    discountType ||
                    undefined,

                usage:
                    usage || undefined,

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
        setPromotion('')
        setSource('')
        setDiscountType('')
        setUsage('')
        setPerPage('20')

        router.get(
            route(
                'admin.promo-codes.index',
            ),
            {},
            {
                replace: true,
            },
        )
    }

    const [
        promoCodeToDelete,
        setPromoCodeToDelete,
    ] = useState<PromoCode | null>(
        null,
    )

    const [deleting, setDeleting] =
        useState(false)

    const deletePromoCode = () => {
        if (!promoCodeToDelete) {
            return
        }

        setDeleting(true)

        router.delete(
            route(
                'admin.promo-codes.destroy',
                promoCodeToDelete.id,
            ),
            {
                preserveScroll: true,

                onSuccess: () => {
                    setPromoCodeToDelete(
                        null,
                    )
                },

                onFinish: () => {
                    setDeleting(false)
                },
            },
        )
    }

    return (
        <AdminLayout
            title="Promo codes"
            description="Manage promo codes, effective discount rules and usage limits."
        >
            <Head title="Promo Codes" />

            <div className="space-y-6 p-4 sm:p-6">
                <div className="my-4 text-center sm:text-right">
                    <div className="inline-flex flex-wrap gap-2">
                        <Link
                            href={route(
                                'admin.promotions.index',
                            )}
                            className="rounded-lg border px-4 py-2 text-sm font-semibold"
                        >
                            Promotions
                        </Link>

                        <Link
                            href={route(
                                'admin.promo-codes.create',
                            )}
                            className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
                        >
                            Add promo code
                        </Link>
                    </div>
                </div>

                <div className="rounded-xl border bg-white p-4">
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <input
                            value={search}
                            onChange={(event) =>
                                setSearch(
                                    event.target.value,
                                )
                            }
                            onKeyDown={(event) => {
                                if (
                                    event.key ===
                                    'Enter'
                                ) {
                                    applyFilters()
                                }
                            }}
                            placeholder="Search code or name..."
                            className="rounded-lg border px-3 py-2 text-sm"
                        />

                        <select
                            value={status}
                            onChange={(event) =>
                                setStatus(
                                    event.target.value,
                                )
                            }
                            className="rounded-lg border px-3 py-2 text-sm"
                        >
                            <option value="">
                                All statuses
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
                            value={promotion}
                            onChange={(event) =>
                                setPromotion(
                                    event.target.value,
                                )
                            }
                            className="rounded-lg border px-3 py-2 text-sm"
                        >
                            <option value="">
                                All promotions
                            </option>

                            <option value="standalone">
                                Standalone only
                            </option>

                            {promotions.map(
                                (item) => (
                                    <option
                                        key={
                                            item.id
                                        }
                                        value={
                                            item.id
                                        }
                                    >
                                        {item.name}
                                    </option>
                                ),
                            )}
                        </select>

                        <select
                            value={source}
                            onChange={(event) =>
                                setSource(
                                    event.target.value,
                                )
                            }
                            className="rounded-lg border px-3 py-2 text-sm"
                        >
                            <option value="">
                                All rule sources
                            </option>
                            <option value="standalone">
                                Standalone
                            </option>
                            <option value="inherited">
                                Inherited
                            </option>
                            <option value="override">
                                Override
                            </option>
                        </select>

                        <select
                            value={discountType}
                            onChange={(event) =>
                                setDiscountType(
                                    event.target.value,
                                )
                            }
                            className="rounded-lg border px-3 py-2 text-sm"
                        >
                            <option value="">
                                All discount types
                            </option>

                            {discountTypes.map(
                                (type) => (
                                    <option
                                        key={
                                            type.value
                                        }
                                        value={
                                            type.value
                                        }
                                    >
                                        {type.label}
                                    </option>
                                ),
                            )}
                        </select>

                        <select
                            value={usage}
                            onChange={(event) =>
                                setUsage(
                                    event.target.value,
                                )
                            }
                            className="rounded-lg border px-3 py-2 text-sm"
                        >
                            <option value="">
                                All usage states
                            </option>
                            <option value="unused">
                                Never used
                            </option>
                            <option value="used">
                                Used
                            </option>
                            <option value="exhausted">
                                Usage exhausted
                            </option>
                        </select>

                        <select
                            value={perPage}
                            onChange={(event) =>
                                setPerPage(
                                    event.target.value,
                                )
                            }
                            className="rounded-lg border px-3 py-2 text-sm"
                        >
                            <option value="10">
                                10 / page
                            </option>
                            <option value="20">
                                20 / page
                            </option>
                            <option value="50">
                                50 / page
                            </option>
                            <option value="100">
                                100 / page
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
                            Apply filters
                        </button>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border bg-white">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y">
                            <thead className="bg-slate-50">
                                <tr>
                                    <Th>
                                        Code
                                    </Th>

                                    <Th>
                                        Promotion
                                    </Th>

                                    <Th>
                                        Effective discount
                                    </Th>

                                    <Th>
                                        Usage
                                    </Th>

                                    <Th>
                                        Status
                                    </Th>

                                    <Th />
                                </tr>
                            </thead>

                            <tbody className="divide-y">
                                {promoCodes.data.map(
                                    (
                                        promoCode,
                                    ) => (
                                        <tr
                                            key={
                                                promoCode.id
                                            }
                                        >
                                            <Td>
                                                <div className="font-mono font-bold">
                                                    {
                                                        promoCode.code
                                                    }
                                                </div>

                                                <div className="text-xs text-slate-500">
                                                    {
                                                        promoCode.name
                                                    }
                                                </div>
                                            </Td>

                                            <Td>
                                                <div className="space-y-1">
                                                    <div>
                                                        {promoCode
                                                            .promotion
                                                            ?.name ??
                                                            'Standalone'}
                                                    </div>

                                                    {promoCode
                                                        .promotion && (
                                                        <div className="text-xs text-slate-500">
                                                            Campaign
                                                            linked
                                                        </div>
                                                    )}
                                                </div>
                                            </Td>

                                            <Td>
                                                <div className="space-y-1.5">
                                                    <div className="font-semibold text-slate-900">
                                                        {
                                                            promoCode
                                                                .effective
                                                                .discount_display
                                                        }
                                                    </div>

                                                    <SourceBadge
                                                        source={
                                                            promoCode
                                                                .effective
                                                                .discount_source
                                                        }
                                                    />

                                                    {promoCode
                                                        .effective
                                                        .maximum_discount !==
                                                        null && (
                                                        <div className="text-xs text-slate-500">
                                                            Max
                                                            Tk{' '}
                                                            {
                                                                promoCode
                                                                    .effective
                                                                    .maximum_discount
                                                            }
                                                        </div>
                                                    )}

                                                    {promoCode
                                                        .effective
                                                        .minimum_subtotal !==
                                                        null && (
                                                        <div className="text-xs text-slate-500">
                                                            Min
                                                            subtotal
                                                            Tk{' '}
                                                            {
                                                                promoCode
                                                                    .effective
                                                                    .minimum_subtotal
                                                            }
                                                        </div>
                                                    )}
                                                </div>
                                            </Td>

                                            <Td>
                                                <UsageProgress
                                                    used={
                                                        promoCode.redeemed_count
                                                    }
                                                    limit={
                                                        promoCode.usage_limit
                                                    }
                                                />

                                                {promoCode.usage_limit_per_customer !==
                                                    null && (
                                                    <div className="mt-1 text-xs text-slate-500">
                                                        Max{' '}
                                                        {
                                                            promoCode.usage_limit_per_customer
                                                        }{' '}
                                                        per
                                                        customer
                                                    </div>
                                                )}
                                            </Td>

                                            <Td>
                                                <div className="space-y-2">
                                                    <PromoStatusBadge
                                                        status={
                                                            promoCode
                                                                .effective
                                                                .status
                                                        }
                                                        label={
                                                            promoCode
                                                                .effective
                                                                .status_label
                                                        }
                                                    />

                                                    <ScheduleSummary
                                                        startsAt={
                                                            promoCode
                                                                .effective
                                                                .starts_at
                                                        }
                                                        endsAt={
                                                            promoCode
                                                                .effective
                                                                .ends_at
                                                        }
                                                    />
                                                </div>
                                            </Td>

                                            <Td>
                                                <div className="flex justify-end gap-3">
                                                    <Link
                                                        href={route(
                                                            'admin.promo-codes.edit',
                                                            promoCode.id,
                                                        )}
                                                        className="text-sm font-semibold"
                                                    >
                                                        Edit
                                                    </Link>

                                                    {promoCode.can_delete ? (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setPromoCodeToDelete(
                                                                    promoCode,
                                                                )
                                                            }
                                                            className="text-sm font-semibold text-red-600"
                                                        >
                                                            Delete
                                                        </button>
                                                    ) : (
                                                        <span
                                                            className="cursor-not-allowed text-sm font-semibold text-slate-400"
                                                            title="This promo code has redemption history. Disable it instead of deleting it."
                                                        >
                                                            Delete
                                                        </span>
                                                    )}
                                                </div>
                                            </Td>
                                        </tr>
                                    ),
                                )}

                                {promoCodes.data
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
                                            promo
                                            codes
                                            yet.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <Pagination
                    links={
                        promoCodes.links
                    }
                />
            </div>

            <ConfirmDialog
                open={
                    promoCodeToDelete !==
                    null
                }
                title="Delete promo code?"
                description={
                    promoCodeToDelete
                        ? `Delete promo code "${promoCodeToDelete.code}"? This action cannot be undone.`
                        : ''
                }
                confirmLabel="Delete Promo Code"
                cancelLabel="Cancel"
                processing={deleting}
                onConfirm={
                    deletePromoCode
                }
                onCancel={() =>
                    setPromoCodeToDelete(
                        null,
                    )
                }
            />
        </AdminLayout>
    )
}

function SourceBadge({
    source,
}: {
    source: 'promo_code' | 'promotion'
}) {
    return (
        <span className="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
            {source === 'promotion'
                ? 'Inherited'
                : 'Promo-code override'}
        </span>
    )
}

function PromoStatusBadge({
    status,
    label,
}: {
    status: PromoStatus
    label: string
}) {
    const classes: Record<
        PromoStatus,
        string
    > = {
        active:
            'bg-emerald-50 text-emerald-700',

        scheduled:
            'bg-blue-50 text-blue-700',

        expired:
            'bg-amber-50 text-amber-700',

        inactive:
            'bg-slate-100 text-slate-600',
    }

    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${classes[status]}`}
        >
            {label}
        </span>
    )
}

function UsageProgress({
    used,
    limit,
}: {
    used: number
    limit: number | null
}) {
    const percentage =
        limit === null
            ? null
            : Math.min(
                  100,
                  Math.round(
                      (used /
                          limit) *
                          100,
                  ),
              )

    return (
        <div className="min-w-28 space-y-1">
            <div className="text-sm font-medium">
                {used} /{' '}
                {limit ?? '∞'}
            </div>

            {percentage !== null && (
                <div className="h-1.5 overflow-hidden rounded-full bg-slate-100">
                    <div
                        className="h-full bg-slate-700"
                        style={{
                            width: `${percentage}%`,
                        }}
                    />
                </div>
            )}
        </div>
    )
}

function ScheduleSummary({
    startsAt,
    endsAt,
}: {
    startsAt: string | null
    endsAt: string | null
}) {
    if (!startsAt && !endsAt) {
        return (
            <div className="text-xs text-slate-500">
                Always
                available
            </div>
        )
    }

    return (
        <div className="space-y-0.5 text-xs text-slate-500">
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
                (link, index) =>
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