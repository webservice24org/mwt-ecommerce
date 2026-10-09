import {
    ArrowDown,
    ArrowUp,
    BadgeCheck,
    Link2,
    Megaphone,
    Plus,
    ShieldCheck,
    Trash2,
} from 'lucide-react'
import type { ReactNode } from 'react'

import type { FooterPopularLink, FooterPromotionConfig } from '@/types/footer-builder'

const MAX_POPULAR_LINKS = 12
const MAX_CERTIFICATIONS = 8

interface Props {
    promotion: FooterPromotionConfig
    popularLinks: FooterPopularLink[]
    certifications: string[]
    disabled?: boolean

    onPromotionChange: (value: FooterPromotionConfig) => void

    onPopularLinksChange: (value: FooterPopularLink[]) => void

    onCertificationsChange: (value: string[]) => void
}

export default function MarketplaceTrustEditor({
    promotion,
    popularLinks,
    certifications,
    disabled = false,
    onPromotionChange,
    onPopularLinksChange,
    onCertificationsChange,
}: Props) {
    return (
        <div className="min-w-0 space-y-6">
            <PromotionEditor value={promotion} disabled={disabled} onChange={onPromotionChange} />

            <PopularLinksEditor
                items={popularLinks}
                disabled={disabled}
                onChange={onPopularLinksChange}
            />

            <CertificationsEditor
                items={certifications}
                disabled={disabled}
                onChange={onCertificationsChange}
            />
        </div>
    )
}

function PromotionEditor({
    value,
    disabled,
    onChange,
}: {
    value: FooterPromotionConfig
    disabled: boolean

    onChange: (value: FooterPromotionConfig) => void
}) {
    return (
        <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white">
            <div className="flex flex-col gap-4 border-b border-neutral-200 bg-neutral-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex min-w-0 items-start gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-neutral-700 ring-1 ring-neutral-200">
                        <Megaphone className="h-5 w-5" strokeWidth={1.8} />
                    </div>

                    <div className="min-w-0">
                        <h3 className="text-sm font-semibold text-neutral-950">Promotion banner</h3>

                        <p className="mt-1 max-w-2xl text-xs leading-5 text-neutral-500">
                            Configure the marketplace promotional call-to-action shown above the
                            footer content.
                        </p>
                    </div>
                </div>

                <div className="flex shrink-0 items-center gap-3">
                    <span className="text-xs font-medium text-neutral-500">
                        {value.enabled ? 'Enabled' : 'Disabled'}
                    </span>

                    <button
                        type="button"
                        role="switch"
                        aria-checked={value.enabled}
                        aria-label="Enable marketplace promotion"
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
            </div>

            <div className="space-y-5 p-5">
                {!value.enabled && (
                    <div className="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-xs leading-5 text-neutral-500">
                        Promotion is disabled. Existing promotion content is preserved.
                    </div>
                )}

                <div className="grid min-w-0 grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label
                            htmlFor="marketplace-promotion-badge"
                            className="block text-sm font-medium text-neutral-800"
                        >
                            Badge
                        </label>

                        <input
                            id="marketplace-promotion-badge"
                            type="text"
                            maxLength={60}
                            value={value.badge}
                            disabled={disabled}
                            onChange={(event) =>
                                onChange({
                                    ...value,

                                    badge: event.target.value,
                                })
                            }
                            placeholder="Flash Sale"
                            className="mt-2 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100"
                        />

                        <p className="mt-1 text-right text-xs text-neutral-400">
                            {value.badge.length}
                            /60
                        </p>
                    </div>

                    <div>
                        <label
                            htmlFor="marketplace-promotion-code"
                            className="block text-sm font-medium text-neutral-800"
                        >
                            Promotion code
                        </label>

                        <input
                            id="marketplace-promotion-code"
                            type="text"
                            maxLength={60}
                            value={value.code}
                            disabled={disabled}
                            onChange={(event) =>
                                onChange({
                                    ...value,

                                    code: event.target.value,
                                })
                            }
                            placeholder="WELCOME20"
                            className="mt-2 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 font-mono text-sm text-neutral-900 outline-none focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100"
                        />

                        <p className="mt-1 text-right text-xs text-neutral-400">
                            {value.code.length}
                            /60
                        </p>
                    </div>
                </div>

                <div>
                    <label
                        htmlFor="marketplace-promotion-message"
                        className="block text-sm font-medium text-neutral-800"
                    >
                        Promotion message
                    </label>

                    <textarea
                        id="marketplace-promotion-message"
                        rows={4}
                        required={value.enabled}
                        maxLength={300}
                        value={value.message}
                        disabled={disabled}
                        onChange={(event) =>
                            onChange({
                                ...value,

                                message: event.target.value,
                            })
                        }
                        placeholder="Get 20% OFF your first order with code"
                        className="mt-2 block w-full min-w-0 resize-y rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm leading-6 text-neutral-900 outline-none focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100"
                    />

                    <p className="mt-1 text-right text-xs text-neutral-400">
                        {value.message.length}
                        /300
                    </p>
                </div>

                <div className="grid min-w-0 grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label
                            htmlFor="marketplace-promotion-button-label"
                            className="block text-sm font-medium text-neutral-800"
                        >
                            Button label
                        </label>

                        <input
                            id="marketplace-promotion-button-label"
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
                            placeholder="Shop Now"
                            className="mt-2 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100"
                        />

                        <p className="mt-1 text-right text-xs text-neutral-400">
                            {value.button_label.length}
                            /80
                        </p>
                    </div>

                    <div>
                        <label
                            htmlFor="marketplace-promotion-button-url"
                            className="block text-sm font-medium text-neutral-800"
                        >
                            Button URL
                        </label>

                        <input
                            id="marketplace-promotion-button-url"
                            type="text"
                            required={value.enabled}
                            maxLength={2048}
                            value={value.button_url}
                            disabled={disabled}
                            onChange={(event) =>
                                onChange({
                                    ...value,

                                    button_url: event.target.value,
                                })
                            }
                            placeholder="/sale, #deals, or https://example.com"
                            className="mt-2 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100"
                        />

                        <p className="mt-1 text-xs text-neutral-400">
                            Internal path, fragment, or HTTP(S) URL.
                        </p>
                    </div>
                </div>

                <div className="rounded-xl border border-neutral-200 bg-neutral-950 p-4">
                    <p className="text-[10px] font-bold uppercase tracking-[0.14em] text-neutral-500">
                        Promotion preview
                    </p>

                    <div className="mt-3 flex min-w-0 flex-wrap items-center gap-2">
                        {value.badge !== '' && (
                            <span className="rounded-full bg-amber-400 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-neutral-950">
                                {value.badge}
                            </span>
                        )}

                        <span className="break-words text-sm font-medium text-neutral-200">
                            {value.message || 'Promotion message'}

                            {value.code !== '' && (
                                <>
                                    {' '}
                                    <span className="font-mono font-bold text-amber-400">
                                        {value.code}
                                    </span>
                                </>
                            )}
                        </span>

                        {value.button_label !== '' && (
                            <span className="ml-auto shrink-0 rounded-lg bg-white px-3 py-2 text-xs font-bold text-neutral-950">
                                {value.button_label}
                            </span>
                        )}
                    </div>
                </div>
            </div>
        </section>
    )
}

