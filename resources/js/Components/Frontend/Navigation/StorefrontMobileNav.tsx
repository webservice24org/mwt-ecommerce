import { Link } from '@inertiajs/react'
import { X } from 'lucide-react'
import { type RefObject, useEffect, useRef } from 'react'

interface StorefrontMobileNavProps {
    open: boolean
    onClose: () => void
    returnFocusRef: RefObject<HTMLButtonElement | null>
}

export default function StorefrontMobileNav({
    open,
    onClose,
    returnFocusRef,
}: StorefrontMobileNavProps) {
    const dialogRef = useRef<HTMLDivElement>(null)
    const closeButtonRef = useRef<HTMLButtonElement>(null)

    useEffect(() => {
        if (!open) {
            return
        }

        /*
         * Capture the element that opened this drawer.
         *
         * We intentionally store the current ref value here instead of
         * accessing returnFocusRef.current inside the cleanup function.
         * This ensures focus returns to the same trigger element and avoids
         * the react-hooks/exhaustive-deps warning about mutable ref values.
         */
        const returnFocusElement = returnFocusRef.current
        const previousOverflow = document.body.style.overflow

        /*
         * Prevent the page behind the mobile navigation from scrolling
         * while the drawer is open.
         */
        document.body.style.overflow = 'hidden'

        /*
         * Wait until the drawer has rendered before moving keyboard focus
         * to its close button.
         */
        const focusFrame = window.requestAnimationFrame(() => {
            closeButtonRef.current?.focus()
        })

        const handleKeyDown = (event: KeyboardEvent) => {
            /*
             * Escape closes the mobile navigation.
             */
            if (event.key === 'Escape') {
                event.preventDefault()
                onClose()

                return
            }

            /*
             * Only Tab/Shift+Tab need focus-trap handling.
             */
            if (event.key !== 'Tab') {
                return
            }

            const dialog = dialogRef.current

            if (!dialog) {
                return
            }

            const focusableElements = dialog.querySelectorAll<HTMLElement>(
                ['a[href]', 'button:not([disabled])', '[tabindex]:not([tabindex="-1"])'].join(','),
            )

            /*
             * Defensive fallback in case the drawer temporarily contains
             * no interactive controls.
             */
            if (focusableElements.length === 0) {
                event.preventDefault()
                dialog.focus()

                return
            }

            const first = focusableElements[0]
            const last = focusableElements[focusableElements.length - 1]

            /*
             * Shift+Tab from the first element wraps to the last.
             */
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault()
                last.focus()

                return
            }

            /*
             * Tab from the last element wraps to the first.
             */
            if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault()
                first.focus()
            }
        }

        document.addEventListener('keydown', handleKeyDown)

        return () => {
            window.cancelAnimationFrame(focusFrame)

            /*
             * Restore whatever body overflow value existed before the
             * navigation opened.
             */
            document.body.style.overflow = previousOverflow

            document.removeEventListener('keydown', handleKeyDown)

            /*
             * Restore focus to the exact menu trigger captured when this
             * drawer instance opened.
             */
            returnFocusElement?.focus()
        }
    }, [open, onClose, returnFocusRef])

    if (!open) {
        return null
    }

    return (
        <div className="fixed inset-0 z-50 lg:hidden" role="presentation">
            <button
                type="button"
                className="absolute inset-0 cursor-default bg-black/40"
                aria-label="Close navigation"
                onClick={onClose}
            />

            <div
                ref={dialogRef}
                id="storefront-mobile-navigation"
                role="dialog"
                aria-modal="true"
                aria-label="Mobile navigation"
                tabIndex={-1}
                className="relative flex h-full w-80 max-w-[85vw] flex-col overflow-y-auto bg-white shadow-xl"
            >
                <div className="flex min-h-16 items-center justify-between border-b border-neutral-200 px-4">
                    <span className="font-semibold text-neutral-950">Menu</span>

                    <button
                        ref={closeButtonRef}
                        type="button"
                        className="inline-flex size-11 items-center justify-center rounded-md text-neutral-700 transition hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
                        aria-label="Close navigation"
                        onClick={onClose}
                    >
                        <X className="size-5" aria-hidden="true" />
                    </button>
                </div>

                <nav className="flex flex-col gap-1 p-4" aria-label="Mobile navigation">
                    <Link
                        href="/"
                        onClick={onClose}
                        className="flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-neutral-800 transition hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
                    >
                        Home
                    </Link>

                    <Link
                        href="/products"
                        onClick={onClose}
                        className="flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-neutral-800 transition hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
                    >
                        Shop
                    </Link>

                    <Link
                        href="/dashboard"
                        onClick={onClose}
                        className="flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-neutral-800 transition hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
                    >
                        Account
                    </Link>
                </nav>
            </div>
        </div>
    )
}
