import { Link } from '@inertiajs/react'

type ReportKey =
    | 'sales'
    | 'products'

interface ReportNavigationProps {
    active: ReportKey
}

interface ReportNavigationItem {
    key: ReportKey
    label: string
    href: string
}

export default function ReportNavigation({
    active,
}: ReportNavigationProps) {
    const items:
        ReportNavigationItem[] = [
            {
                key: 'sales',
                label:
                    'Sales Report',
                href: route(
                    'admin.reports.sales.index',
                ),
            },
            {
                key: 'products',
                label:
                    'Product Performance',
                href: route(
                    'admin.reports.products.index',
                ),
            },
        ]

    return (
        <nav
            aria-label="Reporting"
            className="overflow-x-auto"
        >
            <div className="flex min-w-max gap-1 rounded-lg border border-neutral-200 bg-neutral-50 p-1">
                {items.map(
                    (
                        item,
                    ) => {
                        const isActive =
                            item.key ===
                            active

                        return (
                            <Link
                                key={
                                    item.key
                                }
                                href={
                                    item.href
                                }
                                aria-current={
                                    isActive
                                        ? 'page'
                                        : undefined
                                }
                                className={
                                    isActive
                                        ? 'inline-flex min-h-9 items-center justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-neutral-950 shadow-sm ring-1 ring-neutral-200'
                                        : 'inline-flex min-h-9 items-center justify-center rounded-md px-3 py-2 text-sm font-medium text-neutral-600 transition hover:bg-white hover:text-neutral-950'
                                }
                            >
                                {
                                    item.label
                                }
                            </Link>
                        )
                    },
                )}
            </div>
        </nav>
    )
}