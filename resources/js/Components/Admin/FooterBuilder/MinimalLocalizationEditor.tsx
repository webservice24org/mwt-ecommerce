import { ArrowDown, ArrowUp, Coins, Languages, Plus, Trash2 } from 'lucide-react'
import type { ReactNode } from 'react'

import type { FooterLocalizationConfig, FooterLocalizationOption } from '@/types/footer-builder'

const MAX_OPTIONS = 12

interface Props {
    value: FooterLocalizationConfig
    disabled?: boolean

    onChange: (value: FooterLocalizationConfig) => void
}

export default function MinimalLocalizationEditor({ value, disabled = false, onChange }: Props) {
    return (
        <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white">
            <div className="flex flex-col gap-4 border-b border-neutral-200 bg-neutral-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex min-w-0 items-start gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-neutral-700 ring-1 ring-neutral-200">
                        <Languages className="h-5 w-5" strokeWidth={1.8} />
                    </div>

                    <div className="min-w-0">
                        <h3 className="text-sm font-semibold text-neutral-950">Localization</h3>

                        <p className="mt-1 max-w-2xl text-xs leading-5 text-neutral-500">
                            Configure the language and currency options shown by the Minimal footer.
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
                        aria-label="Enable footer localization"
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

            <div className="space-y-6 p-5">
                {!value.enabled && (
                    <div className="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-xs leading-5 text-neutral-500">
                        Localization controls are disabled. Existing language and currency options
                        are preserved.
                    </div>
                )}

                <LocalizationOptionEditor
                    title="Languages"
                    description="Language choices displayed in the footer."
                    icon={Languages}
                    items={value.languages}
                    disabled={disabled}
                    addLabel="Add language"
                    codePlaceholder="en-US"
                    labelPlaceholder="English (US)"
                    onChange={(languages) =>
                        onChange({
                            ...value,
                            languages,
                        })
                    }
                />

                <LocalizationOptionEditor
                    title="Currencies"
                    description="Currency choices displayed in the footer."
                    icon={Coins}
                    items={value.currencies}
                    disabled={disabled}
                    addLabel="Add currency"
                    codePlaceholder="USD"
                    labelPlaceholder="USD ($)"
                    onChange={(currencies) =>
                        onChange({
                            ...value,
                            currencies,
                        })
                    }
                />

                <div className="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p className="text-xs leading-5 text-amber-800">
                        These controls define the footer&apos;s displayed language and currency
                        choices. They do not create application-wide translation or
                        currency-conversion logic.
                    </p>
                </div>
            </div>
        </section>
    )
}

function LocalizationOptionEditor({
    title,
    description,
    icon: Icon,
    items,
    disabled,
    addLabel,
    codePlaceholder,
    labelPlaceholder,
    onChange,
}: {
    title: string
    description: string
    icon: typeof Languages
    items: FooterLocalizationOption[]
    disabled: boolean
    addLabel: string
    codePlaceholder: string
    labelPlaceholder: string

    onChange: (items: FooterLocalizationOption[]) => void
}) {
    const addItem = () => {
        if (disabled || items.length >= MAX_OPTIONS) {
            return
        }

        onChange([
            ...items,
            {
                code: '',
                label: '',
            },
        ])
    }

    const updateItem = (index: number, option: FooterLocalizationOption) => {
        onChange(items.map((current, currentIndex) => (currentIndex === index ? option : current)))
    }

    const removeItem = (index: number) => {
        if (disabled) {
            return
        }

        onChange(items.filter((_item, currentIndex) => currentIndex !== index))
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
        <div className="overflow-hidden rounded-xl border border-neutral-200">
            <div className="flex flex-col gap-4 border-b border-neutral-200 bg-neutral-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex min-w-0 items-start gap-3">
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-neutral-600 ring-1 ring-neutral-200">
                        <Icon className="h-4 w-4" strokeWidth={1.8} />
                    </div>

                    <div className="min-w-0">
                        <h4 className="text-sm font-semibold text-neutral-900">{title}</h4>

                        <p className="mt-1 text-xs leading-5 text-neutral-500">{description}</p>
                    </div>
                </div>

                <button
                    type="button"
                    onClick={addItem}
                    disabled={disabled || items.length >= MAX_OPTIONS}
                    className="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-3 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <Plus className="h-4 w-4" />

                    {addLabel}
                </button>
            </div>

            <div className="p-4">
                {items.length === 0 ? (
                    <div className="rounded-lg border border-dashed border-neutral-300 bg-neutral-50 px-4 py-6 text-center">
                        <p className="text-sm font-semibold text-neutral-800">
                            No {title.toLowerCase()}
                        </p>

                        <p className="mt-1 text-xs text-neutral-500">
                            {valueRequiredMessage(title)}
                        </p>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {items.map((item, index) => (
                            <LocalizationOptionRow
                                key={index}
                                item={item}
                                index={index}
                                total={items.length}
                                disabled={disabled}
                                codePlaceholder={codePlaceholder}
                                labelPlaceholder={labelPlaceholder}
                                onChange={(option) => updateItem(index, option)}
                                onRemove={() => removeItem(index)}
                                onMove={(direction) => moveItem(index, direction)}
                            />
                        ))}
                    </div>
                )}

                <p className="mt-4 text-right text-xs text-neutral-400">
                    {items.length}/{MAX_OPTIONS}
                </p>
            </div>
        </div>
    )
}

