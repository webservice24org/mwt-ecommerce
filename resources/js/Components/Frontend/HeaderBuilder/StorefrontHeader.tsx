import MegaMenuHeader from '@/Components/Frontend/HeaderBuilder/MegaMenuHeader'
import type { HeaderTemplateKey, StorefrontHeaderData } from '@/types/header-builder'
import type { StorefrontWishlist } from '@/types/storefront'
import type { ReactNode } from 'react'

interface Props {
    header?: StorefrontHeaderData | null

    wishlist?: StorefrontWishlist
}

type HeaderRendererProps = {
    header: StorefrontHeaderData
    wishlist?: StorefrontWishlist
}

type HeaderRenderer = (props: HeaderRendererProps) => ReactNode

const headerRenderers: Partial<Record<HeaderTemplateKey, HeaderRenderer>> = {
    mega_menu: ({ header, wishlist }) => (
        <MegaMenuHeader config={header.config} wishlist={wishlist} />
    ),
}

export default function StorefrontHeader({ header, wishlist }: Props) {
    if (header === null || header === undefined || !header.is_enabled) {
        return null
    }

    const renderer = headerRenderers[header.template]

    if (renderer === undefined) {
        return null
    }

    return renderer({
        header,
        wishlist,
    })
}
