import MegaMenuHeader from '@/Components/Frontend/HeaderBuilder/MegaMenuHeader'
import type { HeaderTemplateKey, StorefrontHeaderData } from '@/types/header-builder'
import type { StorefrontCart, StorefrontWishlist } from '@/types/storefront'
import type { ReactNode } from 'react'

interface Props {
    header?: StorefrontHeaderData | null
    wishlist?: StorefrontWishlist
    cart?: StorefrontCart
}

type HeaderRendererProps = {
    header: StorefrontHeaderData
    wishlist?: StorefrontWishlist
    cart?: StorefrontCart
}

type HeaderRenderer = (props: HeaderRendererProps) => ReactNode

const headerRenderers: Partial<Record<HeaderTemplateKey, HeaderRenderer>> = {
    mega_menu: ({ header, wishlist, cart }) => (
        <MegaMenuHeader config={header.config} wishlist={wishlist} cart={cart} />
    ),
}

export default function StorefrontHeader({ header, wishlist, cart }: Props) {
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
        cart,
    })
}
