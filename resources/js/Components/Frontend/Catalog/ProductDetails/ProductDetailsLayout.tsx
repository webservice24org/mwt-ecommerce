import type { ReactNode } from 'react'

interface ProductDetailsLayoutProps {
    media: ReactNode
    summary: ReactNode
}

export default function ProductDetailsLayout({ media, summary }: ProductDetailsLayoutProps) {
    return (
        <div className="grid min-w-0 grid-cols-1 gap-8 lg:grid-cols-2 lg:items-start lg:gap-12">
            <div className="min-w-0">{media}</div>

            <div className="min-w-0">{summary}</div>
        </div>
    )
}
