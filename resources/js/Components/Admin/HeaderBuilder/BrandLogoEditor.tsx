import { ExternalLink, Image, Store } from 'lucide-react'

import HeaderEditorAccordion from '@/Components/Admin/HeaderBuilder/HeaderEditorAccordion'
import type { HeaderBrandConfig } from '@/types/header-builder'

interface Props {
    value: HeaderBrandConfig
    disabled?: boolean
    onChange: (value: HeaderBrandConfig) => void
}

export default function BrandLogoEditor({ value, disabled = false, onChange }: Props) {
    const update = <K extends keyof HeaderBrandConfig>(key: K, nextValue: HeaderBrandConfig[K]) => {
        if (disabled) {
            return
        }

        onChange({
            ...value,
            [key]: nextValue,
        })
    }

    const logoUrl = value.logo_url?.trim() ?? ''

    return (
        <HeaderEditorAccordion
            title="Brand / Logo"
            description="Configure the storefront brand identity displayed in the main header."
            defaultOpen={false}
            icon={<Store className="h-5 w-5" strokeWidth={1.9} aria-hidden="true" />}
            badge={
                <span className="rounded-full bg-indigo-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-indigo-600">
                    Brand
                </span>
            }
        >
            <div className="space-y-6 p-5 sm:p-6">
                <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <Field label="Brand name" description="Main storefront brand text.">
                        <input
                            type="text"
                            value={value.name}
                            disabled={disabled}
                            maxLength={160}
                            onChange={(event) => update('name', event.target.value)}
                            placeholder="MWT STORE"
                            className={inputClass}
                        />
                    </Field>

                    <Field
                        label="Brand accent"
                        description="Optional text displayed immediately after the brand name."
                    >
                        <input
                            type="text"
                            value={value.accent ?? ''}
                            disabled={disabled}
                            maxLength={160}
                            onChange={(event) => update('accent', event.target.value)}
                            placeholder="."
                            className={inputClass}
                        />
                    </Field>
                </div>

                <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <Field
                        label="Home URL"
                        description="Destination when customers click the logo or brand."
                    >
                        <div className="relative">
                            <input
                                type="text"
                                value={value.home_url}
                                disabled={disabled}
                                maxLength={2048}
                                onChange={(event) => update('home_url', event.target.value)}
                                placeholder="/"
                                className={`${inputClass} pr-10`}
                            />

                            <ExternalLink
                                className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400"
                                aria-hidden="true"
                            />
                        </div>
                    </Field>

                    <Field
                        label="Fallback mark"
                        description="Short mark shown when no logo image is configured."
                    >
                        <input
                            type="text"
                            value={value.fallback_mark ?? ''}
                            disabled={disabled}
                            maxLength={160}
                            onChange={(event) => update('fallback_mark', event.target.value)}
                            placeholder="A"
                            className={inputClass}
                        />
                    </Field>
                </div>

                <div className="border-t border-neutral-100 pt-6">
                    <Field
                        label="Logo image URL"
                        description="Optional image URL. When supplied, the logo image replaces the fallback mark."
                    >
                        <div className="relative">
                            <input
                                type="text"
                                value={value.logo_url ?? ''}
                                disabled={disabled}
                                maxLength={2048}
                                onChange={(event) => update('logo_url', event.target.value || null)}
                                placeholder="/storage/branding/logo.svg"
                                className={`${inputClass} pl-10`}
                            />

                            <Image
                                className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400"
                                aria-hidden="true"
                            />
                        </div>
                    </Field>

                    <p className="mt-2 text-xs leading-5 text-neutral-400">
                        Direct media upload is not wired in this step. Existing storage URLs and
                        external image URLs can be used.
                    </p>
                </div>

                <div className="border-t border-neutral-100 pt-6">
                    <h3 className="text-sm font-semibold text-neutral-950">Brand preview</h3>

                    <p className="mt-1 text-xs leading-5 text-neutral-500">
                        Preview of the current working brand identity.
                    </p>

                    <div className="mt-4 flex min-w-0 items-center gap-3 rounded-xl border border-neutral-800 bg-neutral-950 p-4">
                        {logoUrl ? (
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white">
                                <img
                                    src={logoUrl}
                                    alt=""
                                    className="h-full w-full object-contain"
                                />
                            </div>
                        ) : (
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-lg font-black text-white">
                                {value.fallback_mark || 'A'}
                            </div>
                        )}

                        <div className="min-w-0">
                            <p className="truncate text-xl font-black tracking-tight text-white">
                                {value.name}

                                <span className="text-indigo-500">{value.accent ?? ''}</span>
                            </p>

                            <p className="mt-1 truncate text-xs text-neutral-500">
                                {value.home_url || '/'}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </HeaderEditorAccordion>
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
