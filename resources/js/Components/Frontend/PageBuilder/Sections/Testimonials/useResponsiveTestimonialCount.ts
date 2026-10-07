import { useSyncExternalStore } from 'react'

export type ResponsiveTestimonialCount = 1 | 2 | 3

const TABLET_QUERY = '(min-width: 768px)'

const DESKTOP_QUERY = '(min-width: 1280px)'

function subscribe(callback: () => void): () => void {
    if (typeof window === 'undefined') {
        return () => undefined
    }

    const tablet = window.matchMedia(TABLET_QUERY)

    const desktop = window.matchMedia(DESKTOP_QUERY)

    tablet.addEventListener('change', callback)

    desktop.addEventListener('change', callback)

    return () => {
        tablet.removeEventListener('change', callback)

        desktop.removeEventListener('change', callback)
    }
}

function getSnapshot(): ResponsiveTestimonialCount {
    if (typeof window === 'undefined') {
        return 1
    }

    if (window.matchMedia(DESKTOP_QUERY).matches) {
        return 3
    }

    if (window.matchMedia(TABLET_QUERY).matches) {
        return 2
    }

    return 1
}

function getServerSnapshot(): ResponsiveTestimonialCount {
    return 1
}

export default function useResponsiveTestimonialCount(): ResponsiveTestimonialCount {
    return useSyncExternalStore(subscribe, getSnapshot, getServerSnapshot)
}
