import { Check, LayoutTemplate, PanelTop } from 'lucide-react'

import type { HeaderTemplateKey, HeaderTemplateOption } from '@/types/header-builder'

interface Props {
    templates: HeaderTemplateOption[]
    selected: HeaderTemplateKey
    disabled?: boolean
    onSelect: (template: HeaderTemplateKey) => void
}

const templateDescriptions: Record<HeaderTemplateKey, string> = {
    mega_menu:
        'Announcement bar, product search, wishlist, cart, account actions, category navigation and mega menu.',
}

export default function HeaderTemplateSelector({
    templates,
    selected,
    disabled = false,
    onSelect,
}: Props) {
    return (
        <section
            className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm"
            aria-labelledby="header-template-heading"
        >
            <div className="border-b border-neutral-100 p-5 sm:p-6">
                <div className="flex min-w-0 items-start gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <LayoutTemplate className="h-5 w-5" strokeWidth={1.9} aria-hidden="true" />
                    </div>

                    <div className="min-w-0">
                        <h2
                            id="header-template-heading"
                            className="text-sm font-semibold text-neutral-950"
                        >
                            Header template
                        </h2>

                        <p className="mt-1 text-sm leading-6 text-neutral-500">
                            Choose the design used by the global storefront header.
                        </p>
                    </div>
                </div>
            </div>

            <div className="p-5 sm:p-6">
                <div
                    className="grid min-w-0 grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
                    role="radiogroup"
                    aria-label="Header templates"
                >
                    {templates.map((template) => {
                        const isSelected = template.key === selected

                        return (
                            <button
                                key={template.key}
                                type="button"
                                role="radio"
                                aria-checked={isSelected}
                                disabled={disabled}
                                onClick={() => onSelect(template.key)}
                                className={[
                                    'group relative min-w-0 rounded-2xl border p-4 text-left transition',
                                    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2',
                                    isSelected
                                        ? 'border-indigo-500 bg-indigo-50/60 ring-1 ring-indigo-500'
                                        : 'border-neutral-200 bg-white hover:border-neutral-300 hover:bg-neutral-50',
                                    disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer',
                                ].join(' ')}
                            >
                                <div className="flex min-w-0 items-start justify-between gap-3">
                                    <div
                                        className={[
                                            'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-colors',
                                            isSelected
                                                ? 'bg-indigo-600 text-white'
                                                : 'bg-neutral-100 text-neutral-600 group-hover:bg-neutral-200',
                                        ].join(' ')}
                                    >
                                        <PanelTop
                                            className="h-5 w-5"
                                            strokeWidth={1.9}
                                            aria-hidden="true"
                                        />
                                    </div>

                                    {isSelected && (
                                        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white">
                                            <Check
                                                className="h-4 w-4"
                                                strokeWidth={2.2}
                                                aria-hidden="true"
                                            />
                                        </span>
                                    )}
                                </div>

                                <div className="mt-4 min-w-0">
                                    <p className="break-words text-sm font-semibold text-neutral-950">
                                        {template.label}
                                    </p>

                                    <p className="mt-2 break-words text-sm leading-6 text-neutral-500">
                                        {templateDescriptions[template.key]}
                                    </p>
                                </div>

                                <div className="mt-4 min-w-0 rounded-lg border border-neutral-100 bg-white/80 px-3 py-2">
                                    <p className="break-all font-mono text-[11px] text-neutral-400">
                                        {template.key}
                                    </p>
                                </div>

                                {isSelected && (
                                    <p className="mt-3 text-xs font-semibold text-indigo-600">
                                        Selected template
                                    </p>
                                )}
                            </button>
                        )
                    })}
                </div>

                {templates.length === 1 && (
                    <p className="mt-4 text-xs leading-5 text-neutral-400">
                        One header design is currently available. Header Design 2 and Header Design
                        3 can be added later without changing this selector architecture.
                    </p>
                )}
            </div>
        </section>
    )
}
