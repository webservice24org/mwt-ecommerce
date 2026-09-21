import { Link } from '@inertiajs/react'

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface ProductPaginationProps {
    links: PaginationLink[]
}

export default function ProductPagination({ links }: ProductPaginationProps) {
    if (links.length <= 3) {
        return null
    }

    return (
        <nav className="min-w-0 border-t border-neutral-200 pt-6" aria-label="Product pagination">
            <div className="flex min-w-0 flex-wrap items-center justify-center gap-2">
                {links.map((link, index) => {
                    const label = decodePaginationLabel(link.label)
                    const isPrevious = index === 0
                    const isNext = index === links.length - 1

                    const accessibleLabel = isPrevious
                        ? 'Go to previous page'
                        : isNext
                          ? 'Go to next page'
                          : `Go to page ${label}`

                    if (!link.url) {
                        const disabledLabel = isPrevious
                            ? 'Previous page unavailable'
                            : isNext
                              ? 'Next page unavailable'
                              : undefined

                        return (
                            <span
                                key={`${link.label}-${index}`}
                                className="inline-flex min-h-11 min-w-11 cursor-not-allowed items-center justify-center rounded-lg border border-neutral-200 px-3 text-sm text-neutral-400"
                                aria-disabled="true"
                                aria-label={disabledLabel}
                            >
                                {label}
                            </span>
                        )
                    }

                    if (link.active) {
                        return (
                            <span
                                key={`${link.label}-${index}`}
                                className="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-neutral-950 bg-neutral-950 px-3 text-sm font-medium text-white"
                                aria-current="page"
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
                            className="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-neutral-300 bg-white px-3 text-sm font-medium text-neutral-700 transition hover:border-neutral-400 hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2"
                            aria-label={accessibleLabel}
                        >
                            {label}
                        </Link>
                    )
                })}
            </div>
        </nav>
    )
}

function decodePaginationLabel(label: string): string {
    return label
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/&lsaquo;/g, '‹')
        .replace(/&rsaquo;/g, '›')
        .replace(/&hellip;/g, '…')
}
