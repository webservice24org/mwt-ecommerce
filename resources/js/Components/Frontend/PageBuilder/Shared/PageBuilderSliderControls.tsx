import { ChevronLeft, ChevronRight } from 'lucide-react'

import type { ReactNode } from 'react'

type SliderControlTheme = 'light' | 'dark'

interface ArrowProps {
    canGoPrevious: boolean
    canGoNext: boolean

    onPrevious: () => void
    onNext: () => void

    theme?: SliderControlTheme

    previousLabel?: string
    nextLabel?: string
}

export function PageBuilderSliderArrows({
    canGoPrevious,
    canGoNext,
    onPrevious,
    onNext,
    theme = 'dark',
    previousLabel = 'Previous slide',
    nextLabel = 'Next slide',
}: ArrowProps) {
    return (
        <div role="group" className="flex items-center gap-2" aria-label="Slider navigation">
            <SliderArrowButton
                label={previousLabel}
                disabled={!canGoPrevious}
                theme={theme}
                onClick={onPrevious}
            >
                <ChevronLeft aria-hidden="true" className="h-5 w-5" />
            </SliderArrowButton>

            <SliderArrowButton
                label={nextLabel}
                disabled={!canGoNext}
                theme={theme}
                onClick={onNext}
            >
                <ChevronRight aria-hidden="true" className="h-5 w-5" />
            </SliderArrowButton>
        </div>
    )
}

interface DotsProps {
    activeIndex: number
    slideCount: number

    onSelect: (index: number) => void

    theme?: SliderControlTheme

    label?: string
    itemLabel?: string
}

export function PageBuilderSliderDots({
    activeIndex,
    slideCount,
    onSelect,
    theme = 'dark',
    label = 'Choose slide',
    itemLabel = 'slide',
}: DotsProps) {
    if (slideCount <= 1) {
        return null
    }

    return (
        <div
            role="group"
            className="flex flex-wrap items-center justify-center gap-2"
            aria-label={label}
        >
            {Array.from(
                {
                    length: slideCount,
                },
                (_item, index) => {
                    const active = index === activeIndex

                    return (
                        <button
                            key={index}
                            type="button"
                            aria-label={`Go to ${itemLabel} ${index + 1} of ${slideCount}`}
                            aria-current={active ? 'true' : undefined}
                            onClick={() => onSelect(index)}
                            className={[
                                'h-2.5 rounded-full transition-all',
                                'focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-offset-2',
                                'motion-reduce:transition-none',

                                active ? 'w-6' : 'w-2.5',

                                dotClass(theme, active),
                            ].join(' ')}
                        />
                    )
                },
            )}
        </div>
    )
}

interface SliderArrowButtonProps {
    label: string

    disabled: boolean

    theme: SliderControlTheme

    children: ReactNode

    onClick: () => void
}

function SliderArrowButton({ label, disabled, theme, children, onClick }: SliderArrowButtonProps) {
    return (
        <button
            type="button"
            aria-label={label}
            title={label}
            disabled={disabled}
            onClick={onClick}
            className={[
                'inline-flex h-11 w-11 shrink-0 items-center justify-center',
                'rounded-full border transition',
                'focus-visible:outline-none focus-visible:ring-2',
                'focus-visible:ring-offset-2',
                'disabled:cursor-not-allowed disabled:opacity-40',
                'motion-reduce:transition-none',

                theme === 'light'
                    ? [
                          'border-white/20 text-white',
                          'hover:bg-white/10',
                          'focus-visible:ring-white',
                          'focus-visible:ring-offset-slate-900',
                      ].join(' ')
                    : [
                          'border-slate-200 text-slate-700',
                          'hover:bg-slate-100',
                          'focus-visible:ring-indigo-600',
                          'focus-visible:ring-offset-white',
                      ].join(' '),
            ].join(' ')}
        >
            {children}
        </button>
    )
}

function dotClass(theme: SliderControlTheme, active: boolean): string {
    if (theme === 'light') {
        return active
            ? 'bg-indigo-300 focus-visible:ring-white'
            : 'bg-white/30 hover:bg-white/50 focus-visible:ring-white'
    }

    return active
        ? 'bg-indigo-600 focus-visible:ring-indigo-600'
        : 'bg-slate-300 hover:bg-slate-400 focus-visible:ring-indigo-600'
}
