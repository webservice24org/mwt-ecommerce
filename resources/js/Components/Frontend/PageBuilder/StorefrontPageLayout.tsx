import type { PropsWithChildren, ReactNode } from 'react'

import type { StorefrontPageLayout as StorefrontPageLayoutType } from '@/types/storefront-page'

interface Props extends PropsWithChildren {
    layout: StorefrontPageLayoutType
    sidebar?: ReactNode
}

export default function StorefrontPageLayout({ layout, sidebar, children }: Props) {
    if (layout === 'full_width') {
        return <div className="w-full min-w-0">{children}</div>
    }

    const sidebarContent = sidebar ?? <DefaultPageSidebar />

    if (layout === 'left_sidebar') {
        return (
            <div className="mx-auto grid w-full max-w-7xl min-w-0 gap-8 px-4 py-6 sm:px-6 lg:grid-cols-[280px_minmax(0,1fr)] lg:px-8">
                <aside className="min-w-0">{sidebarContent}</aside>

                <div className="min-w-0">{children}</div>
            </div>
        )
    }

    return (
        <div className="mx-auto grid w-full max-w-7xl min-w-0 gap-8 px-4 py-6 sm:px-6 lg:grid-cols-[minmax(0,1fr)_280px] lg:px-8">
            <div className="min-w-0">{children}</div>

            <aside className="min-w-0">{sidebarContent}</aside>
        </div>
    )
}

function DefaultPageSidebar() {
    return (
        <div className="rounded-xl border border-neutral-200 bg-white p-5">
            <h2 className="text-sm font-semibold text-neutral-900">Sidebar</h2>

            <p className="mt-2 text-sm leading-6 text-neutral-500">
                Sidebar content can be configured here in a future Page Builder step.
            </p>
        </div>
    )
}
