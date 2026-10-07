import { useCallback, useEffect, useState } from 'react'

import type { FocusEvent } from 'react'

interface UsePageBuilderSliderOptions {
    slideCount: number

    autoplay: boolean
    autoplayInterval: number

    pauseOnHover: boolean
    loop: boolean

    reducedMotion?: boolean
}

export interface PageBuilderSliderApi {
    activeIndex: number
    direction: 1 | -1

    canGoPrevious: boolean
    canGoNext: boolean

    previous: () => void
    next: () => void

    goTo: (index: number) => void

    pause: () => void
    resume: () => void

    interactionProps: {
        onPointerEnter: () => void
        onPointerLeave: () => void

        onFocusCapture: () => void
        onBlurCapture: (event: FocusEvent<HTMLElement>) => void
    }
}

export default function usePageBuilderSlider({
    slideCount,
    autoplay,
    autoplayInterval,
    pauseOnHover,
    loop,
    reducedMotion = false,
}: UsePageBuilderSliderOptions): PageBuilderSliderApi {
    const safeSlideCount = Math.max(0, slideCount)

    const [storedActiveIndex, setStoredActiveIndex] = useState(0)

    const [direction, setDirection] = useState<1 | -1>(1)

    const [pointerPaused, setPointerPaused] = useState(false)

    const [focusPaused, setFocusPaused] = useState(false)

    /*
     * Clamp during render instead of
     * synchronously setting state inside
     * an effect.
     *
     * This keeps the public index safe if
     * the number of slides becomes smaller.
     */
    const activeIndex = safeSlideCount === 0 ? 0 : Math.min(storedActiveIndex, safeSlideCount - 1)

    const previous = useCallback(() => {
        if (safeSlideCount <= 1) {
            return
        }

        if (activeIndex === 0 && !loop) {
            return
        }

        const targetIndex = activeIndex > 0 ? activeIndex - 1 : safeSlideCount - 1

        setDirection(-1)

        setStoredActiveIndex(targetIndex)
    }, [activeIndex, loop, safeSlideCount])

    const next = useCallback(() => {
        if (safeSlideCount <= 1) {
            return
        }

        const lastIndex = safeSlideCount - 1

        if (activeIndex === lastIndex && !loop) {
            return
        }

        const targetIndex = activeIndex < lastIndex ? activeIndex + 1 : 0

        setDirection(1)

        setStoredActiveIndex(targetIndex)
    }, [activeIndex, loop, safeSlideCount])

    const goTo = useCallback(
        (index: number) => {
            if (safeSlideCount === 0 || !Number.isInteger(index)) {
                return
            }

            const targetIndex = Math.max(0, Math.min(safeSlideCount - 1, index))

            if (targetIndex === activeIndex) {
                return
            }

            setDirection(targetIndex > activeIndex ? 1 : -1)

            setStoredActiveIndex(targetIndex)
        },
        [activeIndex, safeSlideCount],
    )

    const pause = useCallback(() => {
        setPointerPaused(true)
    }, [])

    const resume = useCallback(() => {
        setPointerPaused(false)
    }, [])

    const onPointerEnter = useCallback(() => {
        if (!pauseOnHover) {
            return
        }

        setPointerPaused(true)
    }, [pauseOnHover])

    const onPointerLeave = useCallback(() => {
        if (!pauseOnHover) {
            return
        }

        setPointerPaused(false)
    }, [pauseOnHover])

    /*
     * Keyboard interaction always pauses
     * autoplay while focus remains inside
     * the slider.
     */
    const onFocusCapture = useCallback(() => {
        setFocusPaused(true)
    }, [])

    const onBlurCapture = useCallback((event: FocusEvent<HTMLElement>) => {
        const nextTarget = event.relatedTarget

        if (nextTarget instanceof Node && event.currentTarget.contains(nextTarget)) {
            return
        }

        setFocusPaused(false)
    }, [])

    const paused = pointerPaused || focusPaused

    /*
     * Autoplay is timer-driven external
     * synchronization, so updating state
     * from the timeout callback is valid.
     */
    useEffect(() => {
        if (!autoplay || reducedMotion || paused || safeSlideCount <= 1) {
            return
        }

        /*
         * Non-looping sliders stop when
         * they reach the final slide.
         */
        if (!loop && activeIndex >= safeSlideCount - 1) {
            return
        }

        const timeout = window.setTimeout(
            () => {
                next()
            },
            Math.max(1000, autoplayInterval),
        )

        return () => {
            window.clearTimeout(timeout)
        }
    }, [activeIndex, autoplay, autoplayInterval, loop, next, paused, reducedMotion, safeSlideCount])

    const canGoPrevious = safeSlideCount > 1 && (loop || activeIndex > 0)

    const canGoNext = safeSlideCount > 1 && (loop || activeIndex < safeSlideCount - 1)

    return {
        activeIndex,
        direction,

        canGoPrevious,
        canGoNext,

        previous,
        next,
        goTo,

        pause,
        resume,

        interactionProps: {
            onPointerEnter,
            onPointerLeave,

            onFocusCapture,
            onBlurCapture,
        },
    }
}
