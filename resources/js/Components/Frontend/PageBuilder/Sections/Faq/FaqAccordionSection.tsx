import { ChevronDown } from 'lucide-react'

import { useId, useRef, useState } from 'react'

import type { KeyboardEvent } from 'react'

import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'

import { readFaqSectionConfig, type StorefrontFaqItem } from './faq-storefront'

interface Props {
    section: StorefrontSectionProps['section']
}

export default function FaqAccordionSection({ section }: Props) {
    const headingId = useId()

    const config = readFaqSectionConfig(section.config)

    const items = config.items

    const [storedOpenIndexes, setStoredOpenIndexes] = useState<Set<number>>(() =>
        config.open_first && items.length > 0 ? new Set([0]) : new Set(),
    )

    const buttonRefs = useRef<Array<HTMLButtonElement | null>>([])

    const validOpenIndexes = Array.from(storedOpenIndexes).filter(
        (index) => index >= 0 && index < items.length,
    )

    const openIndexes = new Set(
        config.allow_multiple_open ? validOpenIndexes : validOpenIndexes.slice(0, 1),
    )

    if (items.length === 0) {
        return null
    }

    const lightTheme = config.text_theme === 'light'

    const centered = config.alignment === 'center'

    const toggleItem = (index: number) => {
        setStoredOpenIndexes((current) => {
            const validCurrent = new Set(
                Array.from(current).filter(
                    (currentIndex) => currentIndex >= 0 && currentIndex < items.length,
                ),
            )

            const isOpen = validCurrent.has(index)

            if (config.allow_multiple_open) {
                if (isOpen) {
                    validCurrent.delete(index)
                } else {
                    validCurrent.add(index)
                }

                return validCurrent
            }

            if (isOpen) {
                return new Set()
            }

            return new Set([index])
        })
    }

    const focusQuestion = (index: number) => {
        buttonRefs.current[index]?.focus()
    }

    const handleQuestionKeyDown = (event: KeyboardEvent<HTMLButtonElement>, index: number) => {
        switch (event.key) {
            case 'ArrowDown': {
                event.preventDefault()

                focusQuestion(index === items.length - 1 ? 0 : index + 1)

                break
            }

            case 'ArrowUp': {
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
            <div className="mx-auto w-full max-w-5xl px-3 sm:px-6 lg:px-8">
                <div
                    className={[
                        'min-w-0 overflow-hidden rounded-2xl border',
                        'p-4 shadow-sm',
                        'sm:rounded-3xl sm:p-8',
                        'lg:p-12',

                        lightTheme ? 'border-white/10' : 'border-slate-200/80',
                    ].join(' ')}
                    style={{
                        backgroundColor: config.background_color,
                    }}
                >
                    <header
                        className={[
                            'mb-6 min-w-0 sm:mb-10',

                            centered ? 'text-center' : 'text-left',
                        ].join(' ')}
                    >
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
                                    'break-words text-xl font-extrabold tracking-tight',
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
                                    'mt-3 break-words text-sm leading-6',
                                    '[overflow-wrap:anywhere]',
                                    'sm:text-base sm:leading-7',

                                    centered ? 'mx-auto max-w-2xl' : 'max-w-2xl',

                                    lightTheme ? 'text-white/70' : 'text-slate-600',
                                ].join(' ')}
                            >
                                {config.description}
                            </p>
                        )}
                    </header>

                    <div className="min-w-0 space-y-3 sm:space-y-4">
                        {items.map((item, index) => (
                            <FaqAccordionItem
                                key={`${index}-${item.question}`}
                                item={item}
                                index={index}
                                isOpen={openIndexes.has(index)}
                                lightTheme={lightTheme}
                                buttonRef={(element) => {
                                    buttonRefs.current[index] = element
                                }}
                                onKeyDown={(event) => handleQuestionKeyDown(event, index)}
                                onToggle={() => toggleItem(index)}
                            />
                        ))}
                    </div>
                </div>
            </div>
        </section>
    )
}

