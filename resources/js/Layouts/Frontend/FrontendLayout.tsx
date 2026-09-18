import StorefrontFooter from '@/Components/Frontend/Navigation/StorefrontFooter'
import StorefrontHeader from '@/Components/Frontend/Navigation/StorefrontHeader'
import type { PropsWithChildren } from 'react'

export default function FrontendLayout({ children }: PropsWithChildren) {
    return (
        <div className="flex min-h-screen w-full min-w-0 flex-col overflow-x-clip bg-white text-neutral-950">
            <StorefrontHeader />

            <main className="w-full min-w-0 flex-1">{children}</main>

            <StorefrontFooter />
        </div>
    )
}
