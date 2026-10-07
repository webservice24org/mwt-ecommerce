import { ArrowRight, CircleHelp } from 'lucide-react'

import { useId, useRef, useState } from 'react'

import type { KeyboardEvent } from 'react'

import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'

import { readFaqSectionConfig, type StorefrontFaqItem } from './faq-storefront'

interface Props {
    section: StorefrontSectionProps['section']
}

export default function FaqSidePanelSection({ section }: Props) {
    const headingId = useId()

    const config = readFaqSectionConfig(section.config)

    const items = config.items

    const [storedActiveIndex, setStoredActiveIndex] = useState<number | null>(() =>
        config.open_first && items.length > 0 ? 0 : null,
    )

    const questionButtonRefs = useRef<Array<HTMLButtonElement | null>>([])

    const answerPanelId = useId()

    const questionIdPrefix = useId()

    if (items.length === 0) {
        return null
    }

    const lightTheme = config.text_theme === 'light'

    const centered = config.alignment === 'center'

    const activeIndex =
        storedActiveIndex === null
            ? null
            : storedActiveIndex >= 0 && storedActiveIndex < items.length
              ? storedActiveIndex
              : config.open_first
                ? 0
                : null

    const activeItem = activeIndex !== null ? (items[activeIndex] ?? null) : null

    const questionId = (index: number) => `${questionIdPrefix}-question-${index}`

    const focusQuestion = (index: number) => {
        questionButtonRefs.current[index]?.focus()
    }

    const handleQuestionKeyDown = (event: KeyboardEvent<HTMLButtonElement>, index: number) => {
        switch (event.key) {
            case 'ArrowDown':
            case 'ArrowRight': {
                event.preventDefault()

                focusQuestion(index === items.length - 1 ? 0 : index + 1)

                break
            }

            case 'ArrowUp':
            case 'ArrowLeft': {
                event.preventDefault()

                focusQuestion(index === 0 ? items.length - 1 : index - 1)

                break
            }

            case 'Home': {
                event.preventDefault()

                focusQuestion(0)

                break
            }

            case 'End': {
                event.preventDefault()

                focusQuestion(items.length - 1)

                break
            }
        }
    }

    return (
        <section
            aria-labelledby={config.heading ? headingId : undefined}
            aria-label={config.heading ? undefined : 'Frequently asked questions'}
            className="overflow-x-clip py-5 sm:py-8"
        >
            <div className="mx-auto w-full max-w-7xl px-3 sm:px-6 lg:px-8">
                <div
                    className={[
                        'min-w-0 overflow-hidden rounded-2xl border shadow-sm',
                        'sm:rounded-3xl',

                        lightTheme ? 'border-white/10' : 'border-slate-200/80',
                    ].join(' ')}
                    style={{
                        backgroundColor: config.background_color,
                    }}
                >
                    <div className="grid min-w-0 grid-cols-1 lg:grid-cols-12">
                        <aside
                            className={[
                                'min-w-0 border-b p-4',
                                'sm:p-6',
                                'lg:col-span-5 lg:border-b-0 lg:border-r lg:p-8',
                                'xl:p-10',

                                lightTheme ? 'border-white/10' : 'border-slate-200/80',
                            ].join(' ')}
                        >
                            <header
                                className={[
                                    'min-w-0',

                                    centered ? 'text-center lg:text-left' : 'text-left',
                                ].join(' ')}
                            >
                                <div
                                    aria-hidden="true"
                                    className={[
                                        'mb-4 inline-flex h-11 w-11 items-center justify-center',
                                        'rounded-xl',
                                        'sm:mb-5 sm:h-12 sm:w-12 sm:rounded-2xl',

                                        lightTheme
                                            ? 'bg-white/10 text-white'
                                            : 'bg-indigo-100 text-indigo-600',
                                    ].join(' ')}
                                >
                                    <CircleHelp className="h-5 w-5 sm:h-6 sm:w-6" />
                                </div>

                                {config.eyebrow && (
                                    <p
                                        className={[
                                            'break-words text-xs font-bold uppercase',
                                            'tracking-[0.15em] sm:tracking-[0.18em]',
                                            '[overflow-wrap:anywhere]',

                                            lightTheme ? 'text-indigo-200' : 'text-indigo-600',
                                        ].join(' ')}
                                    >
                                        {config.eyebrow}
                                    </p>
                                )}

                                {config.heading && (
                                    <h2
                                        id={headingId}
                                        className={[
                                            'break-words text-2xl font-extrabold tracking-tight',
                                            '[overflow-wrap:anywhere]',
                                            'sm:text-3xl',

                                            config.eyebrow ? 'mt-2' : '',

                                            lightTheme ? 'text-white' : 'text-slate-900',
                                        ].join(' ')}
                                    >
                                        {config.heading}
                                    </h2>
                                )}

                                {config.description && (
                                    <p
                                        className={[
                                            'mt-4 break-words text-sm leading-7',
                                            '[overflow-wrap:anywhere]',

                                            centered ? 'mx-auto max-w-xl lg:mx-0' : 'max-w-xl',

                                            lightTheme ? 'text-white/70' : 'text-slate-600',
                                        ].join(' ')}
                                    >
                                        {config.description}
                                    </p>
                                )}
                            </header>

                            <div
                                role="group"
                                aria-label="FAQ questions"
                                className={[
                                    'mt-6 grid min-w-0 grid-cols-1 gap-2',
                                    'sm:mt-8 sm:grid-cols-2',
                                    'lg:grid-cols-1',
                                ].join(' ')}
                            >
                                {items.map((item, index) => {
                                    const isActive = activeIndex === index

                                    return (
                                        <button
                                            key={`${index}-${item.question}`}
                                            ref={(element) => {
                                                questionButtonRefs.current[index] = element
                                            }}
                                            id={questionId(index)}
                                            type="button"
                                            aria-expanded={isActive}
                                            aria-controls={answerPanelId}
                                            onClick={() =>
                                                setStoredActiveIndex(isActive ? null : index)
                                            }
                                            onKeyDown={(event) =>
                                                handleQuestionKeyDown(event, index)
                                            }
                                            className={[
                                                'group flex min-h-12 w-full min-w-0',
                                                'items-center justify-between gap-3',
                                                'rounded-xl border px-4 py-3',
                                                'text-left transition',
                                                'motion-reduce:transition-none',
                                                'focus-visible:outline-none',
                                                'focus-visible:ring-2',
                                                'focus-visible:ring-indigo-500',

                                                isActive
                                                    ? lightTheme
                                                        ? [
                                                              'border-indigo-300/40',
                                                              'bg-white/10',
                                                              'text-white',
                                                          ].join(' ')
                                                        : [
                                                              'border-indigo-200',
                                                              'bg-indigo-50',
                                                              'text-indigo-950',
                                                          ].join(' ')
                                                    : lightTheme
                                                      ? [
                                                            'border-white/10',
                                                            'bg-white/[0.04]',
                                                            'text-white/75',
                                                            'hover:bg-white/[0.08]',
                                                        ].join(' ')
                                                      : [
                                                            'border-slate-200/80',
                                                            'bg-white/60',
                                                            'text-slate-700',
                                                            'hover:border-indigo-200',
                                                            'hover:bg-white',
                                                        ].join(' '),
                                            ].join(' ')}
                                        >
                                            <span className="min-w-0 flex-1">
                                                <span
                                                    className={[
                                                        'block break-words text-sm font-semibold',
                                                        'leading-5 [overflow-wrap:anywhere]',
                                                    ].join(' ')}
                                                >
                                                    {item.question}
                                                </span>
                                            </span>

                                            <ArrowRight
                                                aria-hidden="true"
                                                className={[
                                                    'h-4 w-4 shrink-0 transition-transform',
                                                    'motion-reduce:transition-none',

                                                    isActive ? 'translate-x-1' : '',
                                                ].join(' ')}
                                            />
                                        </button>
                                    )
                                })}
                            </div>
                        </aside>

                        <div
                            className={[
                                'min-w-0 p-4',
                                'sm:min-h-[20rem] sm:p-8',
                                'lg:col-span-7 lg:min-h-[28rem] lg:p-10',
                                'xl:p-12',
                            ].join(' ')}
                        >
                            {activeItem ? (
                                <FaqAnswerPanel
                                    item={activeItem}
                                    index={activeIndex ?? 0}
                                    panelId={answerPanelId}
                                    labelledBy={
                                        activeIndex !== null ? questionId(activeIndex) : undefined
                                    }
                                    lightTheme={lightTheme}
                                />
                            ) : (
                                <EmptyAnswerPanel panelId={answerPanelId} lightTheme={lightTheme} />
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    )
}

interface FaqAnswerPanelProps {
    item: StorefrontFaqItem

    index: number

    panelId: string

    labelledBy: string | undefined

    lightTheme: boolean
}

function FaqAnswerPanel({ item, index, panelId, labelledBy, lightTheme }: FaqAnswerPanelProps) {
    const number = String(index + 1).padStart(2, '0')

    return (
        <article
            id={panelId}
            role="region"
            aria-labelledby={labelledBy}
            className="flex min-h-full min-w-0 flex-col justify-center"
        >
            <div
                className={[
                    'mb-5 inline-flex h-10 w-10 items-center justify-center',
                    'rounded-xl text-xs font-bold',
                    'sm:mb-6 sm:h-11 sm:w-11 sm:text-sm',

                    lightTheme ? 'bg-white/10 text-indigo-200' : 'bg-indigo-100 text-indigo-700',
                ].join(' ')}
            >
                {number}
            </div>

            <p
                className={[
                    'break-words text-xs font-bold uppercase',
                    'tracking-[0.15em] sm:tracking-[0.18em]',
                    '[overflow-wrap:anywhere]',

                    lightTheme ? 'text-indigo-200' : 'text-indigo-600',
                ].join(' ')}
            >
                Selected Question
            </p>

            <h3
                className={[
                    'mt-3 break-words text-xl font-extrabold leading-tight',
                    '[overflow-wrap:anywhere]',
                    'sm:text-3xl',

                    lightTheme ? 'text-white' : 'text-slate-900',
                ].join(' ')}
            >
                {item.question}
            </h3>

            <div
                aria-hidden="true"
                className={[
                    'my-5 h-px w-full sm:my-6',

                    lightTheme ? 'bg-white/10' : 'bg-slate-200/80',
                ].join(' ')}
            />

            <p
                className={[
                    'break-words text-sm leading-7',
                    '[overflow-wrap:anywhere]',
                    'sm:text-base sm:leading-8',

                    lightTheme ? 'text-white/70' : 'text-slate-600',
                ].join(' ')}
            >
                {item.answer}
            </p>
        </article>
    )
}

interface EmptyAnswerPanelProps {
    panelId: string
    lightTheme: boolean
}

function EmptyAnswerPanel({ panelId, lightTheme }: EmptyAnswerPanelProps) {
    return (
        <div
            id={panelId}
            className="flex min-h-64 min-w-0 flex-col items-center justify-center text-center sm:min-h-full"
        >
            <div
                aria-hidden="true"
                className={[
                    'flex h-12 w-12 items-center justify-center rounded-xl',
                    'sm:h-14 sm:w-14 sm:rounded-2xl',

                    lightTheme ? 'bg-white/10 text-white' : 'bg-slate-100 text-slate-500',
                ].join(' ')}
            >
                <CircleHelp className="h-6 w-6 sm:h-7 sm:w-7" />
            </div>

            <p
                className={[
                    'mt-4 break-words text-base font-bold',
                    '[overflow-wrap:anywhere]',
                    'sm:mt-5',

                    lightTheme ? 'text-white' : 'text-slate-900',
                ].join(' ')}
            >
                Choose a question
            </p>

            <p
                className={[
                    'mt-2 max-w-sm break-words text-sm leading-6',
                    '[overflow-wrap:anywhere]',

                    lightTheme ? 'text-white/60' : 'text-slate-500',
                ].join(' ')}
            >
                Select one of the FAQ questions to view its answer.
            </p>
        </div>
    )
}
