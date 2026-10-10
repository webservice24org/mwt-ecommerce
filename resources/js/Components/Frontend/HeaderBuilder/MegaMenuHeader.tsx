import { Link, router } from '@inertiajs/react'
import { ChevronDown, Heart, Menu, Search, ShoppingBag, UserRound, X } from 'lucide-react'
import { type FormEvent, useEffect, useRef, useState } from 'react'

import type { HeaderConfig } from '@/types/header-builder'
import type { StorefrontSearchSuggestion, StorefrontWishlist } from '@/types/storefront'
import {
    STOREFRONT_WISHLIST_CHANGED,
    type StorefrontWishlistChangedDetail,
} from '@/lib/storefrontWishlist'

interface Props {
    config: HeaderConfig
    wishlist?: StorefrontWishlist
}

export default function MegaMenuHeader({ config, wishlist }: Props) {
    const [searchQuery, setSearchQuery] = useState('')

    const [searchOpen, setSearchOpen] = useState(false)

    const [megaMenuOpen, setMegaMenuOpen] = useState(false)

    const [accountOpen, setAccountOpen] = useState(false)

    const [mobileMenuOpen, setMobileMenuOpen] = useState(false)

    const [wishlistCount, setWishlistCount] = useState(wishlist?.count ?? 0)

    useEffect(() => {
        const handleWishlistChanged = (event: Event) => {
            const customEvent = event as CustomEvent<StorefrontWishlistChangedDetail>

            setWishlistCount(customEvent.detail.count)
        }

        window.addEventListener(STOREFRONT_WISHLIST_CHANGED, handleWishlistChanged)

        return () => {
            window.removeEventListener(STOREFRONT_WISHLIST_CHANGED, handleWishlistChanged)
        }
    }, [])

    const closeTransientMenus = () => {
        setSearchOpen(false)
        setMegaMenuOpen(false)
        setAccountOpen(false)
    }

    const handleSearchToggle = () => {
        const next = !searchOpen

        closeTransientMenus()
        setSearchOpen(next)
    }

    const handleMegaMenuToggle = () => {
        const next = !megaMenuOpen

        closeTransientMenus()
        setMegaMenuOpen(next)
    }

    const handleAccountToggle = () => {
        const next = !accountOpen

        closeTransientMenus()
        setAccountOpen(next)
    }

    const handleMobileMenuToggle = () => {
        const next = !mobileMenuOpen

        closeTransientMenus()
        setMobileMenuOpen(next)
    }

    return (
        <header className="relative z-40 w-full bg-neutral-950 text-white">
            <AnnouncementBar config={config} />

            <MainHeader
                config={config}
                wishlistCount={wishlistCount}
                searchQuery={searchQuery}
                searchOpen={searchOpen}
                accountOpen={accountOpen}
                mobileMenuOpen={mobileMenuOpen}
                onSearchQueryChange={setSearchQuery}
                onSearchToggle={handleSearchToggle}
                onAccountToggle={handleAccountToggle}
                onMobileMenuToggle={handleMobileMenuToggle}
            />

            <DesktopNavigation
                config={config}
                megaMenuOpen={megaMenuOpen}
                onMegaMenuToggle={handleMegaMenuToggle}
            />

            {megaMenuOpen && <MegaMenu config={config} />}

            {mobileMenuOpen && (
                <MobileMenu
                    config={config}
                    searchQuery={searchQuery}
                    onSearchQueryChange={setSearchQuery}
                    onClose={() => setMobileMenuOpen(false)}
                />
            )}
        </header>
    )
}

