import { Heart, ShoppingBag } from 'lucide-react'

import HeaderEditorAccordion from '@/Components/Admin/HeaderBuilder/HeaderEditorAccordion'
import type { HeaderActionsConfig, HeaderSimpleActionConfig } from '@/types/header-builder'

interface Props {
    value: HeaderActionsConfig
    disabled?: boolean
    onChange: (value: HeaderActionsConfig) => void
}

export default function HeaderActionsEditor({ value, disabled = false, onChange }: Props) {
    const updateAction = (key: 'wishlist' | 'cart', nextValue: HeaderSimpleActionConfig) => {
        if (disabled) {
            return
        }

        onChange({
            ...value,

            [key]: nextValue,
        })
    }

    return (
        <HeaderEditorAccordion
            title="Header Actions"
            description="Configure the storefront wishlist and cart action buttons displayed in the main header."
            icon={<ShoppingBag className="h-5 w-5" strokeWidth={1.9} aria-hidden="true" />}
            badge={
                <ActionCountBadge
                    count={[value.wishlist.enabled, value.cart.enabled].filter(Boolean).length}
                />
            }
        >
            <div className="space-y-5 p-5 sm:p-6">
                <ActionEditor
                    title="Wishlist"
                    description="Show a wishlist shortcut in the storefront header."
                    icon={<Heart className="h-5 w-5" strokeWidth={1.9} aria-hidden="true" />}
                    value={value.wishlist}
                    disabled={disabled}
                    placeholder="/wishlist"
                    onChange={(nextValue) => updateAction('wishlist', nextValue)}
                />

                <ActionEditor
                    title="Cart"
                    description="Show a cart shortcut in the storefront header."
                    icon={<ShoppingBag className="h-5 w-5" strokeWidth={1.9} aria-hidden="true" />}
                    value={value.cart}
                    disabled={disabled}
                    placeholder="/cart"
                    onChange={(nextValue) => updateAction('cart', nextValue)}
                />

                <div className="rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <p className="text-sm font-semibold text-blue-950">Runtime counts</p>

                    <p className="mt-1 text-xs leading-5 text-blue-800">
                        Wishlist and cart item counts are not Header Builder configuration. They
                        will come from real storefront state during 4.17F.
                    </p>
                </div>
            </div>
        </HeaderEditorAccordion>
    )
}

function ActionEditor({
    title,
    description,
    icon,
    value,
    disabled,
    placeholder,
    onChange,
}: {
    title: string
    description: string
    icon: React.ReactNode
    value: HeaderSimpleActionConfig
    disabled: boolean
    placeholder: string
    onChange: (value: HeaderSimpleActionConfig) => void
}) {
    const update = <K extends keyof HeaderSimpleActionConfig>(
        key: K,
        nextValue: HeaderSimpleActionConfig[K],
    ) => {
        if (disabled) {
            return
        }

        onChange({
            ...value,

            [key]: nextValue,
        })
    }

    return (
        <section className="min-w-0 rounded-2xl border border-neutral-200 bg-neutral-50 p-4 sm:p-5">
            <div className="flex min-w-0 flex-wrap items-start justify-between gap-4">
                <div className="flex min-w-0 items-start gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-neutral-200 bg-white text-neutral-700">
                        {icon}
                    </div>

                    <div className="min-w-0">
                        <div className="flex min-w-0 flex-wrap items-center gap-2">
                            <h3 className="text-sm font-semibold text-neutral-950">{title}</h3>

                            <StatusBadge enabled={value.enabled} />
                        </div>

                        <p className="mt-1 text-xs leading-5 text-neutral-500">{description}</p>
                    </div>
                </div>

                <label className="flex shrink-0 items-center gap-3">
                    <span className="text-xs font-semibold text-neutral-600">
                        {value.enabled ? 'Enabled' : 'Disabled'}
                    </span>

                    <input
                        type="checkbox"
                        checked={value.enabled}
                        disabled={disabled}
                        onChange={(event) => update('enabled', event.target.checked)}
                        className="h-5 w-5 rounded border-neutral-300 text-neutral-950 focus:ring-neutral-950 disabled:cursor-not-allowed disabled:opacity-50"
                    />
                </label>
            </div>

            <div className={['mt-5', value.enabled ? '' : 'opacity-50'].join(' ')}>
                <label className="block min-w-0">
                    <span className="text-sm font-semibold text-neutral-900">Destination URL</span>

                    <span className="mt-1 block text-xs leading-5 text-neutral-500">
                        Storefront route opened when the action is clicked.
                    </span>

                    <input
                        type="text"
                        value={value.url}
                        disabled={disabled || !value.enabled}
                        maxLength={2048}
                        onChange={(event) => update('url', event.target.value)}
                        placeholder={placeholder}
                        className={`${inputClass} mt-2`}
                    />
                </label>
            </div>
        </section>
    )
}

function ActionCountBadge({ count }: { count: number }) {
    return (
        <span className="rounded-full bg-indigo-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-indigo-600">
            {count}/2 On
        </span>
    )
}

function StatusBadge({ enabled }: { enabled: boolean }) {
    return (
        <span
            className={[
                'rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide',
                enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-200 text-neutral-500',
            ].join(' ')}
        >
            {enabled ? 'On' : 'Off'}
        </span>
    )
}

const inputClass =
    'min-h-11 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none transition placeholder:text-neutral-400 focus:border-neutral-950 focus:ring-1 focus:ring-neutral-950 disabled:cursor-not-allowed disabled:bg-neutral-100'