function LocalizationOptionRow({
    item,
    index,
    total,
    disabled,
    codePlaceholder,
    labelPlaceholder,
    onChange,
    onRemove,
    onMove,
}: {
    item: FooterLocalizationOption
    index: number
    total: number
    disabled: boolean
    codePlaceholder: string
    labelPlaceholder: string

    onChange: (item: FooterLocalizationOption) => void

    onRemove: () => void

    onMove: (direction: 'up' | 'down') => void
}) {
    return (
        <div className="grid min-w-0 grid-cols-1 gap-3 rounded-lg border border-neutral-200 bg-neutral-50 p-3 lg:grid-cols-[minmax(0,0.45fr)_minmax(0,1fr)_auto] lg:items-end">
            <div className="min-w-0">
                <label
                    htmlFor={`localization-${codePlaceholder}-${index}-code`}
                    className="block text-xs font-medium text-neutral-600"
                >
                    Code
                </label>

                <input
                    id={`localization-${codePlaceholder}-${index}-code`}
                    type="text"
                    required
                    maxLength={16}
                    pattern="[A-Za-z0-9_-]+"
                    value={item.code}
                    disabled={disabled}
                    onChange={(event) =>
                        onChange({
                            ...item,

                            code: event.target.value,
                        })
                    }
                    placeholder={codePlaceholder}
                    className="mt-1.5 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 font-mono text-sm text-neutral-900 outline-none focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100"
                />

                <p className="mt-1 text-[11px] text-neutral-400">
                    Letters, numbers, hyphen, underscore
                </p>
            </div>

            <div className="min-w-0">
                <label
                    htmlFor={`localization-${codePlaceholder}-${index}-label`}
                    className="block text-xs font-medium text-neutral-600"
                >
                    Label
                </label>

                <input
                    id={`localization-${codePlaceholder}-${index}-label`}
                    type="text"
                    required
                    maxLength={120}
                    value={item.label}
                    disabled={disabled}
                    onChange={(event) =>
                        onChange({
                            ...item,

                            label: event.target.value,
                        })
                    }
                    placeholder={labelPlaceholder}
                    className="mt-1.5 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100"
                />
            </div>

            <div className="flex items-center gap-1 lg:pb-0">
                <IconButton
                    label={`Move option ${index + 1} up`}
                    disabled={disabled || index === 0}
                    onClick={() => onMove('up')}
                >
                    <ArrowUp className="h-4 w-4" />
                </IconButton>

                <IconButton
                    label={`Move option ${index + 1} down`}
                    disabled={disabled || index === total - 1}
                    onClick={() => onMove('down')}
                >
                    <ArrowDown className="h-4 w-4" />
                </IconButton>

                <IconButton
                    label={`Delete option ${index + 1}`}
                    disabled={disabled}
                    destructive
                    onClick={onRemove}
                >
                    <Trash2 className="h-4 w-4" />
                </IconButton>
            </div>
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
    const next = [...items]

    const [moved] = next.splice(from, 1)

    if (moved === undefined) {
        return items
    }

    next.splice(to, 0, moved)

    return next
}

function valueRequiredMessage(title: string): string {
    return `${title} are required while localization is enabled.`
}
