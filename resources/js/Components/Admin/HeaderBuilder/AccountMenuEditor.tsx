import { LogIn, Plus, Trash2, UserRound, UserRoundPlus } from 'lucide-react'

import HeaderEditorAccordion from '@/Components/Admin/HeaderBuilder/HeaderEditorAccordion'
import type { HeaderAccountConfig, HeaderLink } from '@/types/header-builder'

interface Props {
    value: HeaderAccountConfig
    disabled?: boolean
    onChange: (value: HeaderAccountConfig) => void
}

export default function AccountMenuEditor({ value, disabled = false, onChange }: Props) {
    const update = <K extends keyof HeaderAccountConfig>(
        key: K,
        nextValue: HeaderAccountConfig[K],
    ) => {
        if (disabled) {
            return
        }

        onChange({
            ...value,
            [key]: nextValue,
        })
    }

    const updateMenuLink = (index: number, patch: Partial<HeaderLink>) => {
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

    const addMenuLink = () => {
        if (disabled || value.menu_links.length >= 8) {
            return
        }

        update('menu_links', [
            ...value.menu_links,
            {
                label: '',
                url: '#',
            },
        ])
    }

    const removeMenuLink = (index: number) => {
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
            title="Account Menu"
            description="Configure guest authentication links and authenticated customer account menu items."
            icon={<UserRound className="h-5 w-5" strokeWidth={1.9} aria-hidden="true" />}
            badge={<StatusBadge enabled={value.enabled} />}
        >
            <div className="space-y-6 p-5 sm:p-6">
                <SettingToggle
                    title="Account action"
                    description="Show the customer account action in the storefront header."
                    checked={value.enabled}
                    disabled={disabled}
                    onChange={(checked) => update('enabled', checked)}
                />

                <fieldset disabled={disabled} className="space-y-6 disabled:opacity-60">
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <Field
                            label="Guest login URL"
                            description="Destination used when a guest chooses to sign in."
                            icon={<LogIn className="h-4 w-4" aria-hidden="true" />}
                        >
                            <input
                                type="text"
                                value={value.guest_login_url}
                                disabled={disabled || !value.enabled}
                                maxLength={2048}
                                onChange={(event) => update('guest_login_url', event.target.value)}
                                placeholder="/login"
                                className={inputClass}
                            />
                        </Field>

                        <Field
                            label="Guest register URL"
                            description="Destination used when a guest creates a new account."
                            icon={<UserRoundPlus className="h-4 w-4" aria-hidden="true" />}
                        >
                            <input
                                type="text"
                                value={value.guest_register_url}
                                disabled={disabled || !value.enabled}
                                maxLength={2048}
                                onChange={(event) =>
                                    update('guest_register_url', event.target.value)
                                }
                                placeholder="/register"
                                className={inputClass}
                            />
                        </Field>
                    </div>

                    <div className="border-t border-neutral-100 pt-6">
                        <div className="flex min-w-0 flex-wrap items-start justify-between gap-3">
                            <div className="min-w-0">
                                <h3 className="text-sm font-semibold text-neutral-950">
                                    Authenticated menu links
                                </h3>

                                <p className="mt-1 text-sm leading-6 text-neutral-500">
                                    Links displayed when an authenticated customer opens the account
                                    menu.
                                </p>
                            </div>

                            <button
                                type="button"
                                disabled={
                                    disabled || !value.enabled || value.menu_links.length >= 8
                                }
                                onClick={addMenuLink}
                                className="inline-flex min-h-10 shrink-0 items-center gap-2 rounded-lg border border-neutral-200 bg-white px-3 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Plus className="h-4 w-4" aria-hidden="true" />
                                Add link
                            </button>
                        </div>

                        <div className="mt-4 space-y-3">
                            {value.menu_links.map((link, index) => (
                                <div
                                    key={index}
                                    className="grid min-w-0 grid-cols-1 gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-4 md:grid-cols-[1fr_1fr_auto]"
                                >
                                    <input
                                        type="text"
                                        value={link.label}
                                        disabled={disabled || !value.enabled}
                                        maxLength={160}
                                        onChange={(event) =>
                                            updateMenuLink(index, {
                                                label: event.target.value,
                                            })
                                        }
                                        placeholder="My Orders"
                                        className={inputClass}
                                    />

                                    <input
                                        type="text"
                                        value={link.url}
                                        disabled={disabled || !value.enabled}
                                        maxLength={2048}
                                        onChange={(event) =>
                                            updateMenuLink(index, {
                                                url: event.target.value,
                                            })
                                        }
                                        placeholder="/account/orders"
                                        className={inputClass}
                                    />

                                    <button
                                        type="button"
                                        disabled={disabled || !value.enabled}
                                        onClick={() => removeMenuLink(index)}
                                        aria-label={`Remove ${link.label || 'account'} link`}
                                        className="inline-flex min-h-11 items-center justify-center rounded-lg border border-red-200 bg-white px-3 text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <Trash2 className="h-4 w-4" aria-hidden="true" />
                                    </button>
                                </div>
                            ))}

                            {value.menu_links.length === 0 && (
                                <div className="rounded-xl border border-dashed border-neutral-200 p-5 text-center text-sm text-neutral-500">
                                    No account menu links.
                                </div>
                            )}
                        </div>

                        <p className="mt-3 text-xs text-neutral-400">
                            Maximum 8 authenticated account links.
                        </p>
                    </div>
                </fieldset>

                <div className="rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <p className="text-sm font-semibold text-blue-950">Runtime customer data</p>

                    <p className="mt-1 text-xs leading-5 text-blue-800">
                        Customer name, initials, authentication state and logout behavior are
                        runtime storefront data. They are not stored in Header Builder
                        configuration.
                    </p>
                </div>
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
    icon,
    children,
}: {
    label: string
    description: string
    icon: React.ReactNode
    children: React.ReactNode
}) {
    return (
        <label className="block min-w-0">
            <span className="flex items-center gap-2 text-sm font-semibold text-neutral-900">
                <span className="text-neutral-400">{icon}</span>

                {label}
            </span>

            <span className="mt-1 block text-xs leading-5 text-neutral-500">{description}</span>

            <span className="mt-2 block">{children}</span>
        </label>
    )
}

const inputClass =
    'min-h-11 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none transition placeholder:text-neutral-400 focus:border-neutral-950 focus:ring-1 focus:ring-neutral-950 disabled:cursor-not-allowed disabled:bg-neutral-100'
