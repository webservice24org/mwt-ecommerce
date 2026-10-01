import { useEffect, useState } from 'react'

const QUERY = '(prefers-reduced-motion: reduce)'

function getInitialValue(): boolean {
    if (typeof window === 'undefined') {
        return false
    }

    return window.matchMedia(QUERY).matches
}

export function usePrefersReducedMotion(): boolean {
    const [prefersReducedMotion, setPrefersReducedMotion] = useState(getInitialValue)

    useEffect(() => {
        const media = window.matchMedia(QUERY)

        const handleChange = (event: MediaQueryListEvent) => {
            setPrefersReducedMotion(event.matches)
        }

        media.addEventListener('change', handleChange)

        return () => {
            media.removeEventListener('change', handleChange)
        }
    }, [])

    return prefersReducedMotion
}
