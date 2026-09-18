import StorefrontMobileNav from '@/Components/Frontend/Navigation/StorefrontMobileNav'
import { Link, usePage } from '@inertiajs/react'
import { Menu, Search, ShoppingBag, UserRound } from 'lucide-react'
import { useState } from 'react'

interface NavigationItem {
    label: string
    href: string
    active: (url: string) => boolean
}

const navigation: NavigationItem[] = [
    {
        label: 'Home',
        href: '/',
        active: (url) => url === '/',
    },
    {
        label: 'Shop',
        href: '/products',
        active: (url) => url === '/products' || url.startsWith('/products?'),
    },
]

export default function StorefrontHeader() {
    const { url } = usePage()
    const [mobileOpen, setMobileOpen] = useState(false)

    return (
        <>
            <header className="sticky top-0 z-40 w-full min-w-0 border-b border-neutral-200 bg-white/95 backdrop-blur">
                <div className="mx-auto flex h-16 w-full min-w-0 max-w-7xl items-center gap-2 px-4 sm:gap-4 sm:px-6 lg:px-8">
                    <button
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
                        className="min-w-0 shrink truncate text-base font-bold tracking-tight text-neutral-950 sm:text-lg"
                    >
                        MWT Ecommerce
                    </Link>

                    <nav
                        className="ml-8 hidden items-center gap-1 lg:flex"
                        aria-label="Main navigation"
                    >
                        {navigation.map((item) => {
                            const isActive = item.active(url)

                            return (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    aria-current={isActive ? 'page' : undefined}
                                    className={[
                                        'rounded-md px-3 py-2 text-sm font-medium transition',
                                        isActive
                                            ? 'bg-neutral-100 text-neutral-950'
                                            : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-950',
                                    ].join(' ')}
                                >
                                    {item.label}
                                </Link>
                            )
                        })}
                    </nav>

                    <div className="ml-auto flex shrink-0 items-center gap-0.5 sm:gap-1">
                        <button
                            type="button"
                            className="inline-flex size-10 items-center justify-center rounded-md text-neutral-700 transition hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
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
                            <UserRound className="size-5" aria-hidden="true" />
                        </Link>

                        <button
                            type="button"
                            className="relative inline-flex size-10 items-center justify-center rounded-md text-neutral-700 transition hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
                            aria-label="Shopping cart"
                            title="Cart will be added in the cart phase"
                        >
                            <ShoppingBag className="size-5" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </header>

            <StorefrontMobileNav
                open={mobileOpen}
                currentUrl={url}
                navigation={navigation}
                onClose={() => setMobileOpen(false)}
            />
        </>
    )
}

export type { NavigationItem }
