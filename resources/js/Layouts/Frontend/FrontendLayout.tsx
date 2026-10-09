import { usePage } from '@inertiajs/react'
import type { PropsWithChildren } from 'react'

import StorefrontFooter from '@/Components/Frontend/FooterBuilder/StorefrontFooter'
import StorefrontHeader from '@/Components/Frontend/Navigation/StorefrontHeader'
import type { PageProps } from '@/types'
import type { StorefrontFooterData } from '@/types/footer-builder'

type StorefrontLayoutPageProps = PageProps<{
    storefrontFooter?: StorefrontFooterData | null
}>

export default function FrontendLayout({ children }: PropsWithChildren) {
    const { storefrontFooter } = usePage<StorefrontLayoutPageProps>().props

    return (
        <div className="isolate flex min-h-screen w-full min-w-0 flex-col overflow-x-clip bg-white text-neutral-950">
            <StorefrontHeader />

            <div className="w-full min-w-0 flex-1">{children}</div>

            <StorefrontFooter footer={storefrontFooter} />
        </div>
    )
}