function AnnouncementBar({ config }: { config: HeaderConfig }) {
    if (!config.announcement.enabled) {
        return null
    }

    return (
        <div className="border-b border-indigo-500/30 bg-gradient-to-r from-indigo-700 via-violet-700 to-indigo-800 text-white">
            <div className="mx-auto flex min-h-9 w-full max-w-7xl items-center justify-between gap-6 px-4 py-2 sm:px-6 lg:px-8">
                <div className="flex min-w-0 items-center gap-2">
                    {config.announcement.badge && (
                        <span className="shrink-0 rounded-full bg-white/20 px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wide">
                            {config.announcement.badge}
                        </span>
                    )}

                    <p className="min-w-0 truncate text-[11px] font-medium">
                        {config.announcement.message}

                        {config.announcement.promo_code && (
                            <>
                                {' Use code '}

                                <strong className="font-extrabold underline">
                                    {config.announcement.promo_code}
                                </strong>
                            </>
                        )}

                        {config.announcement.promo_suffix && (
                            <> {config.announcement.promo_suffix}</>
                        )}
                    </p>
                </div>

                <div className="hidden shrink-0 items-center gap-6 text-[10px] text-indigo-100 md:flex">
                    {config.announcement.links
                        .filter((link) => link.label.trim() !== '')
                        .map((link, index) => (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url || '#'}
                                className="whitespace-nowrap transition hover:text-white"
                            >
                                {link.label}
                            </Link>
                        ))}

                    {config.announcement.currency_label && (
                        <span className="inline-flex items-center gap-1 whitespace-nowrap">
                            {config.announcement.currency_label}

                            <ChevronDown className="h-3 w-3" aria-hidden="true" />
                        </span>
                    )}
                </div>
            </div>
        </div>
    )
}

function MainHeader({
    config,
    wishlistCount,
    searchQuery,
    searchOpen,
    accountOpen,
    mobileMenuOpen,
    onSearchQueryChange,
    onSearchToggle,
    onAccountToggle,
    onMobileMenuToggle,
}: {
    config: HeaderConfig
    wishlistCount: number
    searchQuery: string
    searchOpen: boolean
    accountOpen: boolean
    mobileMenuOpen: boolean
    onSearchQueryChange: (value: string) => void
    onSearchToggle: () => void
    onAccountToggle: () => void
    onMobileMenuToggle: () => void
}) {
    return (
        <div className="relative z-30 border-b border-neutral-800 bg-neutral-900">
            <div className="mx-auto flex min-h-16 w-full max-w-7xl items-center gap-2 px-3 sm:min-h-20 sm:gap-4 sm:px-6 lg:gap-5 lg:px-8">
                <button
                    type="button"
                    aria-label={mobileMenuOpen ? 'Close navigation' : 'Open navigation'}
                    aria-expanded={mobileMenuOpen}
                    onClick={onMobileMenuToggle}
                    className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-neutral-400 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 lg:hidden"
                >
                    {mobileMenuOpen ? (
                        <X className="h-5 w-5" aria-hidden="true" />
                    ) : (
                        <Menu className="h-5 w-5" aria-hidden="true" />
                    )}
                </button>

                <Brand config={config} />

                <div className="hidden min-w-0 flex-1 md:block">
                    <SearchBox
                        config={config}
                        value={searchQuery}
                        open={searchOpen}
                        onChange={onSearchQueryChange}
                        onToggle={onSearchToggle}
                    />
                </div>

                <HeaderActions
                    config={config}
                    wishlistCount={wishlistCount}
                    accountOpen={accountOpen}
                    onAccountToggle={onAccountToggle}
                />
            </div>
        </div>
    )
}

function Brand({ config }: { config: HeaderConfig }) {
    return (
        <Link
            href={config.brand.home_url || '/'}
            className="flex min-w-0 shrink-0 items-center gap-2.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
        >
            {config.brand.logo_url ? (
                <span className="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white sm:h-10 sm:w-10">
                    <img
                        src={config.brand.logo_url}
                        alt={config.brand.name}
                        className="h-full w-full object-contain"
                    />
                </span>
            ) : (
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-sm font-black text-white shadow-lg shadow-indigo-600/20 sm:h-10 sm:w-10">
                    {config.brand.fallback_mark || 'A'}
                </span>
            )}

            <span className="hidden whitespace-nowrap text-xl font-black tracking-tight text-white lg:inline">
                {config.brand.name}

                <span className="text-indigo-500">{config.brand.accent ?? ''}</span>
            </span>
        </Link>
    )
}

