import { Megaphone, Plus, Trash2 } from 'lucide-react'

import HeaderEditorAccordion from '@/Components/Admin/HeaderBuilder/HeaderEditorAccordion'
import type { HeaderAnnouncementConfig, HeaderLink } from '@/types/header-builder'

interface Props {
    value: HeaderAnnouncementConfig
    disabled?: boolean
    onChange: (value: HeaderAnnouncementConfig) => void
}

export default function AnnouncementBarEditor({ value, disabled = false, onChange }: Props) {
    const update = <K extends keyof HeaderAnnouncementConfig>(
        key: K,
        nextValue: HeaderAnnouncementConfig[K],
    ) => {
        if (disabled) {
            return
        }

        onChange({
            ...value,
            [key]: nextValue,
        })
    }

    const updateLink = (index: number, patch: Partial<HeaderLink>) => {
        if (disabled) {
            return
        }

        update(
            'links',
            value.links.map((link, linkIndex) =>
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
        if (disabled || value.links.length >= 4) {
            return
        }

        update('links', [
            ...value.links,
            {
                label: '',
                url: '#',
            },
        ])
    }

    const removeLink = (index: number) => {
        if (disabled) {
            return
        }

        update(
            'links',
            value.links.filter((_, linkIndex) => linkIndex !== index),
        )
    }

    return (
        <HeaderEditorAccordion
            title="Announcement Bar"
            description="Configure the promotional strip displayed above the main storefront navigation."
            defaultOpen
            icon={<Megaphone className="h-5 w-5" strokeWidth={1.9} aria-hidden="true" />}
            badge={<StatusBadge enabled={value.enabled} />}
        >
            <div className="space-y-6 p-5 sm:p-6">
                <div className="flex min-w-0 flex-wrap items-center justify-between gap-4 rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                    <div className="min-w-0">
                        <p className="text-sm font-semibold text-neutral-900">
                            Show announcement bar
                        </p>

                        <p className="mt-1 text-xs leading-5 text-neutral-500">
                            Disable this without deleting its saved content.
                        </p>
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

                <fieldset disabled={disabled} className="space-y-6 disabled:opacity-60">
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <Field
                            label="Badge"
                            description="Small highlighted label before the message."
                        >
                            <input
                                type="text"
                                value={value.badge ?? ''}
                                maxLength={160}
                                onChange={(event) => update('badge', event.target.value)}
                                placeholder="New Drop"
                                className={inputClass}
                            />
                        </Field>

                        <Field
                            label="Currency label"
                            description="Desktop currency text on the right."
                        >
                            <input
                                type="text"
                                value={value.currency_label ?? ''}
                                maxLength={160}
                                onChange={(event) => update('currency_label', event.target.value)}
                                placeholder="USD ($)"
                                className={inputClass}
                            />
                        </Field>
                    </div>

                    <Field label="Announcement message" description="Main promotional message.">
                        <input
                            type="text"
                            value={value.message}
                            maxLength={1000}
                            onChange={(event) => update('message', event.target.value)}
                            placeholder="Summer Luxe Collection is live!"
                            className={inputClass}
                        />
                    </Field>

                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <Field
                            label="Promo code"
                            description="Optional emphasized promotional code."
                        >
                            <input
                                type="text"
                                value={value.promo_code ?? ''}
                                maxLength={160}
                                onChange={(event) => update('promo_code', event.target.value)}
                                placeholder="SUMMER26"
                                className={inputClass}
                            />
                        </Field>

                        <Field
                            label="Promo suffix"
                            description="Text displayed after the promotional code."
                        >
                            <input
                                type="text"
                                value={value.promo_suffix ?? ''}
                                maxLength={160}
                                onChange={(event) => update('promo_suffix', event.target.value)}
                                placeholder="for 15% off."
                                className={inputClass}
                            />
                        </Field>
                    </div>

                    <div className="border-t border-neutral-100 pt-6">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 className="text-sm font-semibold text-neutral-950">
                                    Utility links
                                </h3>

                                <p className="mt-1 text-sm leading-6 text-neutral-500">
                                    Desktop links displayed beside the announcement message.
                                </p>
                            </div>

                            <button
                                type="button"
                                disabled={disabled || value.links.length >= 4}
                                onClick={addLink}
                                className="inline-flex min-h-10 items-center gap-2 rounded-lg border border-neutral-200 bg-white px-3 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Plus className="h-4 w-4" aria-hidden="true" />
                                Add link
                            </button>
                        </div>

                        <div className="mt-4 space-y-4">
                            {value.links.map((link, index) => (
                                <div
                                    key={index}
                                    className="grid min-w-0 grid-cols-1 gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-4 md:grid-cols-[1fr_1fr_auto]"
                                >
                                    <input
                                        type="text"
                                        value={link.label}
                                        maxLength={160}
                                        onChange={(event) =>
                                            updateLink(index, {
                                                label: event.target.value,
                                            })
                                        }
                                        placeholder="Track Order"
                                        className={inputClass}
                                    />

                                    <input
                                        type="text"
                                        value={link.url}
                                        maxLength={2048}
                                        onChange={(event) =>
                                            updateLink(index, {
                                                url: event.target.value,
                                            })
                                        }
                                        placeholder="#"
                                        className={inputClass}
                                    />

                                    <button
                                        type="button"
                                        disabled={disabled}
                                        onClick={() => removeLink(index)}
                                        aria-label={`Remove ${link.label || 'announcement'} link`}
                                        className="inline-flex min-h-11 items-center justify-center rounded-lg border border-red-200 bg-white px-3 text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <Trash2 className="h-4 w-4" aria-hidden="true" />
                                    </button>
                                </div>
                            ))}

                            {value.links.length === 0 && (
                                <div className="rounded-xl border border-dashed border-neutral-200 p-5 text-center text-sm text-neutral-500">
                                    No utility links.
                                </div>
                            )}
                        </div>

                        <p className="mt-3 text-xs text-neutral-400">
                            Maximum 4 announcement links.
                        </p>
                    </div>
                </fieldset>
            </div>
        </HeaderEditorAccordion>
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
