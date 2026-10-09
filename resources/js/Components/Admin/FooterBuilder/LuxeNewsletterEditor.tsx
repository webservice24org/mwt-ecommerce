import {
    ArrowDown,
    ArrowUp,
    Award,
    BadgeCheck,
    CreditCard,
    Headphones,
    Lock,
    Mail,
    Package,
    Plus,
    RefreshCcw,
    ShieldCheck,
    Trash2,
    Truck,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import type { ReactNode } from 'react'

import { footerValuePropIcons, isFooterValuePropIcon } from '@/types/footer-builder'
import type {
    FooterNewsletterConfig,
    FooterValueProp,
    FooterValuePropIcon,
} from '@/types/footer-builder'

const MAX_VALUE_PROPS = 6
const MAX_PAYMENT_METHODS = 8

const valuePropIcons: Record<FooterValuePropIcon, LucideIcon> = {
    package: Package,
    truck: Truck,
    'shield-check': ShieldCheck,
    'refresh-ccw': RefreshCcw,
    headphones: Headphones,
    'credit-card': CreditCard,
    lock: Lock,
    'badge-check': BadgeCheck,
    award: Award,
}

interface Props {
    valueProps: FooterValueProp[]
    newsletter: FooterNewsletterConfig
    paymentMethods: string[]
    disabled?: boolean

    onValuePropsChange: (value: FooterValueProp[]) => void

    onNewsletterChange: (value: FooterNewsletterConfig) => void

    onPaymentMethodsChange: (value: string[]) => void
}

export default function LuxeNewsletterEditor({
    valueProps,
    newsletter,
    paymentMethods,
    disabled = false,
    onValuePropsChange,
    onNewsletterChange,
    onPaymentMethodsChange,
}: Props) {
    return (
        <div className="min-w-0 space-y-6">
            <ValuePropsEditor
                items={valueProps}
                disabled={disabled}
                onChange={onValuePropsChange}
            />

            <NewsletterEditor
                value={newsletter}
                disabled={disabled}
                onChange={onNewsletterChange}
            />

            <PaymentMethodsEditor
                items={paymentMethods}
                disabled={disabled}
                onChange={onPaymentMethodsChange}
            />
        </div>
    )
}

function ValuePropsEditor({
    items,
    disabled,
    onChange,
}: {
    items: FooterValueProp[]
    disabled: boolean
    onChange: (items: FooterValueProp[]) => void
}) {
    const addItem = () => {
        if (disabled || items.length >= MAX_VALUE_PROPS) {
            return
        }

        onChange([
            ...items,
            {
                icon: 'package',

                title: '',

                description: '',
            },
        ])
    }

    const updateItem = (index: number, value: FooterValueProp) => {
        onChange(items.map((item, itemIndex) => (itemIndex === index ? value : item)))
    }

    const removeItem = (index: number) => {
        if (disabled) {
            return
        }

        onChange(items.filter((_item, itemIndex) => itemIndex !== index))
    }

    const moveItem = (index: number, direction: 'up' | 'down') => {
        if (disabled) {
            return
        }

        const target = direction === 'up' ? index - 1 : index + 1

        if (target < 0 || target >= items.length) {
            return
        }

        onChange(reorder(items, index, target))
    }

    return (
        <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white">
            <div className="flex flex-col gap-4 border-b border-neutral-200 bg-neutral-50 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <div>
                    <h3 className="text-sm font-semibold text-neutral-950">Value propositions</h3>

                    <p className="mt-1 text-xs leading-5 text-neutral-500">
                        Shipping, payment, returns, trust, and support highlights shown above the
                        Luxe footer.
                    </p>
                </div>

                <button
                    type="button"
                    disabled={disabled || items.length >= MAX_VALUE_PROPS}
                    onClick={addItem}
                    className="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-3 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <Plus className="h-4 w-4" />
                    Add value prop
                </button>
            </div>

            <div className="p-4 sm:p-5">
                {items.length === 0 ? (
                    <EmptyState
                        title="No value propositions"
                        description="The Luxe footer can be saved without a value-proposition strip."
                    />
                ) : (
                    <div className="space-y-3">
                        {items.map((item, index) => (
                            <ValuePropRow
                                key={index}
                                item={item}
                                index={index}
                                total={items.length}
                                disabled={disabled}
                                onChange={(value) => updateItem(index, value)}
                                onRemove={() => removeItem(index)}
                                onMove={(direction) => moveItem(index, direction)}
                            />
                        ))}
                    </div>
                )}

                <p className="mt-4 text-right text-xs text-neutral-400">
                    {items.length}/{MAX_VALUE_PROPS} value propositions
                </p>
            </div>
        </section>
    )
}

function ValuePropRow({
    item,
    index,
    total,
    disabled,
    onChange,
    onRemove,
    onMove,
}: {
    item: FooterValueProp
    index: number
    total: number
    disabled: boolean

    onChange: (value: FooterValueProp) => void

    onRemove: () => void

    onMove: (direction: 'up' | 'down') => void
}) {
    const Icon = valuePropIcons[item.icon]

    return (
        <div className="min-w-0 rounded-xl border border-neutral-200 bg-neutral-50 p-4">
            <div className="flex min-w-0 flex-col gap-4 xl:flex-row">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-neutral-700 ring-1 ring-neutral-200">
                    <Icon className="h-5 w-5" strokeWidth={1.8} />
                </div>

                <div className="grid min-w-0 flex-1 grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label
                            htmlFor={`luxe-value-prop-${index}-icon`}
                            className="block text-xs font-medium text-neutral-600"
                        >
                            Icon
                        </label>

                        <select
                            id={`luxe-value-prop-${index}-icon`}
                            value={item.icon}
                            disabled={disabled}
                            onChange={(event) => {
                                const value = event.target.value

                                if (!isFooterValuePropIcon(value)) {
                                    return
                                }

                                onChange({
                                    ...item,

                                    icon: value,
                                })
                            }}
                            className="mt-1.5 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100"
                        >
                            {footerValuePropIcons.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label
                            htmlFor={`luxe-value-prop-${index}-title`}
                            className="block text-xs font-medium text-neutral-600"
                        >
                            Title
                        </label>

                        <input
                            id={`luxe-value-prop-${index}-title`}
                            type="text"
                            required
                            maxLength={120}
                            value={item.title}
                            disabled={disabled}
                            onChange={(event) =>
                                onChange({
                                    ...item,

                                    title: event.target.value,
                                })
                            }
                            placeholder="Free Shipping"
                            className="mt-1.5 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100"
                        />
                    </div>

                    <div>
                        <label
                            htmlFor={`luxe-value-prop-${index}-description`}
                            className="block text-xs font-medium text-neutral-600"
                        >
                            Description
                        </label>

                        <input
                            id={`luxe-value-prop-${index}-description`}
                            type="text"
                            required
                            maxLength={240}
                            value={item.description}
                            disabled={disabled}
                            onChange={(event) =>
                                onChange({
                                    ...item,

                                    description: event.target.value,
                                })
                            }
                            placeholder="On orders over $100 worldwide"
                            className="mt-1.5 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100"
                        />
                    </div>
                </div>

                <div className="flex shrink-0 items-center gap-1">
                    <IconButton
                        label={`Move value proposition ${index + 1} up`}
                        disabled={disabled || index === 0}
                        onClick={() => onMove('up')}
                    >
                        <ArrowUp className="h-4 w-4" />
                    </IconButton>

                    <IconButton
                        label={`Move value proposition ${index + 1} down`}
                        disabled={disabled || index === total - 1}
                        onClick={() => onMove('down')}
                    >
                        <ArrowDown className="h-4 w-4" />
                    </IconButton>

                    <IconButton
                        label={`Delete value proposition ${index + 1}`}
                        disabled={disabled}
                        destructive
                        onClick={onRemove}
                    >
                        <Trash2 className="h-4 w-4" />
                    </IconButton>
                </div>
            </div>
        </div>
    )
}

function NewsletterEditor({
    value,
    disabled,
    onChange,
}: {
    value: FooterNewsletterConfig
    disabled: boolean

    onChange: (value: FooterNewsletterConfig) => void
}) {
    return (
        <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white">
            <div className="flex flex-col gap-4 border-b border-neutral-200 bg-neutral-50 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <div className="flex min-w-0 items-start gap-3">
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-neutral-700 ring-1 ring-neutral-200">
                        <Mail className="h-4 w-4" />
                    </div>

                    <div>
                        <h3 className="text-sm font-semibold text-neutral-950">Newsletter</h3>

                        <p className="mt-1 text-xs leading-5 text-neutral-500">
                            Configure the Luxe newsletter signup content.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    role="switch"
                    aria-checked={value.enabled}
                    aria-label="Enable Luxe newsletter"
                    disabled={disabled}
                    onClick={() =>
                        onChange({
                            ...value,

                            enabled: !value.enabled,
                        })
                    }
                    className={[
                        'relative inline-flex h-6 w-11 shrink-0 rounded-full transition-colors',
                        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2',
                        'disabled:cursor-not-allowed disabled:opacity-50',
                        value.enabled ? 'bg-neutral-900' : 'bg-neutral-300',
                    ].join(' ')}
                >
                    <span
                        className={[
                            'pointer-events-none absolute top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform',
                            value.enabled ? 'translate-x-[22px]' : 'translate-x-0.5',
                        ].join(' ')}
                    />
                </button>
            </div>

            <div className="space-y-5 p-4 sm:p-5">
                {!value.enabled && (
                    <div className="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3 text-xs leading-5 text-neutral-500">
                        Newsletter signup is disabled. Its saved content is preserved.
                    </div>
                )}

                <div>
                    <label
                        htmlFor="luxe-newsletter-description"
                        className="block text-sm font-medium text-neutral-800"
                    >
                        Description
                    </label>

                    <textarea
                        id="luxe-newsletter-description"
                        rows={4}
                        maxLength={800}
                        value={value.description}
                        disabled={disabled}
                        onChange={(event) =>
                            onChange({
                                ...value,

                                description: event.target.value,
                            })
                        }
                        placeholder="Subscribe for exclusive offers and updates."
                        className="mt-2 block w-full min-w-0 resize-y rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm leading-6 text-neutral-900 outline-none focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100"
                    />

                    <p className="mt-1 text-right text-xs text-neutral-400">
                        {value.description.length}
                        /800
                    </p>
                </div>

                <div className="grid min-w-0 grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label
                            htmlFor="luxe-newsletter-placeholder"
                            className="block text-sm font-medium text-neutral-800"
                        >
                            Email placeholder
                        </label>

                        <input
                            id="luxe-newsletter-placeholder"
                            type="text"
                            required={value.enabled}
                            maxLength={160}
                            value={value.placeholder}
                            disabled={disabled}
                            onChange={(event) =>
                                onChange({
                                    ...value,

                                    placeholder: event.target.value,
                                })
                            }
                            placeholder="Enter your email address"
                            className="mt-2 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100"
                        />

                        <p className="mt-1 text-right text-xs text-neutral-400">
                            {value.placeholder.length}
                            /160
                        </p>
                    </div>

                    <div>
                        <label
                            htmlFor="luxe-newsletter-button-label"
                            className="block text-sm font-medium text-neutral-800"
                        >
                            Button label
                        </label>

                        <input
                            id="luxe-newsletter-button-label"
                            type="text"
                            required={value.enabled}
                            maxLength={80}
                            value={value.button_label}
                            disabled={disabled}
                            onChange={(event) =>
                                onChange({
                                    ...value,

                                    button_label: event.target.value,
                                })
                            }
                            placeholder="Subscribe"
                            className="mt-2 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100"
                        />

                        <p className="mt-1 text-right text-xs text-neutral-400">
                            {value.button_label.length}
                            /80
                        </p>
                    </div>
                </div>
            </div>
        </section>
    )
}

function PaymentMethodsEditor({
    items,
    disabled,
    onChange,
}: {
    items: string[]
    disabled: boolean

    onChange: (items: string[]) => void
}) {
    const addItem = () => {
        if (disabled || items.length >= MAX_PAYMENT_METHODS) {
            return
        }

        onChange([...items, ''])
    }

    return (
        <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white">
            <div className="flex flex-col gap-4 border-b border-neutral-200 bg-neutral-50 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <div>
                    <h3 className="text-sm font-semibold text-neutral-950">Payment methods</h3>

                    <p className="mt-1 text-xs leading-5 text-neutral-500">
                        Labels shown beneath the Luxe newsletter signup.
                    </p>
                </div>

                <button
                    type="button"
                    disabled={disabled || items.length >= MAX_PAYMENT_METHODS}
                    onClick={addItem}
                    className="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-3 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <Plus className="h-4 w-4" />
                    Add method
                </button>
            </div>

            <div className="p-4 sm:p-5">
                {items.length === 0 ? (
                    <EmptyState
                        title="No payment methods"
                        description="Payment labels are optional."
                    />
                ) : (
                    <div className="space-y-3">
                        {items.map((item, index) => (
                            <div
                                key={index}
                                className="flex min-w-0 items-center gap-2 rounded-lg border border-neutral-200 bg-neutral-50 p-3"
                            >
                                <CreditCard className="h-4 w-4 shrink-0 text-neutral-400" />

                                <input
                                    type="text"
                                    required
                                    maxLength={40}
                                    aria-label={`Payment method ${index + 1}`}
                                    value={item}
                                    disabled={disabled}
                                    onChange={(event) =>
                                        onChange(
                                            items.map((current, itemIndex) =>
                                                itemIndex === index ? event.target.value : current,
                                            ),
                                        )
                                    }
                                    placeholder="VISA"
                                    className="h-9 min-w-0 flex-1 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100"
                                />

                                <IconButton
                                    label={`Move payment method ${index + 1} up`}
                                    disabled={disabled || index === 0}
                                    onClick={() => onChange(reorder(items, index, index - 1))}
                                >
                                    <ArrowUp className="h-4 w-4" />
                                </IconButton>

                                <IconButton
                                    label={`Move payment method ${index + 1} down`}
                                    disabled={disabled || index === items.length - 1}
                                    onClick={() => onChange(reorder(items, index, index + 1))}
                                >
                                    <ArrowDown className="h-4 w-4" />
                                </IconButton>

                                <IconButton
                                    label={`Delete payment method ${index + 1}`}
                                    disabled={disabled}
                                    destructive
                                    onClick={() =>
                                        onChange(
                                            items.filter(
                                                (_value, itemIndex) => itemIndex !== index,
                                            ),
                                        )
                                    }
                                >
                                    <Trash2 className="h-4 w-4" />
                                </IconButton>
                            </div>
                        ))}
                    </div>
                )}

                <p className="mt-4 text-right text-xs text-neutral-400">
                    {items.length}/{MAX_PAYMENT_METHODS} payment methods
                </p>
            </div>
        </section>
    )
}

function EmptyState({ title, description }: { title: string; description: string }) {
    return (
        <div className="rounded-lg border border-dashed border-neutral-300 bg-neutral-50 px-4 py-7 text-center">
            <p className="text-sm font-semibold text-neutral-800">{title}</p>

            <p className="mt-1 text-xs leading-5 text-neutral-500">{description}</p>
        </div>
    )
}

function IconButton({
    label,
    disabled,
    destructive = false,
    onClick,
    children,
}: {
    label: string
    disabled: boolean
    destructive?: boolean
    onClick: () => void
    children: ReactNode
}) {
    return (
        <button
            type="button"
            aria-label={label}
            title={label}
            disabled={disabled}
            onClick={onClick}
            className={[
                'inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border transition',
                'disabled:cursor-not-allowed disabled:opacity-35',
                destructive
                    ? 'border-red-200 bg-white text-red-600 hover:bg-red-50'
                    : 'border-neutral-300 bg-white text-neutral-600 hover:bg-neutral-100',
            ].join(' ')}
        >
            {children}
        </button>
    )
}

function reorder<T>(items: T[], from: number, to: number): T[] {
    if (to < 0 || to >= items.length) {
        return items
    }

    const result = [...items]

    const [moved] = result.splice(from, 1)

    if (moved === undefined) {
        return items
    }

    result.splice(to, 0, moved)

    return result
}