interface FaqAccordionItemProps {
    item: StorefrontFaqItem

    index: number

    isOpen: boolean
    lightTheme: boolean

    buttonRef: (element: HTMLButtonElement | null) => void

    onKeyDown: (event: KeyboardEvent<HTMLButtonElement>) => void

    onToggle: () => void
}

function FaqAccordionItem({
    item,
    index,
    isOpen,
    lightTheme,
    buttonRef,
    onKeyDown,
    onToggle,
}: FaqAccordionItemProps) {
    const id = useId()

    const buttonId = `${id}-button`

    const panelId = `${id}-panel`

    return (
        <article
            className={[
                'min-w-0 overflow-hidden rounded-xl border',
                'transition-colors duration-200',
                'motion-reduce:transition-none',
                'sm:rounded-2xl',

                isOpen
                    ? lightTheme
                        ? ['border-indigo-400/40', 'bg-white/10'].join(' ')
                        : ['border-indigo-200', 'bg-indigo-50/40'].join(' ')
                    : lightTheme
                      ? ['border-white/10', 'bg-white/[0.06]'].join(' ')
                      : ['border-slate-200', 'bg-slate-50/60'].join(' '),
            ].join(' ')}
        >
            <h3 className="min-w-0">
                <button
                    ref={buttonRef}
                    id={buttonId}
                    type="button"
                    aria-expanded={isOpen}
                    aria-controls={panelId}
                    onClick={onToggle}
                    onKeyDown={onKeyDown}
                    className={[
                        'flex min-h-14 w-full min-w-0 items-center justify-between',
                        'gap-3 p-4 text-left',
                        'sm:gap-4 sm:p-6',
                        'focus-visible:outline-none',
                        'focus-visible:ring-2',
                        'focus-visible:ring-inset',
                        'focus-visible:ring-indigo-500',
                    ].join(' ')}
                >
                    <span
                        className={[
                            'min-w-0 flex-1 break-words',
                            'text-base font-bold leading-6',
                            '[overflow-wrap:anywhere]',
                            'sm:text-lg',

                            lightTheme ? 'text-white' : 'text-slate-900',
                        ].join(' ')}
                    >
                        {item.question}
                    </span>

                    <span
                        aria-hidden="true"
                        className={[
                            'flex h-8 w-8 shrink-0 items-center justify-center',
                            'rounded-full transition',
                            'motion-reduce:transition-none',
                            'sm:h-9 sm:w-9',

                            isOpen
                                ? 'bg-indigo-600 text-white'
                                : lightTheme
                                  ? 'bg-white/10 text-white/75'
                                  : 'bg-slate-200/80 text-slate-600',
                        ].join(' ')}
                    >
                        <ChevronDown
                            className={[
                                'h-4 w-4 transition-transform duration-300',
                                'motion-reduce:transition-none',
                                'sm:h-5 sm:w-5',

                                isOpen ? 'rotate-180' : '',
                            ].join(' ')}
                        />
                    </span>
                </button>
            </h3>

            <div
                id={panelId}
                role="region"
                aria-labelledby={buttonId}
                aria-hidden={!isOpen}
                className={[
                    'grid transition-[grid-template-rows] duration-300 ease-in-out',
                    'motion-reduce:transition-none',

                    isOpen ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]',
                ].join(' ')}
            >
                <div className="min-w-0 overflow-hidden">
                    <div
                        className={[
                            'mx-4 border-t pb-4 pt-4',
                            'sm:mx-6 sm:pb-6 sm:pt-5',

                            lightTheme
                                ? ['border-white/10', 'text-white/70'].join(' ')
                                : ['border-slate-200/70', 'text-slate-600'].join(' '),
                        ].join(' ')}
                    >
                        <p className="break-words text-sm leading-7 [overflow-wrap:anywhere]">
                            {item.answer}
                        </p>
                    </div>
                </div>
            </div>

            <span className="sr-only">Question {index + 1} of FAQ</span>
        </article>
    )
}
