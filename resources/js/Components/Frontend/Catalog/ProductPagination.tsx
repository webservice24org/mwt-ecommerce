import type { PaginationLink } from '@/types/storefront'
import { Link } from '@inertiajs/react'

interface ProductPaginationProps {
    links: PaginationLink[]
}

export default function ProductPagination({ links }: ProductPaginationProps) {
    if (links.length <= 3) {
        return null
    }

    return (
        <nav
            className="mt-10 flex flex-wrap items-center justify-center gap-2"
            aria-label="Product pagination"
        >
            {links.map((link, index) => {
                const label = cleanLabel(link.label)

                if (link.url === null) {
                    return (
                        <span
                            key={`${link.label}-${index}`}
                            className="inline-flex min-h-10 min-w-10 cursor-not-allowed items-center justify-center rounded-md border border-neutral-200 px-3 text-sm text-neutral-400"
                            aria-disabled="true"
                        >
                            {label}
                        </span>
                    )
                }

                return (
                    <Link
                        key={`${link.label}-${index}`}
                        href={link.url}
                        preserveScroll
                        className={[
                            'inline-flex min-h-10 min-w-10 items-center justify-center rounded-md border px-3 text-sm transition-colors',
                            link.active
                                ? 'border-neutral-950 bg-neutral-950 text-white'
                                : 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-400 hover:text-neutral-950',
                        ].join(' ')}
                        aria-current={link.active ? 'page' : undefined}
                    >
                        {label}
                    </Link>
                )
            })}
        </nav>
    )
}

function cleanLabel(label: string): string {
    return label
        .replace('&laquo;', '«')
        .replace('&raquo;', '»')
        .replace('Previous', 'Previous')
        .replace('Next', 'Next')
}
