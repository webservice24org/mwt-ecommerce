import { Plus, Search, Trash2 } from 'lucide-react'

import HeaderEditorAccordion from '@/Components/Admin/HeaderBuilder/HeaderEditorAccordion'
import type { HeaderSearchConfig } from '@/types/header-builder'

interface Props {
    value: HeaderSearchConfig
    disabled?: boolean
    onChange: (value: HeaderSearchConfig) => void
}

export default function SearchSettingsEditor({ value, disabled = false, onChange }: Props) {
    const update = <K extends keyof HeaderSearchConfig>(
        key: K,
        nextValue: HeaderSearchConfig[K],
    ) => {
        if (disabled) {
            return
        }

        onChange({
            ...value,
            [key]: nextValue,
        })
    }

    const updateTrendingSearch = (index: number, nextValue: string) => {
        if (disabled) {
            return
        }

        update(
            'trending_searches',
            value.trending_searches.map((item, itemIndex) =>
                itemIndex === index ? nextValue : item,
            ),
        )
    }

    const addTrendingSearch = () => {
        if (disabled || value.trending_searches.length >= 12) {
            return
        }

        update('trending_searches', [...value.trending_searches, ''])
    }

    const removeTrendingSearch = (index: number) => {
        if (disabled) {
            return
        }

        update(
            'trending_searches',
            value.trending_searches.filter((_, itemIndex) => itemIndex !== index),
        )
    }

    return (
        <HeaderEditorAccordion
            title="Search"
            description="Configure the storefront product search field and its search suggestion content."
            icon={<Search className="h-5 w-5" strokeWidth={1.9} aria-hidden="true" />}
            badge={<StatusBadge enabled={value.enabled} />}
        >
            <div className="space-y-6 p-5 sm:p-6">
                <SettingToggle
                    title="Product search"
                    description="Show the main product search field in the header."
                    checked={value.enabled}
                    disabled={disabled}
                    onChange={(checked) => update('enabled', checked)}
                />

                <fieldset disabled={disabled} className="space-y-6 disabled:opacity-60">
                    <Field
                        label="Search placeholder"
                        description="Placeholder displayed inside the desktop search field."
                    >
                        <input
                            type="text"
                            value={value.placeholder}
                            maxLength={160}
                            onChange={(event) => update('placeholder', event.target.value)}
                            placeholder="Search 20,000+ fashion products..."
                            className={inputClass}
                        />
                    </Field>

                    <div className="border-t border-neutral-100 pt-6">
                        <SettingToggle
                            title="Search suggestions"
                            description="Show suggestion content when the customer interacts with the search field."
                            checked={value.suggestions_enabled}
                            disabled={disabled}
                            onChange={(checked) => update('suggestions_enabled', checked)}
                        />
                    </div>

                    <div
                        className={[
                            'space-y-6',
                            value.suggestions_enabled ? '' : 'opacity-50',
                        ].join(' ')}
                    >
                        <Field
                            label="Suggestion heading"
                            description="Heading displayed above trending search terms."
                        >
                            <input
                                type="text"
                                value={value.suggestion_heading}
                                maxLength={160}
                                disabled={disabled || !value.suggestions_enabled}
                                onChange={(event) =>
                                    update('suggestion_heading', event.target.value)
                                }
                                placeholder="Trending Searches"
                                className={inputClass}
                            />
                        </Field>

                        <div>
                            <div className="flex min-w-0 flex-wrap items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <h3 className="text-sm font-semibold text-neutral-950">
                                        Trending searches
                                    </h3>

                                    <p className="mt-1 text-sm leading-6 text-neutral-500">
                                        Suggested search terms displayed in the search dropdown.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    disabled={
                                        disabled ||
                                        !value.suggestions_enabled ||
                                        value.trending_searches.length >= 12
                                    }
                                    onClick={addTrendingSearch}
                                    className="inline-flex min-h-10 shrink-0 items-center gap-2 rounded-lg border border-neutral-200 bg-white px-3 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <Plus className="h-4 w-4" aria-hidden="true" />
                                    Add term
                                </button>
                            </div>

                            <div className="mt-4 space-y-3">
                                {value.trending_searches.map((item, index) => (
                                    <div
                                        key={index}
                                        className="flex min-w-0 gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-3"
                                    >
                                        <input
                                            type="text"
                                            value={item}
                                            maxLength={160}
                                            disabled={disabled || !value.suggestions_enabled}
                                            onChange={(event) =>
                                                updateTrendingSearch(index, event.target.value)
                                            }
                                            placeholder="Oversized Hoodie"
                                            className={inputClass}
                                        />

                                        <button
                                            type="button"
                                            disabled={disabled || !value.suggestions_enabled}
                                            onClick={() => removeTrendingSearch(index)}
                                            aria-label={`Remove trending search ${item || index + 1}`}
                                            className="inline-flex min-h-11 shrink-0 items-center justify-center rounded-lg border border-red-200 bg-white px-3 text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <Trash2 className="h-4 w-4" aria-hidden="true" />
                                        </button>
                                    </div>
                                ))}

                                {value.trending_searches.length === 0 && (
                                    <div className="rounded-xl border border-dashed border-neutral-200 p-5 text-center text-sm text-neutral-500">
                                        No trending search terms.
                                    </div>
                                )}
                            </div>

                            <p className="mt-3 text-xs text-neutral-400">
                                Maximum 12 trending search terms.
                            </p>
                        </div>
                    </div>
                </fieldset>
            </div>
        </HeaderEditorAccordion>
    )
}

function SettingToggle({
    title,
    description,
    checked,
    disabled,
    onChange,
}: {
    title: string
    description: string
    checked: boolean
    disabled: boolean
    onChange: (checked: boolean) => void
}) {
    return (
        <div className="flex min-w-0 flex-wrap items-center justify-between gap-4 rounded-xl border border-neutral-200 bg-neutral-50 p-4">
            <div className="min-w-0">
                <p className="text-sm font-semibold text-neutral-900">{title}</p>

                <p className="mt-1 text-xs leading-5 text-neutral-500">{description}</p>
            </div>

            <label className="flex shrink-0 items-center gap-3">
                <span className="text-xs font-semibold text-neutral-600">
                    {checked ? 'Enabled' : 'Disabled'}
                </span>

                <input
                    type="checkbox"
                    checked={checked}
                    disabled={disabled}
                    onChange={(event) => onChange(event.target.checked)}
                    className="h-5 w-5 rounded border-neutral-300 text-neutral-950 focus:ring-neutral-950 disabled:cursor-not-allowed disabled:opacity-50"
                />
            </label>
        </div>
    )
}

function StatusBadge({ enabled }: { enabled: boolean }) {
    return (
        <span
            className={[
                'rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide',
                enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-500',
            ].join(' ')}
        >
            {enabled ? 'On' : 'Off'}
        </span>
    )
}

function Field({
    label,
    description,
    children,
}: {
    label: string
    description: string
    children: React.ReactNode
}) {
    return (
        <label className="block min-w-0">
            <span className="text-sm font-semibold text-neutral-900">{label}</span>

            <span className="mt-1 block text-xs leading-5 text-neutral-500">{description}</span>

            <span className="mt-2 block">{children}</span>
        </label>
    )
}

const inputClass =
    'min-h-11 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none transition placeholder:text-neutral-400 focus:border-neutral-950 focus:ring-1 focus:ring-neutral-950 disabled:cursor-not-allowed disabled:bg-neutral-100'
