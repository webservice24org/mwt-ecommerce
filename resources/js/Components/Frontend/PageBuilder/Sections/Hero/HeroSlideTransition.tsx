import type { ReactNode } from 'react'

import type { HeroEffect } from './types'

interface Props {
    activeIndex: number
    direction: 1 | -1
    effect: HeroEffect
    children: ReactNode
}

export default function HeroSlideTransition({ activeIndex, direction, effect, children }: Props) {
    return (
        <div
            key={activeIndex}
            data-hero-effect={effect}
            className={getAnimationClass(effect, direction)}
        >
            {children}
        </div>
    )
}

function getAnimationClass(effect: HeroEffect, direction: 1 | -1): string {
    switch (effect) {
        case 'slide_left':
            return direction === 1 ? 'animate-hero-slide-in-right' : 'animate-hero-slide-in-left'

        case 'slide_right':
            return direction === 1 ? 'animate-hero-slide-in-left' : 'animate-hero-slide-in-right'

        case 'slide_up':
            return direction === 1 ? 'animate-hero-slide-in-bottom' : 'animate-hero-slide-in-top'

        case 'slide_down':
            return direction === 1 ? 'animate-hero-slide-in-top' : 'animate-hero-slide-in-bottom'

        case 'fade':
        default:
            return 'animate-hero-fade-in'
    }
}
