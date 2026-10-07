import { CircleHelp } from 'lucide-react'

import { useId } from 'react'

import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'

import { readFaqSectionConfig, type StorefrontFaqItem } from './faq-storefront'

interface Props {
    section: StorefrontSectionProps['section']
}

export default function FaqTwoColumnSection({ section }: Props) {
    const headingId = useId()

    const config = readFaqSectionConfig(section.config)

    const items = config.items

    if (items.length === 0) {
        return null
    }

    const lightTheme = config.text_theme === 'light'

    const centered = config.alignment === 'center'

    return (
        <section
            aria-labelledby={config.heading ? headingId : undefined}
            aria-label={config.heading ? undefined : 'Frequently asked questions'}
            className="overflow-x-clip py-5 sm:py-8"
        >
            <div className="mx-auto w-full max-w-7xl px-3 sm:px-6 lg:px-8">
                <div
                    className={[
                        'min-w-0 overflow-hidden rounded-2xl border',
                        'p-4 shadow-sm',
                        'sm:rounded-3xl sm:p-8',
                        'lg:p-10 xl:p-12',

                        lightTheme ? 'border-white/10' : 'border-slate-200/80',
                    ].join(' ')}
                    style={{
                        backgroundColor: config.background_color,
                    }}
                >
                    <div
                        className={[
                            'grid min-w-0 grid-cols-1 gap-8',
                            'lg:grid-cols-12 lg:gap-10',
                            'xl:gap-12',
                        ].join(' ')}
                    >
                        <FaqIntro
                            headingId={headingId}
                            eyebrow={config.eyebrow}
                            heading={config.heading}
                            description={config.description}
                            centered={centered}
                            lightTheme={lightTheme}
                        />

                        <div
                            role="list"
                            aria-label="FAQ answers"
                            className={[
                                'grid min-w-0 grid-cols-1 gap-4',
                                'sm:grid-cols-2 sm:gap-5',
                                'lg:col-span-8 lg:grid-cols-1',
                                'xl:grid-cols-2',
                            ].join(' ')}
                        >
                            {items.map((item, index) => (
                                <FaqCard
                                    key={`${index}-${item.question}`}
                                    item={item}
                                    index={index}
                                    lightTheme={lightTheme}
                                />
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    )
}

interface FaqIntroProps {
    headingId: string

    eyebrow: string | null
    heading: string | null
    description: string

    centered: boolean
    lightTheme: boolean
}

function FaqIntro({
    headingId,
    eyebrow,
    heading,
    description,
    centered,
    lightTheme,
}: FaqIntroProps) {
    return (
        <header
            className={[
                'min-w-0 lg:col-span-4 lg:self-start',
                'lg:sticky lg:top-24',

                centered ? 'text-center lg:text-left' : 'text-left',
            ].join(' ')}
        >
            <div
                aria-hidden="true"
                className={[
                    'mb-4 inline-flex h-11 w-11 items-center justify-center',
                    'rounded-xl sm:mb-5 sm:h-12 sm:w-12 sm:rounded-2xl',

                    lightTheme ? 'bg-white/10 text-white' : 'bg-indigo-100 text-indigo-600',
                ].join(' ')}
            >
                <CircleHelp className="h-5 w-5 sm:h-6 sm:w-6" />
            </div>

            {eyebrow && (
                <p
                    className={[
                        'break-words text-xs font-bold uppercase',
                        'tracking-[0.15em] sm:tracking-[0.18em]',
                        '[overflow-wrap:anywhere]',

                        lightTheme ? 'text-indigo-200' : 'text-indigo-600',
                    ].join(' ')}
                >
                    {eyebrow}
                </p>
            )}

            {heading && (
                <h2
                    id={headingId}
                    className={[
                        'break-words text-2xl font-extrabold tracking-tight',
                        '[overflow-wrap:anywhere]',
                        'sm:text-3xl',

                        eyebrow ? 'mt-2' : '',

                        lightTheme ? 'text-white' : 'text-slate-900',
                    ].join(' ')}
                >
                    {heading}
                </h2>
            )}

            {description && (
                <p
                    className={[
                        'mt-4 break-words text-sm leading-7',
                        '[overflow-wrap:anywhere]',

                        centered ? 'mx-auto max-w-xl lg:mx-0' : 'max-w-xl',

                        lightTheme ? 'text-white/70' : 'text-slate-600',
                    ].join(' ')}
                >
                    {description}
                </p>
            )}

            <div
                aria-hidden="true"
                className={[
                    'mt-6 h-px w-full',

                    lightTheme ? 'bg-white/10' : 'bg-slate-200/80',
                ].join(' ')}
            />

            <p
                className={[
                    'mt-5 text-xs font-medium leading-5',

                    lightTheme ? 'text-white/50' : 'text-slate-500',
                ].join(' ')}
            >
                Browse the questions beside this panel for quick answers.
            </p>
        </header>
    )
}

interface FaqCardProps {
    item: StorefrontFaqItem

    index: number
    lightTheme: boolean
}

function FaqCard({ item, index, lightTheme }: FaqCardProps) {
    const number = String(index + 1).padStart(2, '0')

    return (
        <article
            role="listitem"
            className={[
                'group min-w-0 rounded-xl border p-4',
                'transition duration-200',
                'motion-reduce:transition-none',
                'sm:rounded-2xl sm:p-5',
                'xl:p-6',

                lightTheme
                    ? [
                          'border-white/10',
                          'bg-white/[0.06]',
                          'hover:border-white/20',
                          'hover:bg-white/[0.09]',
                      ].join(' ')
                    : [
                          'border-slate-200/80',
                          'bg-slate-50/70',
                          'hover:border-indigo-200',
                          'hover:bg-white',
                          'hover:shadow-md',
                      ].join(' '),
            ].join(' ')}
        >
            <div className="flex min-w-0 items-start gap-3 sm:gap-4">
                <span
                    aria-hidden="true"
                    className={[
                        'flex h-8 min-w-8 shrink-0 items-center justify-center',
                        'rounded-lg text-[11px] font-bold',
                        'sm:h-9 sm:min-w-9 sm:rounded-xl sm:text-xs',

                        lightTheme
                            ? 'bg-white/10 text-indigo-200'
                            : 'bg-indigo-100 text-indigo-700',
                    ].join(' ')}
                >
                    {number}
                </span>

                <div className="min-w-0 flex-1">
                    <h3
                        className={[
                            'break-words text-sm font-bold leading-6',
                            '[overflow-wrap:anywhere]',
                            'sm:text-base',

                            lightTheme ? 'text-white' : 'text-slate-900',
                        ].join(' ')}
                    >
                        {item.question}
                    </h3>

                    <p
                        className={[
                            'mt-2 break-words text-sm leading-7',
                            '[overflow-wrap:anywhere]',

                            lightTheme ? 'text-white/65' : 'text-slate-600',
                        ].join(' ')}
                    >
                        {item.answer}
                    </p>
                </div>
            </div>
        </article>
    )
}
