import type { StorefrontBreadcrumbItem } from '@/types/storefront'
import { Link } from '@inertiajs/react'
import { ChevronRight } from 'lucide-react'

interface StorefrontBreadcrumbsProps {
    items: StorefrontBreadcrumbItem[]
}

export default function StorefrontBreadcrumbs({ items }: StorefrontBreadcrumbsProps) {
    if (items.length === 0) {
        return null
    }

    return (
        <nav aria-label="Breadcrumb" className="min-w-0">
            <ol className="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 text-sm text-neutral-500">
                {items.map((item, index) => {
                    const current = index === items.length - 1

                    return (
                        <li
                            key={`${item.label}-${index}`}
                            className="flex min-w-0 items-center gap-2"
                        >
                            {index > 0 && (
                                <ChevronRight
                                    className="h-4 w-4 shrink-0 text-neutral-400"
                                    aria-hidden="true"
                                />
                            )}

                            {current || !item.href ? (
                                <span
                                    className={
                                        current
                                            ? 'min-w-0 break-words font-medium text-neutral-900'
                                            : 'min-w-0 break-words'
                                    }
                                    aria-current={current ? 'page' : undefined}
                                >
                                    {item.label}
                                </span>
                            ) : (
                                <Link
                                    href={item.href}
                                    className="min-w-0 break-words rounded-sm transition hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2"
                                >
                                    {item.label}
                                </Link>
                            )}
                        </li>
                    )
                })}
            </ol>
        </nav>
    )
}
