import type {
    PropsWithChildren,
} from 'react'

import type {
    StorefrontSectionLayout as StorefrontSectionLayoutType,
} from '@/types/storefront-page'

interface Props extends PropsWithChildren {
    layout:
        | StorefrontSectionLayoutType
        | null
        | undefined
}

export default function StorefrontSectionLayout({
    layout,
    children,
}: Props) {
    const width =
        normalizeSectionWidth(
            layout,
        )

    if (width === 'full') {
        return (
            <div className="w-full min-w-0">
                {children}
            </div>
        )
    }

    return (
        <div className="mx-auto w-full max-w-7xl min-w-0 px-4 sm:px-6 lg:px-8">
            {children}
        </div>
    )
}

function normalizeSectionWidth(
    layout:
        | StorefrontSectionLayoutType
        | null
        | undefined,
): 'container' | 'full' {
    return layout?.width ===
        'full'
        ? 'full'
        : 'container'
}