import type { ReactNode } from 'react'

import SlideTransition, { type SlideEffect } from './SlideTransition'

interface Props {
    activeIndex: number
    direction: 1 | -1

    effect: SlideEffect

    children: ReactNode

    className?: string
}

export default function PageBuilderSliderViewport({
    activeIndex,
    direction,
    effect,
    children,
    className,
}: Props) {
    return (
        <div className={['min-w-0 overflow-hidden', className ?? ''].join(' ')}>
            <SlideTransition activeIndex={activeIndex} direction={direction} effect={effect}>
                {children}
            </SlideTransition>
        </div>
    )
}
