import { useSyncExternalStore } from 'react'

const QUERY = '(prefers-reduced-motion: reduce)'

function subscribe(callback: () => void): () => void {
    if (typeof window === 'undefined') {
        return () => undefined
    }

    const mediaQuery = window.matchMedia(QUERY)

    mediaQuery.addEventListener('change', callback)

    return () => {
        mediaQuery.removeEventListener('change', callback)
    }
}

function getSnapshot(): boolean {
    if (typeof window === 'undefined') {
        return false
    }

    return window.matchMedia(QUERY).matches
}

function getServerSnapshot(): boolean {
    return false
}

export default function usePrefersReducedMotion(): boolean {
    return useSyncExternalStore(subscribe, getSnapshot, getServerSnapshot)
}
