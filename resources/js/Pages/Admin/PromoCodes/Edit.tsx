import { Head } from '@inertiajs/react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'

import PromoCodeForm from './PromoCodeForm'

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
    promotion_id: number | null

    code: string
    name: string
    description: string | null

    discount_type: string | null
    discount_value: number | null

    maximum_discount: number | null
    minimum_subtotal: number | null

    usage_limit: number | null
    usage_limit_per_customer:
        number | null

    redeemed_count: number

    starts_at: string | null
    ends_at: string | null

    is_active: boolean
    is_stackable: boolean
}

type Props = {
    promoCode: PromoCode

    discountTypes: Array<{
        value: string
        label: string
    }>

    promotions: Array<{
        id: number
        name: string
    }>

    effective: EffectiveRules

    canDelete: boolean
}

const dt = (
    value: string | null,
): string =>
    value?.slice(0, 16) ?? ''

const nullableNumber = (
    value: number | null,
): string =>
    value === null
        ? ''
        : String(value)

/*
 * Stored monetary values use minor units.
 *
 * Example:
 *
 * 15000 => BDT 150
 * 150050 => BDT 1,500.50
 *
 * Admin forms should display major-unit
 * BDT values instead of raw stored integers.
 */
const minorMoneyToInput = (
    value: number | null,
): string => {
    if (value === null) {
        return ''
    }

    const major =
        value / 100

    return Number.isInteger(
        major,
    )
        ? String(
              major,
          )
        : major.toFixed(
              2,
          )
}

/*
 * Percentage discount values are basis points
 * and must not be divided by 100 here.
 *
 * Example:
 *
 * 1000 = 10%
 *
 * Fixed discount values are monetary minor
 * units and must be converted back to BDT.
 */
const discountValueToInput = (
    type: string | null,
    value: number | null,
): string => {
    if (value === null) {
        return ''
    }

    if (type === 'fixed') {
        return minorMoneyToInput(
            value,
        )
    }

    return String(
        value,
    )
}

const formatMinorMoney = (
    value: number,
): string => {
    const major =
        value / 100

    return new Intl.NumberFormat(
        'en-BD',
        {
            minimumFractionDigits:
                Number.isInteger(
                    major,
                )
                    ? 0
                    : 2,

            maximumFractionDigits:
                2,
        },
    ).format(
        major,
    )
}

export default function Edit({
    promoCode,
    discountTypes,
    promotions,
    effective,
    canDelete,
}: Props) {
    return (
        <AdminLayout
            title={`Edit ${promoCode.name}`}
            description="Update promo code rules, limits and availability."
        >
            <Head
                title={`Edit ${promoCode.code}`}
            />

            <div className="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
                <EffectiveRulePreview
                    effective={
                        effective
                    }
                />

                {!canDelete && (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        This promo
                        code has
                        redemption
                        history and
                        cannot be
                        deleted.
                        Disable it
                        instead if it
                        should no
                        longer be
                        available.
                    </div>
                )}

                <PromoCodeForm
                    discountTypes={
                        discountTypes
                    }
                    promotions={
                        promotions
                    }
                    redeemedCount={
                        promoCode.redeemed_count
                    }
                    submitLabel="Save changes"
                    initialValues={{
                        promotion_id:
                            promoCode.promotion_id ===
                            null
                                ? ''
                                : String(
                                      promoCode.promotion_id,
                                  ),

                        code:
                            promoCode.code,

                        name:
                            promoCode.name,

                        description:
                            promoCode.description ??
                            '',

                        discount_type:
                            promoCode.discount_type ??
                            '',

                        discount_value:
                            discountValueToInput(
                                promoCode.discount_type,
                                promoCode.discount_value,
                            ),

                        maximum_discount:
                            minorMoneyToInput(
                                promoCode.maximum_discount,
                            ),

                        minimum_subtotal:
                            minorMoneyToInput(
                                promoCode.minimum_subtotal,
                            ),

                        usage_limit:
                            nullableNumber(
                                promoCode.usage_limit,
                            ),

                        usage_limit_per_customer:
                            nullableNumber(
                                promoCode.usage_limit_per_customer,
                            ),

                        starts_at:
                            dt(
                                promoCode.starts_at,
                            ),

                        ends_at:
                            dt(
                                promoCode.ends_at,
                            ),

                        is_active:
                            promoCode.is_active,

                        is_stackable:
                            promoCode.is_stackable,
                    }}
                    onSubmit={(
                        form,
                    ) =>
                        form.put(
                            route(
                                'admin.promo-codes.update',
                                promoCode.id,
                            ),
                        )
                    }
                />
            </div>
        </AdminLayout>
    )
}