function SearchBox({
    config,
    value,
    open,
    onChange,
    onToggle,
}: {
    config: HeaderConfig
    value: string
    open: boolean
    onChange: (value: string) => void
    onToggle: () => void
}) {
    const [productSuggestions, setProductSuggestions] = useState<StorefrontSearchSuggestion[]>([])

    const [suggestionsLoading, setSuggestionsLoading] = useState(false)

    const [suggestionsFailed, setSuggestionsFailed] = useState(false)

    const requestController = useRef<AbortController | null>(null)

    const configuredSuggestions = config.search.trending_searches.filter(
        (item) => item.trim() !== '',
    )

    const normalizedQuery = value.trim()

    const liveSearchEnabled = config.search.suggestions_enabled && normalizedQuery.length >= 2

    useEffect(() => {
        requestController.current?.abort()

        if (!config.search.suggestions_enabled || normalizedQuery.length < 2) {
            return
        }

        const timeout = window.setTimeout(() => {
            const controller = new AbortController()

            requestController.current = controller

            setSuggestionsLoading(true)

            setSuggestionsFailed(false)

            const params = new URLSearchParams({
                q: normalizedQuery,
            })

            void fetch(`/products/search-suggestions?${params.toString()}`, {
                headers: {
                    Accept: 'application/json',
                },
                signal: controller.signal,
            })
                .then(async (response) => {
                    if (!response.ok) {
                        throw new Error('Search suggestions failed.')
                    }

                    return (await response.json()) as {
                        data: StorefrontSearchSuggestion[]
                    }
                })
                .then((payload) => {
                    if (controller.signal.aborted) {
                        return
                    }

                    setProductSuggestions(payload.data)
                })
                .catch((error: unknown) => {
                    if (controller.signal.aborted) {
                        return
                    }

                    if (error instanceof DOMException && error.name === 'AbortError') {
                        return
                    }

                    setProductSuggestions([])

                    setSuggestionsFailed(true)
                })
                .finally(() => {
                    if (!controller.signal.aborted) {
                        setSuggestionsLoading(false)
                    }
                })
        }, 250)

        return () => {
            window.clearTimeout(timeout)

            requestController.current?.abort()
        }
    }, [normalizedQuery, config.search.suggestions_enabled])

    if (!config.search.enabled) {
        return null
    }

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        if (normalizedQuery === '') {
            return
        }

        if (open) {
            onToggle()
        }

        router.get(
            '/products',
            {
                q: normalizedQuery,
            },
            {
                preserveScroll: false,
            },
        )
    }

    const searchFor = (query: string) => {
        const normalized = query.trim()

        if (normalized === '') {
            return
        }

        onChange(normalized)

        if (open) {
            onToggle()
        }

        router.get(
            '/products',
            {
                q: normalized,
            },
            {
                preserveScroll: false,
            },
        )
    }

    const productUrl = (slug: string) => `/products/${encodeURIComponent(slug)}`

    return (
        <div className="relative mx-auto max-w-xl">
            <form
                onSubmit={submit}
                className={[
                    'flex min-h-11 w-full min-w-0 items-center gap-3 rounded-xl border bg-neutral-950 px-4 transition focus-within:ring-2 focus-within:ring-indigo-500',
                    open ? 'border-indigo-500' : 'border-neutral-700 hover:border-neutral-600',
                ].join(' ')}
            >
                <Search className="h-4 w-4 shrink-0 text-neutral-500" aria-hidden="true" />

                <input
                    type="search"
                    value={value}
                    placeholder={config.search.placeholder}
                    autoComplete="off"
                    onFocus={() => {
                        if (!open) {
                            onToggle()
                        }
                    }}
                    onChange={(event) => onChange(event.target.value)}
                    className="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-white outline-none placeholder:text-neutral-500 focus:ring-0"
                />

                {value !== '' && (
                    <button
                        type="button"
                        aria-label="Clear search"
                        onClick={() => onChange('')}
                        className="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-neutral-800 hover:text-white"
                    >
                        <X className="h-3.5 w-3.5" aria-hidden="true" />
                    </button>
                )}

                <button
                    type="submit"
                    className="inline-flex min-h-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white transition hover:bg-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                    Search
                </button>
            </form>

            {open && config.search.suggestions_enabled && (
                <div className="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-xl border border-neutral-700 bg-neutral-900 shadow-2xl">
                    <div className="p-4">
                        <div className="flex items-center justify-between gap-3">
                            <p className="text-[10px] font-bold uppercase tracking-[0.12em] text-neutral-500">
                                {liveSearchEnabled
                                    ? 'Product Suggestions'
                                    : config.search.suggestion_heading}
                            </p>

                            <button
                                type="button"
                                onClick={onToggle}
                                aria-label="Close search suggestions"
                                className="inline-flex h-7 w-7 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-neutral-800 hover:text-white"
                            >
                                <X className="h-3.5 w-3.5" aria-hidden="true" />
                            </button>
                        </div>

                        {!liveSearchEnabled ? (
                            configuredSuggestions.length > 0 ? (
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {configuredSuggestions.map((item, index) => (
                                        <button
                                            key={`${item}-${index}`}
                                            type="button"
                                            onClick={() => searchFor(item)}
                                            className="rounded-full border border-neutral-700 bg-neutral-800 px-2.5 py-1.5 text-[10px] font-medium text-neutral-300 transition hover:border-indigo-500 hover:text-white"
                                        >
                                            {item}
                                        </button>
                                    ))}
                                </div>
                            ) : (
                                <p className="mt-3 text-xs text-neutral-500">
                                    No trending searches configured.
                                </p>
                            )
                        ) : suggestionsLoading ? (
                            <div className="mt-3 space-y-2">
                                {[0, 1, 2].map((item) => (
                                    <div
                                        key={item}
                                        className="h-10 animate-pulse rounded-lg bg-neutral-800"
                                    />
                                ))}
                            </div>
                        ) : suggestionsFailed ? (
                            <p className="mt-3 text-xs text-neutral-500">
                                Suggestions are temporarily unavailable.
                            </p>
                        ) : productSuggestions.length > 0 ? (
                            <div className="mt-3 space-y-1">
                                {productSuggestions.map((product) => (
                                    <Link
                                        key={product.id}
                                        href={productUrl(product.slug)}
                                        onClick={() => {
                                            if (open) {
                                                onToggle()
                                            }
                                        }}
                                        className="flex min-h-10 items-center rounded-lg px-3 text-sm font-medium text-neutral-200 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                    >
                                        <Search
                                            className="mr-3 h-3.5 w-3.5 shrink-0 text-neutral-500"
                                            aria-hidden="true"
                                        />

                                        <span className="min-w-0 truncate">{product.name}</span>
                                    </Link>
                                ))}

                                <button
                                    type="button"
                                    onClick={() => searchFor(value)}
                                    className="mt-2 flex min-h-10 w-full items-center justify-center rounded-lg border border-neutral-700 px-3 text-xs font-semibold text-neutral-300 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                >
                                    View all results
                                </button>
                            </div>
                        ) : (
                            <div className="mt-3">
                                <p className="text-xs text-neutral-500">
                                    No matching products found.
                                </p>

                                <button
                                    type="button"
                                    onClick={() => searchFor(value)}
                                    className="mt-3 text-xs font-semibold text-indigo-400 transition hover:text-indigo-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                >
                                    Search all products
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    )
}

function HeaderActions({
    config,
    wishlistCount,
    accountOpen,
    onAccountToggle,
}: {
    config: HeaderConfig
    wishlistCount: number
    accountOpen: boolean
    onAccountToggle: () => void
}) {
    const wishlistUrl = config.actions.wishlist.url.trim()

    const resolvedWishlistUrl =
        wishlistUrl === '' || wishlistUrl === '#' ? '/wishlist' : wishlistUrl
    return (
        <div className="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
            {config.actions.wishlist.enabled && (
                <Link
                    href={resolvedWishlistUrl}
                    aria-label={wishlistCount > 0 ? `Wishlist, ${wishlistCount} items` : 'Wishlist'}
                    className="relative hidden h-10 w-10 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-950 text-neutral-300 transition hover:border-neutral-700 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 sm:inline-flex"
                >
                    <Heart
                        className={[
                            'h-4 w-4',
                            wishlistCount > 0 ? 'fill-current text-indigo-400' : '',
                        ].join(' ')}
                        aria-hidden="true"
                    />

                    {wishlistCount > 0 && (
                        <span
                            className="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-indigo-600 px-1 text-[8px] font-bold text-white"
                            aria-hidden="true"
                        >
                            {wishlistCount > 99 ? '99+' : wishlistCount}
                        </span>
                    )}
                </Link>
            )}

            {config.actions.cart.enabled && (
                <Link
                    href={config.actions.cart.url || '#'}
                    aria-label="Shopping cart"
                    className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-950 text-neutral-300 transition hover:border-neutral-700 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                >
                    <ShoppingBag className="h-4 w-4" aria-hidden="true" />
                </Link>
            )}

            {config.actions.account.enabled && (
                <div className="relative hidden sm:block">
                    <button
                        type="button"
                        aria-label="Account menu"
                        aria-expanded={accountOpen}
                        onClick={onAccountToggle}
                        className={[
                            'inline-flex min-h-10 items-center gap-2 rounded-xl border bg-neutral-950 px-2.5 text-neutral-300 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500',
                            accountOpen
                                ? 'border-indigo-500'
                                : 'border-neutral-800 hover:border-neutral-700',
                        ].join(' ')}
                    >
                        <UserRound className="h-4 w-4" aria-hidden="true" />

                        <ChevronDown
                            className={[
                                'h-3.5 w-3.5 text-neutral-500 transition-transform',
                                accountOpen ? 'rotate-180' : '',
                            ].join(' ')}
                            aria-hidden="true"
                        />
                    </button>

                    {accountOpen && <AccountMenu config={config} onClose={onAccountToggle} />}
                </div>
            )}
        </div>
    )
}

function AccountMenu({ config, onClose }: { config: HeaderConfig; onClose: () => void }) {
    const links = config.actions.account.menu_links.filter((link) => link.label.trim() !== '')

    return (
        <div className="absolute right-0 top-full z-50 mt-2 w-64 overflow-hidden rounded-xl border border-neutral-700 bg-neutral-900 shadow-2xl">
            <div className="flex items-center justify-between gap-3 border-b border-neutral-800 p-4">
                <div>
                    <p className="text-xs font-semibold text-white">Account</p>

                    <p className="mt-1 text-[10px] text-neutral-500">Customer menu</p>
                </div>

                <button
                    type="button"
                    onClick={onClose}
                    aria-label="Close account menu"
                    className="inline-flex h-7 w-7 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-neutral-800 hover:text-white"
                >
                    <X className="h-3.5 w-3.5" aria-hidden="true" />
                </button>
            </div>

            <div className="p-2">
                {links.map((link, index) => (
                    <Link
                        key={`${link.label}-${index}`}
                        href={link.url || '#'}
                        onClick={onClose}
                        className="flex min-h-9 items-center rounded-lg px-3 text-xs font-medium text-neutral-300 transition hover:bg-neutral-800 hover:text-white"
                    >
                        {link.label}
                    </Link>
                ))}

                {links.length === 0 && (
                    <p className="px-3 py-2 text-xs text-neutral-500">
                        No account links configured.
                    </p>
                )}
            </div>

            <div className="grid grid-cols-2 gap-2 border-t border-neutral-800 p-2">
                <Link
                    href={config.actions.account.guest_login_url || '#'}
                    className="flex min-h-9 items-center justify-center rounded-lg border border-neutral-700 px-2 text-xs font-semibold text-neutral-300 transition hover:bg-neutral-800"
                >
                    Sign in
                </Link>

                <Link
                    href={config.actions.account.guest_register_url || '#'}
                    className="flex min-h-9 items-center justify-center rounded-lg bg-white px-2 text-xs font-bold text-neutral-950 transition hover:bg-neutral-200"
                >
                    Register
                </Link>
            </div>
        </div>
    )
}

function DesktopNavigation({
    config,
    megaMenuOpen,
    onMegaMenuToggle,
}: {
    config: HeaderConfig
    megaMenuOpen: boolean
    onMegaMenuToggle: () => void
}) {
    if (!config.navigation.enabled) {
        return null
    }

    return (
        <div className="hidden border-b border-neutral-800 bg-neutral-950 lg:block">
            <div className="mx-auto flex min-h-12 w-full max-w-7xl items-center gap-7 px-4 sm:px-6 lg:px-8">
                {config.mega_menu.enabled && (
                    <button
                        type="button"
                        aria-expanded={megaMenuOpen}
                        onClick={onMegaMenuToggle}
                        className={[
                            'inline-flex min-h-9 shrink-0 items-center gap-2 rounded-lg px-3 text-xs font-bold text-white transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500',
                            megaMenuOpen ? 'bg-violet-600' : 'bg-indigo-600 hover:bg-indigo-500',
                        ].join(' ')}
                    >
                        {megaMenuOpen ? (
                            <X className="h-4 w-4" aria-hidden="true" />
                        ) : (
                            <Menu className="h-4 w-4" aria-hidden="true" />
                        )}

                        {config.navigation.mega_menu_label}

                        <ChevronDown
                            className={[
                                'h-3.5 w-3.5 transition-transform',
                                megaMenuOpen ? 'rotate-180' : '',
                            ].join(' ')}
                            aria-hidden="true"
                        />
                    </button>
                )}

                <nav
                    aria-label="Primary navigation"
                    className="flex min-w-0 flex-1 items-center gap-7 overflow-hidden"
                >
                    {config.navigation.links
                        .filter((link) => link.label.trim() !== '')
                        .map((link, index) => (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url || '#'}
                                className={[
                                    'shrink-0 whitespace-nowrap text-xs font-semibold transition',
                                    link.style === 'highlight'
                                        ? 'text-amber-400 hover:text-amber-300'
                                        : 'text-neutral-300 hover:text-white',
                                ].join(' ')}
                            >
                                {link.label}
                            </Link>
                        ))}
                </nav>
            </div>
        </div>
    )
}

function MegaMenu({ config }: { config: HeaderConfig }) {
    if (!config.navigation.enabled || !config.mega_menu.enabled) {
        return null
    }

    return (
        <div className="hidden border-b border-neutral-800 bg-neutral-900 lg:block">
            <div className="mx-auto grid w-full max-w-7xl grid-cols-12 gap-8 px-4 py-7 sm:px-6 lg:px-8">
                <div className="col-span-8 grid min-w-0 grid-cols-2 gap-x-8 gap-y-6 xl:grid-cols-3">
                    {config.mega_menu.groups
                        .filter(
                            (group) =>
                                group.heading.trim() !== '' ||
                                group.links.some((link) => link.label.trim() !== ''),
                        )
                        .map((group, groupIndex) => (
                            <section key={`${group.heading}-${groupIndex}`} className="min-w-0">
                                {group.heading && (
                                    <h2 className="text-xs font-bold uppercase tracking-[0.08em] text-white">
                                        {group.heading}
                                    </h2>
                                )}

                                <div className="mt-3 space-y-2.5">
                                    {group.links
                                        .filter((link) => link.label.trim() !== '')
                                        .map((link, linkIndex) => (
                                            <Link
                                                key={`${link.label}-${linkIndex}`}
                                                href={link.url || '#'}
                                                className="block truncate text-xs text-neutral-400 transition hover:text-white"
                                            >
                                                {link.label}
                                            </Link>
                                        ))}
                                </div>
                            </section>
                        ))}
                </div>

                {config.mega_menu.promotion.enabled && (
                    <div className="col-span-4 min-w-0">
                        <Link
                            href={config.mega_menu.promotion.url || '#'}
                            className="flex h-full min-h-44 flex-col justify-between rounded-2xl border border-indigo-500/20 bg-gradient-to-br from-indigo-950 via-violet-950 to-neutral-950 p-5 transition hover:border-indigo-400/40"
                        >
                            <div>
                                {config.mega_menu.promotion.eyebrow && (
                                    <p className="text-[10px] font-bold uppercase tracking-[0.12em] text-indigo-400">
                                        {config.mega_menu.promotion.eyebrow}
                                    </p>
                                )}

                                <p className="mt-2 max-w-xs text-lg font-black leading-tight text-white">
                                    {config.mega_menu.promotion.title}
                                </p>
                            </div>

                            {config.mega_menu.promotion.button_label && (
                                <span className="mt-5 inline-flex min-h-9 w-fit items-center rounded-lg bg-white px-3 text-xs font-bold text-neutral-950">
                                    {config.mega_menu.promotion.button_label}
                                </span>
                            )}
                        </Link>
                    </div>
                )}
            </div>
        </div>
    )
}

function MobileMenu({
    config,
    searchQuery,
    onSearchQueryChange,
    onClose,
}: {
    config: HeaderConfig
    searchQuery: string
    onSearchQueryChange: (value: string) => void
    onClose: () => void
}) {
    const submitSearch = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        const query = searchQuery.trim()

        if (query === '') {
            return
        }

        onClose()

        router.get(
            '/products',
            {
                q: query,
            },
            {
                preserveScroll: false,
            },
        )
    }

    return (
        <div className="border-b border-neutral-800 bg-neutral-950 p-4 lg:hidden">
            <div className="mx-auto w-full max-w-7xl">
                <div className="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <p className="text-[10px] font-bold uppercase tracking-[0.1em] text-neutral-500">
                            Menu
                        </p>

                        <p className="mt-1 text-sm font-semibold text-white">Browse store</p>
                    </div>

                    <button
                        type="button"
                        aria-label="Close navigation"
                        onClick={onClose}
                        className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-neutral-800 bg-neutral-900 text-neutral-400 transition hover:text-white"
                    >
                        <X className="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>

                {config.mobile.search_enabled && (
                    <form
                        onSubmit={submitSearch}
                        className="mb-4 flex min-h-11 items-center gap-2 rounded-xl border border-neutral-800 bg-neutral-900 px-3 focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/20"
                    >
                        <Search className="h-4 w-4 shrink-0 text-neutral-500" aria-hidden="true" />

                        <input
                            type="search"
                            value={searchQuery}
                            placeholder={config.mobile.search_placeholder}
                            autoComplete="off"
                            onChange={(event) => onSearchQueryChange(event.target.value)}
                            className="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-white outline-none placeholder:text-neutral-500 focus:ring-0"
                        />

                        <button
                            type="submit"
                            aria-label="Search products"
                            className="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white transition hover:bg-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                        >
                            <Search className="h-4 w-4" aria-hidden="true" />
                        </button>
                    </form>
                )}

                <nav aria-label="Mobile navigation" className="space-y-2">
                    {config.mobile.menu_links
                        .filter((link) => link.label.trim() !== '')
                        .map((link, index) => (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url || '#'}
                                onClick={onClose}
                                className={[
                                    'flex min-h-11 items-center justify-between rounded-xl px-3 text-sm font-semibold transition',
                                    link.style === 'primary'
                                        ? 'bg-indigo-600 text-white hover:bg-indigo-500'
                                        : link.style === 'highlight'
                                          ? 'border border-amber-500/30 bg-amber-500/10 text-amber-400 hover:bg-amber-500/20'
                                          : 'border border-neutral-800 bg-neutral-900 text-neutral-300 hover:bg-neutral-800',
                                ].join(' ')}
                            >
                                <span className="min-w-0 truncate">{link.label}</span>

                                <ChevronDown
                                    className="-rotate-90 h-3.5 w-3.5 shrink-0 opacity-60"
                                    aria-hidden="true"
                                />
                            </Link>
                        ))}
                </nav>

                {config.actions.account.enabled && (
                    <div className="mt-4 grid grid-cols-2 gap-2 border-t border-neutral-800 pt-4">
                        <Link
                            href={config.actions.account.guest_login_url || '#'}
                            onClick={onClose}
                            className="flex min-h-10 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-900 px-3 text-xs font-semibold text-neutral-300 transition hover:bg-neutral-800"
                        >
                            Sign in
                        </Link>

                        <Link
                            href={config.actions.account.guest_register_url || '#'}
                            onClick={onClose}
                            className="flex min-h-10 items-center justify-center rounded-xl bg-white px-3 text-xs font-bold text-neutral-950 transition hover:bg-neutral-200"
                        >
                            Register
                        </Link>
                    </div>
                )}
            </div>
        </div>
    )
}
