import { useCallback, useEffect, useState } from 'react'

interface Options {
    slideCount: number
    autoplay: boolean
    autoplayDelay: number
}

interface HeroSliderState {
    activeIndex: number
    direction: 1 | -1

    goToSlide: (index: number) => void

    nextSlide: () => void
    previousSlide: () => void

    pause: () => void
    resume: () => void
}

const MIN_AUTOPLAY_DELAY = 1000

function normalizeIndex(index: number, slideCount: number): number {
    if (slideCount <= 0) {
        return 0
    }

    return ((index % slideCount) + slideCount) % slideCount
}

export function useHeroSlider({ slideCount, autoplay, autoplayDelay }: Options): HeroSliderState {
    const [storedActiveIndex, setStoredActiveIndex] = useState(0)

    const [direction, setDirection] = useState<1 | -1>(1)

    const [paused, setPaused] = useState(false)

    const [timerVersion, setTimerVersion] = useState(0)

    /*
     * Keep the public index valid without
     * synchronously repairing state inside
     * an effect.
     */
    const activeIndex = normalizeIndex(storedActiveIndex, slideCount)

    const navigateRelative = useCallback(
        (offset: 1 | -1, manual: boolean) => {
            if (slideCount <= 1) {
                return
            }

            setDirection(offset)

            setStoredActiveIndex((current) =>
                normalizeIndex(normalizeIndex(current, slideCount) + offset, slideCount),
            )

            if (manual) {
                setTimerVersion((version) => version + 1)
            }
        },
        [slideCount],
    )

    const nextSlide = useCallback(() => {
        navigateRelative(1, true)
    }, [navigateRelative])

    const previousSlide = useCallback(() => {
        navigateRelative(-1, true)
    }, [navigateRelative])

    const goToSlide = useCallback(
        (index: number) => {
            if (slideCount <= 1) {
                return
            }

            const targetIndex = normalizeIndex(index, slideCount)

            setStoredActiveIndex((current) => {
                const currentIndex = normalizeIndex(current, slideCount)

                if (targetIndex === currentIndex) {
                    return current
                }

                setDirection(targetIndex > currentIndex ? 1 : -1)

                return targetIndex
            })

            setTimerVersion((version) => version + 1)
        },
        [slideCount],
    )

    const pause = useCallback(() => {
        setPaused(true)
    }, [])

    const resume = useCallback(() => {
        setPaused(false)
    }, [])

    useEffect(() => {
        if (!autoplay || paused || slideCount <= 1) {
            return
        }

        const delay = Math.max(autoplayDelay, MIN_AUTOPLAY_DELAY)

        const timer = window.setInterval(() => {
            navigateRelative(1, false)
        }, delay)

        return () => {
            window.clearInterval(timer)
        }
    }, [autoplay, autoplayDelay, navigateRelative, paused, slideCount, timerVersion])

    return {
        activeIndex,
        direction,

        goToSlide,
        nextSlide,
        previousSlide,

        pause,
        resume,
    }
}
