import type { ReactNode } from 'react'

export type SlideEffect = 'none' | 'fade' | 'slide_left' | 'slide_right' | 'slide_up' | 'slide_down'

interface Props {
    activeIndex: number
    direction: 1 | -1
    effect: SlideEffect
    children: ReactNode
}

export default function SlideTransition({ activeIndex, direction, effect, children }: Props) {
    return (
        <div
            key={activeIndex}
            data-slide-effect={effect}
            className={getAnimationClass(effect, direction)}
        >
            {children}
        </div>
    )
}

function getAnimationClass(effect: SlideEffect, direction: 1 | -1): string {
    switch (effect) {
        case 'slide_left':
            return direction === 1 ? 'animate-hero-slide-in-right' : 'animate-hero-slide-in-left'

        case 'slide_right':
            return direction === 1 ? 'animate-hero-slide-in-left' : 'animate-hero-slide-in-right'

        case 'slide_up':
            return direction === 1 ? 'animate-hero-slide-in-bottom' : 'animate-hero-slide-in-top'

        case 'slide_down':
            return direction === 1 ? 'animate-hero-slide-in-top' : 'animate-hero-slide-in-bottom'

        case 'none':
            return ''

        case 'fade':
        default:
            return 'animate-hero-fade-in'
    }
}
