import type { FormDataConvertible } from '@inertiajs/core'
import { router } from '@inertiajs/react'
import { useEffect, useMemo, useState } from 'react'

import type { SectionDefinition } from '@/types/page-builder'

import SectionLibraryCard from './SectionLibraryCard'

interface Props {
    open: boolean
    pageId: number
    sectionDefinitions: SectionDefinition[]
    onClose: () => void
}

interface LibraryItem {
    id: string
    type: string
    typeLabel: string
    template: string
    templateLabel: string
    description: string
    category: string
    defaultConfig: Record<string, FormDataConvertible>
}

export default function SectionLibraryDialog({ open, pageId, sectionDefinitions, onClose }: Props) {
    const [search, setSearch] = useState('')
    const [activeCategory, setActiveCategory] = useState<string>('all')
    const [insertingId, setInsertingId] = useState<string | null>(null)

    const items = useMemo<LibraryItem[]>(
        () =>
            sectionDefinitions.flatMap((definition) =>
                definition.templates.map((template) => ({
                    id: `${definition.type}:${template.key}`,
                    type: definition.type,
                    typeLabel: definition.label,
                    template: template.key,
                    templateLabel: template.label,
                    description: template.description,
                    category: template.category,
                    defaultConfig: definition.default_config,
                })),
            ),
        [sectionDefinitions],
    )

    const categories = useMemo(
        () => Array.from(new Set(items.map((item) => item.category))).sort(),
        [items],
    )

    const filteredItems = useMemo(() => {
        const query = search.trim().toLowerCase()

        return items.filter((item) => {
            const matchesCategory = activeCategory === 'all' || item.category === activeCategory

            if (!matchesCategory) {
                return false
            }

            if (!query) {
                return true
            }

            return [
                item.typeLabel,
                item.templateLabel,
                item.type,
                item.template,
                item.description,
                item.category,
            ].some((value) => value.toLowerCase().includes(query))
        })
    }, [activeCategory, items, search])

    useEffect(() => {
        if (!open) {
            return
        }

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape' && insertingId === null) {
                onClose()
            }
        }

        document.addEventListener('keydown', handleKeyDown)

        return () => {
            document.removeEventListener('keydown', handleKeyDown)
        }
    }, [insertingId, onClose, open])

    if (!open) {
        return null
    }

    const insertSection = (item: LibraryItem) => {
        if (insertingId !== null) {
            return
        }

        setInsertingId(item.id)

        router.post(
            route('admin.pages.sections.store', pageId),
            {
                type: item.type,
                template: item.template,
                config: item.defaultConfig,
                is_enabled: true,
            },
            {
                preserveScroll: true,

                onSuccess: () => {
                    setSearch('')
                    setActiveCategory('all')
                    onClose()
                },

                onFinish: () => {
                    setInsertingId(null)
                },
            },
        )
    }

    const resetLibrary = () => {
        setSearch('')
        setActiveCategory('all')
        setInsertingId(null)
    }

    const close = () => {
        if (insertingId !== null) {
            return
        }

        resetLibrary()
        onClose()
    }

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="section-library-title"
            onMouseDown={(event) => {
                if (event.target === event.currentTarget) {
                    close()
                }
            }}
        >
            <div className="flex max-h-[90vh] w-full max-w-7xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-neutral-950">
                <header className="flex items-start justify-between gap-4 border-b border-neutral-200 px-6 py-5 dark:border-neutral-800">
                    <div>
                        <h2
                            id="section-library-title"
                            className="text-xl font-semibold text-neutral-950 dark:text-neutral-100"
                        >
                            Section Library
                        </h2>

                        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            Choose a pre-designed section to add to this page.
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={close}
                        disabled={insertingId !== null}
                        aria-label="Close Section Library"
                        className="inline-flex h-9 w-9 items-center justify-center rounded-lg text-xl text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900 disabled:opacity-50 dark:hover:bg-neutral-900 dark:hover:text-neutral-100"
                    >
                        ×
                    </button>
                </header>

                <div className="border-b border-neutral-200 px-6 py-4 dark:border-neutral-800">
                    <label htmlFor="section-library-search" className="sr-only">
                        Search section designs
                    </label>

                    <input
                        id="section-library-search"
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search sections..."
                        autoFocus
                        className="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:ring-neutral-800"
                    />

                    <div
                        className="mt-4 flex gap-2 overflow-x-auto pb-1"
                        role="group"
                        aria-label="Filter sections by category"
                    >
                        <FilterButton
                            active={activeCategory === 'all'}
                            onClick={() => setActiveCategory('all')}
                        >
                            All
                        </FilterButton>

                        {categories.map((category) => (
                            <FilterButton
                                key={category}
                                active={activeCategory === category}
                                onClick={() => setActiveCategory(category)}
                            >
                                {category}
                            </FilterButton>
                        ))}
                    </div>
                </div>

                <div className="min-h-0 flex-1 overflow-y-auto p-6">
                    {filteredItems.length > 0 ? (
                        <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                            {filteredItems.map((item) => (
                                <SectionLibraryCard
                                    key={item.id}
                                    typeLabel={item.typeLabel}
                                    templateLabel={item.templateLabel}
                                    description={item.description}
                                    category={item.category}
                                    inserting={insertingId === item.id}
                                    disabled={insertingId !== null}
                                    onInsert={() => insertSection(item)}
                                    preview={
                                        <SectionPreviewPlaceholder
                                            typeLabel={item.typeLabel}
                                            templateLabel={item.templateLabel}
                                        />
                                    }
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="flex min-h-64 items-center justify-center rounded-xl border border-dashed border-neutral-300 p-8 text-center dark:border-neutral-700">
                            <div>
                                <p className="font-medium text-neutral-800 dark:text-neutral-200">
                                    No sections found
                                </p>

                                <p className="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                                    Try another search or section category.
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    )
}

function SectionPreviewPlaceholder({
    typeLabel,
    templateLabel,
}: {
    typeLabel: string
    templateLabel: string
}) {
    return (
        <div className="w-full max-w-xs">
            <div className="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-950">
                <div className="h-2 w-20 rounded-full bg-neutral-200 dark:bg-neutral-800" />

                <div className="mt-3 h-4 w-3/4 rounded bg-neutral-300 dark:bg-neutral-700" />

                <div className="mt-2 h-2 w-full rounded-full bg-neutral-200 dark:bg-neutral-800" />

                <div className="mt-1.5 h-2 w-2/3 rounded-full bg-neutral-200 dark:bg-neutral-800" />

                <div className="mt-4 grid grid-cols-3 gap-2">
                    <div className="aspect-square rounded bg-neutral-100 dark:bg-neutral-900" />
                    <div className="aspect-square rounded bg-neutral-100 dark:bg-neutral-900" />
                    <div className="aspect-square rounded bg-neutral-100 dark:bg-neutral-900" />
                </div>
            </div>

            <p className="mt-3 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                {typeLabel} · {templateLabel}
            </p>
        </div>
    )
}

function FilterButton({
    active,
    children,
    onClick,
}: {
    active: boolean
    children: string
    onClick: () => void
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-pressed={active}
            className={[
                'shrink-0 rounded-full border px-3.5 py-1.5 text-sm font-medium transition',
                active
                    ? 'border-neutral-900 bg-neutral-900 text-white dark:border-neutral-100 dark:bg-neutral-100 dark:text-neutral-900'
                    : 'border-neutral-300 bg-white text-neutral-600 hover:border-neutral-400 hover:text-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-300 dark:hover:border-neutral-600 dark:hover:text-neutral-100',
            ].join(' ')}
        >
            {children}
        </button>
    )
}
