import type { StorefrontFilterOptions, StorefrontProductFilters } from '@/types/storefront'
import { SlidersHorizontal, X } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
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

    const triggerRef = useRef<HTMLButtonElement>(null)
    const dialogRef = useRef<HTMLDivElement>(null)
    const closeButtonRef = useRef<HTMLButtonElement>(null)

    useEffect(() => {
        if (!open) {
            return
        }

        const returnFocusElement = triggerRef.current
        const previousOverflow = document.body.style.overflow

        document.body.style.overflow = 'hidden'

        const focusFrame = window.requestAnimationFrame(() => {
            closeButtonRef.current?.focus()
        })

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                event.preventDefault()
                setOpen(false)

                return
            }

            if (event.key !== 'Tab') {
                return
            }

            const dialog = dialogRef.current

            if (!dialog) {
                return
            }

            const focusableElements = dialog.querySelectorAll<HTMLElement>(
                [
                    'a[href]',
                    'button:not([disabled])',
                    'input:not([disabled])',
                    'select:not([disabled])',
                    'textarea:not([disabled])',
                    '[tabindex]:not([tabindex="-1"])',
                ].join(','),
            )

            if (focusableElements.length === 0) {
                event.preventDefault()
                dialog.focus()

                return
            }

            const first = focusableElements[0]
            const last = focusableElements[focusableElements.length - 1]

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault()
                last.focus()

                return
            }

            if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault()
                first.focus()
            }
        }

        document.addEventListener('keydown', handleKeyDown)

        return () => {
            window.cancelAnimationFrame(focusFrame)

            document.body.style.overflow = previousOverflow

            document.removeEventListener('keydown', handleKeyDown)

            returnFocusElement?.focus()
        }
    }, [open])

    return (
        <>
            <button
                ref={triggerRef}
                type="button"
                onClick={() => setOpen(true)}
                className="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-3 text-sm font-medium text-neutral-800 transition hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 sm:w-auto lg:hidden"
                aria-haspopup="dialog"
                aria-expanded={open}
                aria-controls="storefront-mobile-filters"
            >
                <SlidersHorizontal className="size-4 shrink-0" aria-hidden="true" />

                <span>Filters</span>
            </button>

            {open && (
                <div className="fixed inset-0 z-50 lg:hidden" role="presentation">
                    <button
                        type="button"
                        className="absolute inset-0 cursor-default bg-black/40"
                        onClick={() => setOpen(false)}
                        aria-label="Close filters"
                    />

                    <div
                        ref={dialogRef}
                        id="storefront-mobile-filters"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="storefront-mobile-filter-title"
                        tabIndex={-1}
                        className="absolute inset-y-0 left-0 flex w-[min(90vw,360px)] min-w-0 flex-col overflow-hidden bg-white shadow-xl"
                    >
                        <div className="flex min-h-16 shrink-0 items-center justify-between gap-3 border-b border-neutral-200 px-4">
                            <h2
                                id="storefront-mobile-filter-title"
                                className="min-w-0 break-words font-semibold text-neutral-950"
                            >
                                Filters
                            </h2>

                            <button
                                ref={closeButtonRef}
                                type="button"
                                onClick={() => setOpen(false)}
                                className="inline-flex size-11 shrink-0 items-center justify-center rounded-md text-neutral-700 transition hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
                                aria-label="Close filters"
                            >
                                <X className="size-5" aria-hidden="true" />
                            </button>
                        </div>

                        <div className="min-h-0 min-w-0 flex-1 overflow-y-auto overscroll-contain p-4">
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
