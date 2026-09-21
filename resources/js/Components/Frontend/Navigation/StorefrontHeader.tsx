import { Link } from '@inertiajs/react'
import { Menu, Search, ShoppingBag, User } from 'lucide-react'
import { useCallback, useRef, useState } from 'react'
import StorefrontMobileNav from './StorefrontMobileNav'

export default function StorefrontHeader() {
    const [mobileOpen, setMobileOpen] = useState(false)
    const menuButtonRef = useRef<HTMLButtonElement>(null)

    const closeMobileNavigation = useCallback(() => {
        setMobileOpen(false)
    }, [])

    return (
        <>
            <header className="relative z-40 border-b border-neutral-200 bg-white">
                <div className="mx-auto flex h-16 w-full max-w-7xl min-w-0 items-center gap-2 px-4 sm:gap-4 sm:px-6 lg:px-8">
                    <button
                        ref={menuButtonRef}
                        type="button"
                        className="inline-flex size-10 shrink-0 items-center justify-center rounded-md text-neutral-700 transition hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 lg:hidden"
                        aria-label="Open navigation"
                        aria-expanded={mobileOpen}
                        aria-controls="storefront-mobile-navigation"
                        onClick={() => setMobileOpen(true)}
                    >
                        <Menu className="size-5" aria-hidden="true" />
                    </button>

                    <Link
                        href="/"
                        className="min-w-0 flex-1 truncate text-base font-bold tracking-tight text-neutral-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 sm:flex-none sm:text-lg"
                    >
                        Storefront
                    </Link>

                    <nav
                        className="hidden items-center gap-6 lg:flex"
                        aria-label="Primary navigation"
                    >
                        <Link
                            href="/"
                            className="text-sm font-medium text-neutral-700 transition hover:text-neutral-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
                        >
                            Home
                        </Link>

                        <Link
                            href="/products"
                            className="text-sm font-medium text-neutral-700 transition hover:text-neutral-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
                        >
                            Shop
                        </Link>
                    </nav>

                    <div className="ml-auto flex shrink-0 items-center gap-0.5 sm:gap-1">
                        <button
                            type="button"
                            className="hidden size-10 items-center justify-center rounded-md text-neutral-700 transition hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 sm:inline-flex"
                            aria-label="Search"
                            title="Search will be added in a later storefront step"
                        >
                            <Search className="size-5" aria-hidden="true" />
                        </button>

                        <Link
                            href="/dashboard"
                            className="inline-flex size-10 items-center justify-center rounded-md text-neutral-700 transition hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
                            aria-label="Account"
                        >
                            <User className="size-5" aria-hidden="true" />
                        </Link>

                        <button
                            type="button"
                            className="inline-flex size-10 items-center justify-center rounded-md text-neutral-700 transition hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
                            aria-label="Shopping cart"
                            title="Cart will be added in a later phase"
                        >
                            <ShoppingBag className="size-5" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </header>

            <StorefrontMobileNav
                open={mobileOpen}
                onClose={closeMobileNavigation}
                returnFocusRef={menuButtonRef}
            />
        </>
    )
}