function PopularLinksEditor({
    items,
    disabled,
    onChange,
}: {
    items: FooterPopularLink[]
    disabled: boolean

    onChange: (items: FooterPopularLink[]) => void
}) {
    const addItem = () => {
        if (disabled || items.length >= MAX_POPULAR_LINKS) {
            return
        }

        onChange([
            ...items,
            {
                label: '',
                url: '',
            },
        ])
    }

    return (
        <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white">
            <div className="flex flex-col gap-4 border-b border-neutral-200 bg-neutral-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex min-w-0 items-start gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-neutral-700 ring-1 ring-neutral-200">
                        <Link2 className="h-5 w-5" strokeWidth={1.8} />
                    </div>

                    <div>
                        <h3 className="text-sm font-semibold text-neutral-950">
                            Popular categories
                        </h3>

                        <p className="mt-1 text-xs leading-5 text-neutral-500">
                            Quick marketplace links displayed prominently in the trust footer.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    disabled={disabled || items.length >= MAX_POPULAR_LINKS}
                    onClick={addItem}
                    className="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-3 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <Plus className="h-4 w-4" />
                    Add popular link
                </button>
            </div>

            <div className="p-5">
                {items.length === 0 ? (
                    <EmptyState
                        title="No popular links"
                        description="Popular marketplace shortcuts are optional."
                    />
                ) : (
                    <div className="space-y-3">
                        {items.map((item, index) => (
                            <div
                                key={index}
                                className="grid min-w-0 grid-cols-1 gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-3 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)_auto] lg:items-end"
                            >
                                <div className="min-w-0">
                                    <label
                                        htmlFor={`marketplace-popular-${index}-label`}
                                        className="block text-xs font-medium text-neutral-600"
                                    >
                                        Label
                                    </label>

                                    <input
                                        id={`marketplace-popular-${index}-label`}
                                        type="text"
                                        required
                                        maxLength={120}
                                        value={item.label}
                                        disabled={disabled}
                                        onChange={(event) =>
                                            onChange(
                                                replaceAt(items, index, {
                                                    ...item,

                                                    label: event.target.value,
                                                }),
                                            )
                                        }
                                        placeholder="Sneakers"
                                        className="mt-1.5 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100"
                                    />
                                </div>

                                <div className="min-w-0">
                                    <label
                                        htmlFor={`marketplace-popular-${index}-url`}
                                        className="block text-xs font-medium text-neutral-600"
                                    >
                                        URL
                                    </label>

                                    <input
                                        id={`marketplace-popular-${index}-url`}
                                        type="text"
                                        required
                                        maxLength={2048}
                                        value={item.url}
                                        disabled={disabled}
                                        onChange={(event) =>
                                            onChange(
                                                replaceAt(items, index, {
                                                    ...item,

                                                    url: event.target.value,
                                                }),
                                            )
                                        }
                                        placeholder="/category/sneakers"
                                        className="mt-1.5 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100"
                                    />
                                </div>

                                <div className="flex items-center gap-1">
                                    <IconButton
                                        label={`Move popular link ${index + 1} up`}
                                        disabled={disabled || index === 0}
                                        onClick={() => onChange(reorder(items, index, index - 1))}
                                    >
                                        <ArrowUp className="h-4 w-4" />
                                    </IconButton>

                                    <IconButton
                                        label={`Move popular link ${index + 1} down`}
                                        disabled={disabled || index === items.length - 1}
                                        onClick={() => onChange(reorder(items, index, index + 1))}
                                    >
                                        <ArrowDown className="h-4 w-4" />
                                    </IconButton>

                                    <IconButton
                                        label={`Delete popular link ${index + 1}`}
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
                            </div>
                        ))}
                    </div>
                )}

                <p className="mt-4 text-right text-xs text-neutral-400">
                    {items.length}/{MAX_POPULAR_LINKS} popular links
                </p>
            </div>
        </section>
    )
}

