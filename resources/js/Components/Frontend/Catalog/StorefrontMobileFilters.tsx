import type { StorefrontFilterOptions, StorefrontProductFilters } from '@/types/storefront'
import { SlidersHorizontal, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import StorefrontFilterPanel from './StorefrontFilterPanel'

interface StorefrontMobileFiltersProps {
    filters: StorefrontProductFilters
    options: StorefrontFilterOptions
    showCategories?: boolean
    onBrandChange: (slug: string | null) => void
    onCategoryChange: (slug: string | null) => void
    onPriceChange: (minPrice: number | null, maxPrice: number | null) => void
    onAttributeChange: (attributeSlug: string, valueSlug: string | null) => void
}

export default function StorefrontMobileFilters({
    filters,
    options,
    showCategories = true,
    onBrandChange,
    onCategoryChange,
    onPriceChange,
    onAttributeChange,
}: StorefrontMobileFiltersProps) {
    const [open, setOpen] = useState(false)

    useEffect(() => {
        if (!open) {
            return
        }

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false)
            }
        }

        window.addEventListener('keydown', handleKeyDown)

        return () => {
            window.removeEventListener('keydown', handleKeyDown)
        }
    }, [open])

    return (
        <>
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="inline-flex h-10 items-center gap-2 rounded-lg border border-neutral-300 bg-white px-3 text-sm font-medium text-neutral-800 lg:hidden"
                aria-haspopup="dialog"
                aria-expanded={open}
            >
                <SlidersHorizontal className="h-4 w-4" aria-hidden="true" />
                Filters
            </button>

            {open && (
                <div
                    className="fixed inset-0 z-50 lg:hidden"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="storefront-mobile-filter-title"
                >
                    <button
                        type="button"
                        className="absolute inset-0 bg-black/40"
                        onClick={() => setOpen(false)}
                        aria-label="Close filters"
                    />

                    <div className="absolute inset-y-0 left-0 flex w-[min(90vw,360px)] flex-col bg-white shadow-xl">
                        <div className="flex items-center justify-between border-b px-4 py-4">
                            <h2 id="storefront-mobile-filter-title" className="font-semibold">
                                Filters
                            </h2>

                            <button
                                type="button"
                                onClick={() => setOpen(false)}
                                className="rounded-md p-2 hover:bg-neutral-100"
                                aria-label="Close filters"
                            >
                                <X className="h-5 w-5" aria-hidden="true" />
                            </button>
                        </div>

                        <div className="min-h-0 flex-1 overflow-y-auto p-4">
                            <StorefrontFilterPanel
                                filters={filters}
                                options={options}
                                showCategories={showCategories}
                                onBrandChange={onBrandChange}
                                onCategoryChange={onCategoryChange}
                                onPriceChange={onPriceChange}
                                onAttributeChange={onAttributeChange}
                            />
                        </div>
                    </div>
                </div>
            )}
        </>
    )
}
