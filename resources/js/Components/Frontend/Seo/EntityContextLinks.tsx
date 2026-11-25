import { Link } from '@inertiajs/react'

export type EntityContextLink = {
    key: string
    label: string
    href: string
}

type Props = {
    title: string
    items: EntityContextLink[]
    maxItems?: number
}

export default function EntityContextLinks({
    title,
    items,
    maxItems = 8,
}: Props) {
    const visible =
        items
            .filter(
                (
                    item,
                ) =>
                    item.label.trim() !==
                    ''
                &&
                    item.href.trim() !==
                    '',
            )
            .slice(
                0,
                maxItems,
            )

    if (visible.length === 0) {
        return null
    }

    return (
        <section
            className="mt-5 rounded-xl border border-neutral-200 bg-neutral-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-900/40"
            aria-labelledby="entity-context-links-title"
        >
            <h2
                id="entity-context-links-title"
                className="text-sm font-semibold text-neutral-900 dark:text-neutral-100"
            >
                {title}
            </h2>

            <ul className="mt-3 flex flex-wrap gap-2">
                {visible.map(
                    (
                        item,
                    ) => (
                        <li
                            key={
                                item.key
                            }
                        >
                            <Link
                                href={
                                    item.href
                                }
                                className="inline-flex rounded-full border border-neutral-200 bg-white px-3 py-1.5 text-sm font-medium text-neutral-700 transition hover:border-neutral-400 hover:text-neutral-950 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-300 dark:hover:border-neutral-500 dark:hover:text-white"
                            >
                                {
                                    item.label
                                }
                            </Link>
                        </li>
                    ),
                )}
            </ul>
        </section>
    )
}
