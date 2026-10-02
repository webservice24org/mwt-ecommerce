import { useCallback, useEffect, useState } from 'react'

interface Options {
    pageCount: number
    autoplay: boolean
    autoplayDelay: number
}

interface CategoryCarouselState {
    activePage: number
    direction: 1 | -1
    nextPage: () => void
    previousPage: () => void
    goToPage: (index: number) => void
    pause: () => void
    resume: () => void
}

const MIN_AUTOPLAY_DELAY = 1000

function normalizeIndex(index: number, pageCount: number): number {
    if (pageCount <= 0) {
        return 0
    }

    return ((index % pageCount) + pageCount) % pageCount
}

export function useCategoryCarousel({
    pageCount,
    autoplay,
    autoplayDelay,
}: Options): CategoryCarouselState {
    const [storedActivePage, setStoredActivePage] = useState(0)

    const [direction, setDirection] = useState<1 | -1>(1)

    const [paused, setPaused] = useState(false)

    const [timerVersion, setTimerVersion] = useState(0)

    const activePage = normalizeIndex(storedActivePage, pageCount)

    const navigateRelative = useCallback(
        (offset: 1 | -1, manual: boolean) => {
            if (pageCount <= 1) {
                return
            }

            setDirection(offset)

            setStoredActivePage((current) =>
                normalizeIndex(normalizeIndex(current, pageCount) + offset, pageCount),
            )

            if (manual) {
                setTimerVersion((version) => version + 1)
            }
        },
        [pageCount],
    )

    const nextPage = useCallback(() => {
        navigateRelative(1, true)
    }, [navigateRelative])

    const previousPage = useCallback(() => {
        navigateRelative(-1, true)
    }, [navigateRelative])

    const goToPage = useCallback(
        (index: number) => {
            if (pageCount <= 1) {
                return
            }

            const target = normalizeIndex(index, pageCount)

            const currentPage = normalizeIndex(storedActivePage, pageCount)

            if (target === currentPage) {
                return
            }

            setDirection(target > currentPage ? 1 : -1)

            setStoredActivePage(target)

            setTimerVersion((version) => version + 1)
        },
        [pageCount, storedActivePage],
    )

    const pause = useCallback(() => {
        setPaused(true)
    }, [])

    const resume = useCallback(() => {
        setPaused(false)
    }, [])

    useEffect(() => {
        if (!autoplay || paused || pageCount <= 1) {
            return
        }

        const delay = Math.max(autoplayDelay, MIN_AUTOPLAY_DELAY)

        const timer = window.setInterval(() => {
            navigateRelative(1, false)
        }, delay)

        return () => {
            window.clearInterval(timer)
        }
    }, [autoplay, autoplayDelay, navigateRelative, pageCount, paused, timerVersion])

    return {
        activePage,
        direction,
        nextPage,
        previousPage,
        goToPage,
        pause,
        resume,
    }
}