function CertificationsEditor({
    items,
    disabled,
    onChange,
}: {
    items: string[]
    disabled: boolean

    onChange: (items: string[]) => void
}) {
    const addItem = () => {
        if (disabled || items.length >= MAX_CERTIFICATIONS) {
            return
        }

        onChange([...items, ''])
    }

    return (
        <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white">
            <div className="flex flex-col gap-4 border-b border-neutral-200 bg-neutral-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex min-w-0 items-start gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-neutral-700 ring-1 ring-neutral-200">
                        <ShieldCheck className="h-5 w-5" strokeWidth={1.8} />
                    </div>

                    <div>
                        <h3 className="text-sm font-semibold text-neutral-950">Certifications</h3>

                        <p className="mt-1 text-xs leading-5 text-neutral-500">
                            Security, compliance, payment, or trust labels.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    disabled={disabled || items.length >= MAX_CERTIFICATIONS}
                    onClick={addItem}
                    className="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-3 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <Plus className="h-4 w-4" />
                    Add certification
                </button>
            </div>

            <div className="p-5">
                {items.length === 0 ? (
                    <EmptyState
                        title="No certifications"
                        description="Trust and certification labels are optional."
                    />
                ) : (
                    <div className="space-y-3">
                        {items.map((item, index) => (
                            <div
                                key={index}
                                className="flex min-w-0 items-center gap-2 rounded-xl border border-neutral-200 bg-neutral-50 p-3"
                            >
                                <BadgeCheck className="h-4 w-4 shrink-0 text-emerald-600" />

                                <input
                                    type="text"
                                    required
                                    maxLength={160}
                                    aria-label={`Certification ${index + 1}`}
                                    value={item}
                                    disabled={disabled}
                                    onChange={(event) =>
                                        onChange(replaceAt(items, index, event.target.value))
                                    }
                                    placeholder="SSL 256-Bit Encrypted"
                                    className="h-10 min-w-0 flex-1 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100"
                                />

                                <IconButton
                                    label={`Move certification ${index + 1} up`}
                                    disabled={disabled || index === 0}
                                    onClick={() => onChange(reorder(items, index, index - 1))}
                                >
                                    <ArrowUp className="h-4 w-4" />
                                </IconButton>

                                <IconButton
                                    label={`Move certification ${index + 1} down`}
                                    disabled={disabled || index === items.length - 1}
                                    onClick={() => onChange(reorder(items, index, index + 1))}
                                >
                                    <ArrowDown className="h-4 w-4" />
                                </IconButton>

                                <IconButton
                                    label={`Delete certification ${index + 1}`}
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
                    {items.length}/{MAX_CERTIFICATIONS} certifications
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

function replaceAt<T>(items: T[], index: number, value: T): T[] {
    return items.map((item, itemIndex) => (itemIndex === index ? value : item))
}

function reorder<T>(items: T[], from: number, to: number): T[] {
    if (to < 0 || to >= items.length) {
        return items
    }

    const next = [...items]

    const [moved] = next.splice(from, 1)

    if (moved === undefined) {
        return items
    }

    next.splice(to, 0, moved)

    return next
}
