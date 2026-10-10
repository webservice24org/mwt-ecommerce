import { usePage } from '@inertiajs/react'
import type { PropsWithChildren } from 'react'

import StorefrontFooter from '@/Components/Frontend/FooterBuilder/StorefrontFooter'
import StorefrontHeader from '@/Components/Frontend/HeaderBuilder/StorefrontHeader'
import type { PageProps } from '@/types'
import type { StorefrontFooterData } from '@/types/footer-builder'
import type { StorefrontHeaderData } from '@/types/header-builder'
import type { StorefrontWishlist } from '@/types/storefront'

type StorefrontLayoutPageProps = PageProps<{
    storefrontHeader?: StorefrontHeaderData | null
    storefrontFooter?: StorefrontFooterData | null
    storefrontWishlist?: StorefrontWishlist
}>

export default function FrontendLayout({ children }: PropsWithChildren) {
    const { storefrontHeader, storefrontFooter, storefrontWishlist } =
        usePage<StorefrontLayoutPageProps>().props

    return (
        <div className="isolate flex min-h-screen w-full min-w-0 flex-col overflow-x-clip bg-white text-neutral-950">
            <StorefrontHeader
                key={storefrontWishlist?.count ?? 0}
                header={storefrontHeader}
                wishlist={storefrontWishlist}
            />

            <div className="w-full min-w-0 flex-1">{children}</div>

            <StorefrontFooter footer={storefrontFooter} />
        </div>
    )
}
