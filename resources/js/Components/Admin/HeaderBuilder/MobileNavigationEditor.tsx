import { Menu, Plus, Search, Trash2 } from 'lucide-react'

import HeaderEditorAccordion from '@/Components/Admin/HeaderBuilder/HeaderEditorAccordion'
import type { HeaderMobileConfig, HeaderMobileLink } from '@/types/header-builder'

interface Props {
    value: HeaderMobileConfig
    disabled?: boolean
    onChange: (value: HeaderMobileConfig) => void
}

export default function MobileNavigationEditor({ value, disabled = false, onChange }: Props) {
    const update = <K extends keyof HeaderMobileConfig>(
        key: K,
        nextValue: HeaderMobileConfig[K],
    ) => {
        if (disabled) {
            return
        }

        onChange({
            ...value,

            [key]: nextValue,
        })
    }

    const updateLink = (index: number, patch: Partial<HeaderMobileLink>) => {
        if (disabled) {
            return
        }

        update(
            'menu_links',
            value.menu_links.map((link, linkIndex) =>
                linkIndex === index
                    ? {
                          ...link,
                          ...patch,
                      }
                    : link,
            ),
        )
    }

    const addLink = () => {
        if (disabled || value.menu_links.length >= 12) {
            return
        }

        update('menu_links', [
            ...value.menu_links,

            {
                label: '',
                url: '#',
                style: 'default',
            },
        ])
    }

    const removeLink = (index: number) => {
        if (disabled) {
            return
        }

        update(
            'menu_links',
            value.menu_links.filter((_, linkIndex) => linkIndex !== index),
        )
    }

    return (
        <HeaderEditorAccordion
            title="Mobile Navigation"
            description="Configure mobile search behavior and the navigation links displayed inside the mobile header menu."
            icon={<Menu className="h-5 w-5" strokeWidth={1.9} aria-hidden="true" />}
            badge={
                <span className="rounded-full bg-indigo-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-indigo-600">
                    Mobile
                </span>
            }
        >
            <div className="space-y-6 p-5 sm:p-6">
                <div className="rounded-2xl border border-neutral-200 bg-neutral-50 p-4 sm:p-5">
                    <div className="flex min-w-0 flex-wrap items-start justify-between gap-4">
                        <div className="flex min-w-0 items-start gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-neutral-200 bg-white text-neutral-700">
                                <Search className="h-5 w-5" strokeWidth={1.9} aria-hidden="true" />
                            </div>

                            <div className="min-w-0">
                                <div className="flex min-w-0 flex-wrap items-center gap-2">
                                    <h3 className="text-sm font-semibold text-neutral-950">
                                        Mobile search
                                    </h3>

                                    <StatusBadge enabled={value.search_enabled} />
                                </div>

                                <p className="mt-1 text-xs leading-5 text-neutral-500">
                                    Show a dedicated search field inside the mobile menu.
                                </p>
                            </div>
                        </div>

                        <label className="flex shrink-0 items-center gap-3">
                            <span className="text-xs font-semibold text-neutral-600">
                                {value.search_enabled ? 'Enabled' : 'Disabled'}
                            </span>

                            <input
                                type="checkbox"
                                checked={value.search_enabled}
                                disabled={disabled}
                                onChange={(event) => update('search_enabled', event.target.checked)}
                                className="h-5 w-5 rounded border-neutral-300 text-neutral-950 focus:ring-neutral-950 disabled:cursor-not-allowed disabled:opacity-50"
                            />
                        </label>
                    </div>

                    <div className={['mt-5', value.search_enabled ? '' : 'opacity-50'].join(' ')}>
                        <label className="block min-w-0">
                            <span className="text-sm font-semibold text-neutral-900">
                                Mobile search placeholder
                            </span>

                            <span className="mt-1 block text-xs leading-5 text-neutral-500">
                                Placeholder shown inside the mobile search input.
                            </span>

                            <input
                                type="text"
                                value={value.search_placeholder}
                                disabled={disabled || !value.search_enabled}
                                maxLength={160}
                                onChange={(event) =>
                                    update('search_placeholder', event.target.value)
                                }
                                placeholder="Search products..."
                                className={`${inputClass} mt-2`}
                            />
                        </label>
                    </div>
                </div>

                <div className="border-t border-neutral-100 pt-6">
                    <div className="flex min-w-0 flex-wrap items-start justify-between gap-3">
                        <div className="min-w-0">
                            <h3 className="text-sm font-semibold text-neutral-950">
                                Mobile menu links
                            </h3>

                            <p className="mt-1 text-sm leading-6 text-neutral-500">
                                Configure the navigation links displayed when customers open the
                                mobile menu.
                            </p>
                        </div>

                        <button
                            type="button"
                            disabled={disabled || value.menu_links.length >= 12}
                            onClick={addLink}
                            className="inline-flex min-h-10 shrink-0 items-center gap-2 rounded-lg border border-neutral-200 bg-white px-3 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Plus className="h-4 w-4" aria-hidden="true" />
                            Add link
                        </button>
                    </div>

                    <div className="mt-4 space-y-4">
                        {value.menu_links.map((link, index) => (
                            <div
                                key={index}
                                className="grid min-w-0 grid-cols-1 gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-4 lg:grid-cols-[1fr_1fr_180px_auto]"
                            >
                                <FieldCompact label="Label">
                                    <input
                                        type="text"
                                        value={link.label}
                                        maxLength={160}
                                        disabled={disabled}
                                        onChange={(event) =>
                                            updateLink(index, {
                                                label: event.target.value,
                                            })
                                        }
                                        placeholder="Categories"
                                        className={inputClass}
                                    />
                                </FieldCompact>

                                <FieldCompact label="URL">
                                    <input
                                        type="text"
                                        value={link.url}
                                        maxLength={2048}
                                        disabled={disabled}
                                        onChange={(event) =>
                                            updateLink(index, {
                                                url: event.target.value,
                                            })
                                        }
                                        placeholder="#"
                                        className={inputClass}
                                    />
                                </FieldCompact>

                                <FieldCompact label="Style">
                                    <select
                                        value={link.style}
                                        disabled={disabled}
                                        onChange={(event) =>
                                            updateLink(index, {
                                                style: event.target
                                                    .value as HeaderMobileLink['style'],
                                            })
                                        }
                                        className={inputClass}
                                    >
                                        <option value="default">Default</option>

                                        <option value="primary">Primary</option>

                                        <option value="highlight">Highlight</option>
                                    </select>
                                </FieldCompact>

                                <div className="flex items-end">
                                    <button
                                        type="button"
                                        disabled={disabled}
                                        onClick={() => removeLink(index)}
                                        aria-label={`Remove ${link.label || 'mobile'} link`}
                                        className="inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-red-200 bg-white px-3 text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 lg:w-auto"
                                    >
                                        <Trash2 className="h-4 w-4" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>
                        ))}

                        {value.menu_links.length === 0 && (
                            <div className="rounded-xl border border-dashed border-neutral-200 p-5 text-center text-sm text-neutral-500">
                                No mobile menu links.
                            </div>
                        )}
                    </div>

                    <p className="mt-3 text-xs text-neutral-400">
                        Maximum 12 mobile navigation links.
                    </p>
                </div>

                <div className="rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <p className="text-sm font-semibold text-blue-950">Mobile interaction</p>

                    <p className="mt-1 text-xs leading-5 text-blue-800">
                        The final hamburger open/close behavior belongs to the interactive preview
                        and storefront renderer. This step only configures the mobile menu content.
                    </p>
                </div>
            </div>
        </HeaderEditorAccordion>
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

function FieldCompact({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <label className="block min-w-0">
            <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.08em] text-neutral-500">
                {label}
            </span>

            {children}
        </label>
    )
}

const inputClass =
    'min-h-11 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none transition placeholder:text-neutral-400 focus:border-neutral-950 focus:ring-1 focus:ring-neutral-950 disabled:cursor-not-allowed disabled:bg-neutral-100'