function EffectiveRulePreview({
    effective,
}: {
    effective: EffectiveRules
}) {
    return (
        <section className="rounded-xl border bg-slate-50 p-5">
            <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="font-semibold text-slate-900">
                        Current
                        effective
                        rules
                    </h2>

                    <p className="mt-1 text-sm text-slate-500">
                        These are
                        the saved
                        rules after
                        resolving
                        parent
                        promotion
                        inheritance.
                    </p>
                </div>

                <PromoStatusBadge
                    status={
                        effective.status
                    }
                    label={
                        effective.status_label
                    }
                />
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <PreviewItem
                    label="Discount"
                    value={
                        effective.discount_display
                    }
                    source={
                        effective.discount_source
                    }
                />

                <PreviewItem
                    label="Maximum discount"
                    value={
                        effective.maximum_discount ===
                        null
                            ? 'No limit'
                            : `Tk ${formatMinorMoney(
                                  effective.maximum_discount,
                              )}`
                    }
                    source={
                        effective.maximum_discount_source
                    }
                />

                <PreviewItem
                    label="Minimum subtotal"
                    value={
                        effective.minimum_subtotal ===
                        null
                            ? 'None'
                            : `Tk ${formatMinorMoney(
                                  effective.minimum_subtotal,
                              )}`
                    }
                    source={
                        effective.minimum_subtotal_source
                    }
                />

                <PreviewItem
                    label="Stacking"
                    value={
                        effective.is_stackable
                            ? 'Allowed'
                            : 'Not allowed'
                    }
                    source="effective"
                />

                <PreviewItem
                    label="Starts at"
                    value={
                        effective.starts_at
                            ? formatDate(
                                  effective.starts_at,
                              )
                            : 'No start restriction'
                    }
                    source={
                        effective.starts_at_source
                    }
                />

                <PreviewItem
                    label="Ends at"
                    value={
                        effective.ends_at
                            ? formatDate(
                                  effective.ends_at,
                              )
                            : 'No expiry'
                    }
                    source={
                        effective.ends_at_source
                    }
                />

                <PreviewItem
                    label="Effective state"
                    value={
                        effective.is_active
                            ? 'Enabled'
                            : 'Disabled'
                    }
                    source="effective"
                />

                <PreviewItem
                    label="Discount type"
                    value={
                        effective.discount_type_label
                    }
                    source={
                        effective.discount_source
                    }
                />
            </div>
        </section>
    )
}

function PreviewItem({
    label,
    value,
    source,
}: {
    label: string
    value: string
    source:
        | RuleSource
        | 'effective'
}) {
    return (
        <div className="rounded-lg border bg-white p-4">
            <div className="text-xs font-medium uppercase tracking-wide text-slate-500">
                {label}
            </div>

            <div className="mt-1 font-semibold text-slate-900">
                {value}
            </div>

            <div className="mt-2 text-xs text-slate-500">
                {sourceLabel(
                    source,
                )}
            </div>
        </div>
    )
}

function sourceLabel(
    source:
        | RuleSource
        | 'effective',
): string {
    switch (source) {
        case 'promotion':
            return 'Inherited from promotion'

        case 'promo_code':
            return 'Promo-code override'

        case 'none':
            return 'No rule configured'

        default:
            return 'Resolved effective rule'
    }
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
            'bg-slate-200 text-slate-700',
    }

    return (
        <span
            className={`inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold ${classes[status]}`}
        >
            {label}
        </span>
    )
}

function formatDate(
    value: string,
): string {
    return new Date(
        value,
    ).toLocaleString()
}
