import type { ReactNode } from 'react'

interface Props {
    typeLabel: string
    templateLabel: string
    description: string
    category: string
    inserting: boolean
    disabled: boolean
    preview: ReactNode
    onInsert: () => void
}

export default function SectionLibraryCard({
    typeLabel,
    templateLabel,
    description,
    category,
    inserting,
    disabled,
    preview,
    onInsert,
}: Props) {
    return (
        <article className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm transition hover:border-neutral-300 hover:shadow-md dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-700">
            <div className="flex aspect-[16/9] items-center justify-center bg-neutral-100 p-6 dark:bg-neutral-900">
                {preview}
            </div>

            <div className="p-5">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                        <h3 className="font-semibold text-neutral-950 dark:text-neutral-100">
                            {templateLabel}
                        </h3>

                        <p className="mt-1 text-xs font-medium text-neutral-500 dark:text-neutral-400">
                            {typeLabel}
                        </p>
                    </div>

                    <span className="shrink-0 rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-medium text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                        {category}
                    </span>
                </div>

                <p className="mt-3 line-clamp-2 min-h-10 text-sm leading-5 text-neutral-500 dark:text-neutral-400">
                    {description}
                </p>

                <div className="mt-5 flex items-center justify-end">
                    <button
                        type="button"
                        onClick={onInsert}
                        disabled={disabled}
                        aria-busy={inserting}
                        className="inline-flex items-center justify-center gap-2 rounded-lg bg-neutral-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-neutral-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-neutral-300"
                    >
                        <span aria-hidden="true">+</span>

                        {inserting ? 'Inserting...' : 'Insert Section'}
                    </button>
                </div>
            </div>
        </article>
    )
}
