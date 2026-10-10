import { Columns3, Plus, Trash2 } from 'lucide-react'

import HeaderEditorAccordion from '@/Components/Admin/HeaderBuilder/HeaderEditorAccordion'
import type { HeaderLink, HeaderMegaMenuConfig, HeaderMegaMenuGroup } from '@/types/header-builder'

interface Props {
    value: HeaderMegaMenuConfig
    disabled?: boolean
    onChange: (value: HeaderMegaMenuConfig) => void
}

export default function MegaMenuEditor({ value, disabled = false, onChange }: Props) {
    const update = <K extends keyof HeaderMegaMenuConfig>(
        key: K,
        nextValue: HeaderMegaMenuConfig[K],
    ) => {
        if (disabled) {
            return
        }

        onChange({
            ...value,
            [key]: nextValue,
        })
    }

    const updateGroup = (index: number, patch: Partial<HeaderMegaMenuGroup>) => {
        if (disabled) {
            return
        }

        update(
            'groups',
            value.groups.map((group, groupIndex) =>
                groupIndex === index
                    ? {
                          ...group,
                          ...patch,
                      }
                    : group,
            ),
        )
    }

    const updateGroupLink = (groupIndex: number, linkIndex: number, patch: Partial<HeaderLink>) => {
        if (disabled) {
            return
        }

        update(
            'groups',
            value.groups.map((group, currentGroupIndex) => {
                if (currentGroupIndex !== groupIndex) {
                    return group
                }

                return {
                    ...group,

                    links: group.links.map((link, currentLinkIndex) =>
                        currentLinkIndex === linkIndex
                            ? {
                                  ...link,
                                  ...patch,
                              }
                            : link,
                    ),
                }
            }),
        )
    }

    const addGroup = () => {
        if (disabled || value.groups.length >= 6) {
            return
        }

        update('groups', [
            ...value.groups,
            {
                heading: '',
                links: [],
            },
        ])
    }

    const removeGroup = (index: number) => {
        if (disabled) {
            return
        }

        update(
            'groups',
            value.groups.filter((_, groupIndex) => groupIndex !== index),
        )
    }

    const addGroupLink = (groupIndex: number) => {
        if (disabled) {
            return
        }

        const group = value.groups[groupIndex]

        if (group === undefined || group.links.length >= 12) {
            return
        }

        updateGroup(groupIndex, {
            links: [
                ...group.links,
                {
                    label: '',
                    url: '#',
                },
            ],
        })
    }

    const removeGroupLink = (groupIndex: number, linkIndex: number) => {
        if (disabled) {
            return
        }

        const group = value.groups[groupIndex]

        if (group === undefined) {
            return
        }

        updateGroup(groupIndex, {
            links: group.links.filter((_, currentLinkIndex) => currentLinkIndex !== linkIndex),
        })
    }

    const updatePromotion = <K extends keyof HeaderMegaMenuConfig['promotion']>(
        key: K,
        nextValue: HeaderMegaMenuConfig['promotion'][K],
    ) => {
        if (disabled) {
            return
        }

        update('promotion', {
            ...value.promotion,

            [key]: nextValue,
        })
    }

    return (
        <HeaderEditorAccordion
            title="Mega Menu"
            description="Configure grouped category links and the promotional panel displayed from the Categories trigger."
            icon={<Columns3 className="h-5 w-5" strokeWidth={1.9} aria-hidden="true" />}
            badge={<StatusBadge enabled={value.enabled} />}
        >
            <div className="space-y-6 p-5 sm:p-6">
                <SettingToggle
                    title="Mega menu"
                    description="Enable the expandable mega-menu content for the category navigation trigger."
                    checked={value.enabled}
                    disabled={disabled}
                    onChange={(checked) => update('enabled', checked)}
                />

                <fieldset disabled={disabled} className="space-y-6 disabled:opacity-60">
                    <div className="border-t border-neutral-100 pt-6">
                        <div className="flex min-w-0 flex-wrap items-start justify-between gap-3">
                            <div className="min-w-0">
                                <h3 className="text-sm font-semibold text-neutral-950">
                                    Menu groups
                                </h3>

                                <p className="mt-1 text-sm leading-6 text-neutral-500">
                                    Create category columns such as Women's Apparel and Men's
                                    Fashion.
                                </p>
                            </div>

                            <button
                                type="button"
                                disabled={disabled || value.groups.length >= 6}
                                onClick={addGroup}
                                className="inline-flex min-h-10 shrink-0 items-center gap-2 rounded-lg border border-neutral-200 bg-white px-3 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Plus className="h-4 w-4" aria-hidden="true" />
                                Add group
                            </button>
                        </div>

                        <div className="mt-4 space-y-5">
                            {value.groups.map((group, groupIndex) => (
                                <div
                                    key={groupIndex}
                                    className="min-w-0 rounded-2xl border border-neutral-200 bg-neutral-50 p-4 sm:p-5"
                                >
                                    <div className="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-end">
                                        <div className="min-w-0 flex-1">
                                            <FieldCompact label="Group heading">
                                                <input
                                                    type="text"
                                                    value={group.heading}
                                                    maxLength={160}
                                                    onChange={(event) =>
                                                        updateGroup(groupIndex, {
                                                            heading: event.target.value,
                                                        })
                                                    }
                                                    placeholder="Women's Apparel"
                                                    className={inputClass}
                                                />
                                            </FieldCompact>
                                        </div>

                                        <button
                                            type="button"
                                            disabled={disabled}
                                            onClick={() => removeGroup(groupIndex)}
                                            className="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-lg border border-red-200 bg-white px-3 text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <Trash2 className="h-4 w-4" aria-hidden="true" />
                                            Remove group
                                        </button>
                                    </div>

                                    <div className="mt-5 border-t border-neutral-200 pt-5">
                                        <div className="flex min-w-0 flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <p className="text-sm font-semibold text-neutral-900">
                                                    Group links
                                                </p>

                                                <p className="mt-1 text-xs leading-5 text-neutral-500">
                                                    Maximum 12 links per group.
                                                </p>
                                            </div>

                                            <button
                                                type="button"
                                                disabled={disabled || group.links.length >= 12}
                                                onClick={() => addGroupLink(groupIndex)}
                                                className="inline-flex min-h-10 shrink-0 items-center gap-2 rounded-lg border border-neutral-200 bg-white px-3 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                <Plus className="h-4 w-4" aria-hidden="true" />
                                                Add link
                                            </button>
                                        </div>

                                        <div className="mt-4 space-y-3">
                                            {group.links.map((link, linkIndex) => (
                                                <div
                                                    key={linkIndex}
                                                    className="grid min-w-0 grid-cols-1 gap-3 rounded-xl border border-neutral-200 bg-white p-3 md:grid-cols-[1fr_1fr_auto]"
                                                >
                                                    <input
                                                        type="text"
                                                        value={link.label}
                                                        maxLength={160}
                                                        onChange={(event) =>
                                                            updateGroupLink(groupIndex, linkIndex, {
                                                                label: event.target.value,
                                                            })
                                                        }
                                                        placeholder="Dresses & Jumpsuits"
                                                        className={inputClass}
                                                    />

                                                    <input
                                                        type="text"
                                                        value={link.url}
                                                        maxLength={2048}
                                                        onChange={(event) =>
                                                            updateGroupLink(groupIndex, linkIndex, {
                                                                url: event.target.value,
                                                            })
                                                        }
                                                        placeholder="#"
                                                        className={inputClass}
                                                    />

                                                    <button
                                                        type="button"
                                                        disabled={disabled}
                                                        onClick={() =>
                                                            removeGroupLink(groupIndex, linkIndex)
                                                        }
                                                        aria-label={`Remove ${link.label || 'mega-menu'} link`}
                                                        className="inline-flex min-h-11 items-center justify-center rounded-lg border border-red-200 bg-white px-3 text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                                                    >
                                                        <Trash2
                                                            className="h-4 w-4"
                                                            aria-hidden="true"
                                                        />
                                                    </button>
                                                </div>
                                            ))}

                                            {group.links.length === 0 && (
                                                <div className="rounded-xl border border-dashed border-neutral-200 bg-white p-4 text-center text-sm text-neutral-500">
                                                    No links in this group.
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}

                            {value.groups.length === 0 && (
                                <div className="rounded-xl border border-dashed border-neutral-200 p-5 text-center text-sm text-neutral-500">
                                    No mega-menu groups.
                                </div>
                            )}
                        </div>

                        <p className="mt-3 text-xs text-neutral-400">Maximum 6 groups.</p>
                    </div>

                    <div className="border-t border-neutral-100 pt-6">
                        <h3 className="text-sm font-semibold text-neutral-950">
                            Promotional panel
                        </h3>

                        <p className="mt-1 text-sm leading-6 text-neutral-500">
                            Configure the promotional card displayed beside the mega-menu groups.
                        </p>

                        <div className="mt-4">
                            <SettingToggle
                                title="Promotion"
                                description="Show the promotional panel inside the mega menu."
                                checked={value.promotion.enabled}
                                disabled={disabled}
                                onChange={(checked) => updatePromotion('enabled', checked)}
                            />
                        </div>

                        <div
                            className={[
                                'mt-5 grid grid-cols-1 gap-5 md:grid-cols-2',
                                value.promotion.enabled ? '' : 'opacity-50',
                            ].join(' ')}
                        >
                            <Field
                                label="Eyebrow"
                                description="Small label above the promotional title."
                            >
                                <input
                                    type="text"
                                    value={value.promotion.eyebrow ?? ''}
                                    disabled={disabled || !value.promotion.enabled}
                                    maxLength={160}
                                    onChange={(event) =>
                                        updatePromotion('eyebrow', event.target.value)
                                    }
                                    placeholder="Featured Promo"
                                    className={inputClass}
                                />
                            </Field>

                            <Field label="Button label" description="Call-to-action text.">
                                <input
                                    type="text"
                                    value={value.promotion.button_label}
                                    disabled={disabled || !value.promotion.enabled}
                                    maxLength={160}
                                    onChange={(event) =>
                                        updatePromotion('button_label', event.target.value)
                                    }
                                    placeholder="Shop Sale"
                                    className={inputClass}
                                />
                            </Field>

                            <Field label="Promotion title" description="Main promotional message.">
                                <input
                                    type="text"
                                    value={value.promotion.title}
                                    disabled={disabled || !value.promotion.enabled}
                                    maxLength={1000}
                                    onChange={(event) =>
                                        updatePromotion('title', event.target.value)
                                    }
                                    placeholder="Up to 40% Off Footwear"
                                    className={inputClass}
                                />
                            </Field>

                            <Field
                                label="Promotion URL"
                                description="Destination for the promotional action."
                            >
                                <input
                                    type="text"
                                    value={value.promotion.url}
                                    disabled={disabled || !value.promotion.enabled}
                                    maxLength={2048}
                                    onChange={(event) => updatePromotion('url', event.target.value)}
                                    placeholder="#"
                                    className={inputClass}
                                />
                            </Field>
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
