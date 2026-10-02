import { useEffect, useState } from 'react'

function getVisibleColumns(desktopColumns: number): number {
    if (typeof window === 'undefined') {
        return 1
    }

    const width = window.innerWidth

    if (width < 640) {
        return 1
    }

    if (width < 768) {
        return Math.min(desktopColumns, 2)
    }

    if (width < 1024) {
        return Math.min(desktopColumns, 3)
    }

    if (width < 1280) {
        return Math.min(desktopColumns, 4)
    }

    return desktopColumns
}

export function useCategoryCarouselColumns(desktopColumns: number): number {
    const [visibleColumns, setVisibleColumns] = useState(() => getVisibleColumns(desktopColumns))

    useEffect(() => {
        const handleResize = () => {
            setVisibleColumns(getVisibleColumns(desktopColumns))
        }

        window.addEventListener('resize', handleResize)

        return () => {
            window.removeEventListener('resize', handleResize)
        }
    }, [desktopColumns])

    return visibleColumns
}
