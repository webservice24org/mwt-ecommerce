import { Check, Languages, Mail, ShieldCheck, ShoppingBag } from 'lucide-react'
import type { LucideIcon } from 'lucide-react'

import type { FooterTemplateKey, FooterTemplateOption } from '@/types/footer-builder'

interface Props {
    templates: FooterTemplateOption[]
    value: FooterTemplateKey
    savedValue: FooterTemplateKey
    disabled?: boolean

    onChange: (template: FooterTemplateKey) => void

    onRevert: () => void
}

interface TemplateMeta {
    number: string
    eyebrow: string
    features: string[]
    icon: LucideIcon
}

const templateMeta: Record<FooterTemplateKey, TemplateMeta> = {
    luxe_newsletter: {
        number: '01',
        eyebrow: 'Commerce + Newsletter',
        features: ['Value propositions', 'Newsletter signup', 'Payment methods'],
        icon: Mail,
    },

    minimal_localized: {
        number: '02',
        eyebrow: 'Minimal + Localization',
        features: ['Minimal brand layout', 'Language options', 'Currency options'],
        icon: Languages,
    },

    marketplace_trust: {
        number: '03',
        eyebrow: 'Marketplace + Trust',
        features: ['Promotion banner', 'Popular categories', 'Trust certifications'],
        icon: ShieldCheck,
    },
}

export default function FooterTemplateSelector({
    templates,
    value,
    savedValue,
    disabled = false,
    onChange,
    onRevert,
}: Props) {
    const selected = templates.find((template) => template.key === value) ?? null

    const saved = templates.find((template) => template.key === savedValue) ?? null

    const changed = value !== savedValue

    return (
        <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
            <div className="border-b border-neutral-200 p-5 sm:p-6">
                <div className="flex min-w-0 items-start gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700">
                        <ShoppingBag className="h-5 w-5" strokeWidth={1.9} />
                    </div>

                    <div className="min-w-0">
                        <h2 className="text-base font-semibold text-neutral-950">Footer design</h2>

                        <p className="mt-1 max-w-3xl text-sm leading-6 text-neutral-500">
                            Choose the visual structure used by the global storefront footer.
                            Changing the design does not remove your shared footer content.
                        </p>
                    </div>
                </div>
            </div>

            <div className="p-5 sm:p-6">
                <fieldset disabled={disabled} className="min-w-0">
                    <legend className="sr-only">Select footer design</legend>

                    <div className="grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-3">
                        {templates.map((template) => {
                            const active = template.key === value

                            const meta = templateMeta[template.key]

                            const Icon = meta.icon

                            return (
                                <button
                                    key={template.key}
                                    type="button"
                                    aria-pressed={active}
                                    onClick={() => {
                                        if (disabled || active) {
                                            return
                                        }

                                        onChange(template.key)
                                    }}
                                    className={[
                                        'group relative min-w-0 overflow-hidden rounded-xl border p-5 text-left transition',
                                        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2',
                                        active
                                            ? 'border-neutral-950 bg-neutral-950 text-white shadow-sm'
                                            : 'border-neutral-200 bg-white text-neutral-900 hover:border-neutral-300 hover:bg-neutral-50',
                                        disabled ? 'cursor-default opacity-75' : '',
                                    ].join(' ')}
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div
                                            className={[
                                                'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
                                                active
                                                    ? 'bg-white/10 text-white'
                                                    : 'bg-neutral-100 text-neutral-600',
                                            ].join(' ')}
                                        >
                                            <Icon className="h-4 w-4" strokeWidth={1.9} />
                                        </div>

                                        <div className="flex shrink-0 items-center gap-2">
                                            <span
                                                className={[
                                                    'text-xs font-bold tracking-[0.16em]',
                                                    active
                                                        ? 'text-neutral-400'
                                                        : 'text-neutral-400',
                                                ].join(' ')}
                                            >
                                                {meta.number}
                                            </span>

                                            <span
                                                className={[
                                                    'flex h-6 w-6 items-center justify-center rounded-full border transition',
                                                    active
                                                        ? 'border-white bg-white text-neutral-950'
                                                        : 'border-neutral-200 bg-white text-transparent',
                                                ].join(' ')}
                                            >
                                                <Check className="h-3.5 w-3.5" />
                                            </span>
                                        </div>
                                    </div>

                                    <div className="mt-6 min-w-0">
                                        <p
                                            className={[
                                                'text-[11px] font-bold uppercase tracking-[0.13em]',
                                                active ? 'text-neutral-400' : 'text-neutral-400',
                                            ].join(' ')}
                                        >
                                            {meta.eyebrow}
                                        </p>

                                        <h3 className="mt-2 text-base font-semibold">
                                            {template.label}
                                        </h3>

                                        <p
                                            className={[
                                                'mt-2 text-sm leading-6',
                                                active ? 'text-neutral-300' : 'text-neutral-500',
                                            ].join(' ')}
                                        >
                                            {template.description}
                                        </p>

                                        <div className="mt-5 space-y-2">
                                            {meta.features.map((feature) => (
                                                <div
                                                    key={feature}
                                                    className="flex min-w-0 items-center gap-2"
                                                >
                                                    <span
                                                        className={[
                                                            'h-1.5 w-1.5 shrink-0 rounded-full',
                                                            active
                                                                ? 'bg-neutral-400'
                                                                : 'bg-neutral-300',
                                                        ].join(' ')}
                                                    />

                                                    <span
                                                        className={[
                                                            'truncate text-xs',
                                                            active
                                                                ? 'text-neutral-300'
                                                                : 'text-neutral-500',
                                                        ].join(' ')}
                                                    >
                                                        {feature}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </button>
                            )
                        })}
                    </div>
                </fieldset>

                <div className="mt-5 rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                    <div className="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="min-w-0">
                            <p className="text-xs font-bold uppercase tracking-[0.12em] text-neutral-400">
                                Selected design
                            </p>

                            <p className="mt-1 truncate text-sm font-semibold text-neutral-900">
                                {selected?.label ?? value}
                            </p>

                            {changed ? (
                                <p className="mt-1 text-sm leading-6 text-amber-700">
                                    This is a pending template change. Save Footer to replace{' '}
                                    <span className="font-semibold">
                                        {saved?.label ?? savedValue}
                                    </span>
                                    .
                                </p>
                            ) : (
                                <p className="mt-1 text-sm leading-6 text-neutral-500">
                                    This is the currently saved storefront footer design.
                                </p>
                            )}
                        </div>

                        {changed && !disabled && (
                            <button
                                type="button"
                                onClick={onRevert}
                                className="inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-neutral-300 bg-white px-3 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50"
                            >
                                Revert design
                            </button>
                        )}
                    </div>
                </div>
            </div>
        </section>
    )
}
