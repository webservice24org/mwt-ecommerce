import { Link, router, usePage } from '@inertiajs/react'
import { ChevronDown, Heart, Menu, Search, ShoppingBag, UserRound, X } from 'lucide-react'
import { type FormEvent, type RefObject, useEffect, useId, useRef, useState } from 'react'

import type { PageProps } from '@/types'
import type { HeaderConfig } from '@/types/header-builder'
import { jsonRequestHeaders } from '@/lib/csrf'
import { formatMoney } from '@/lib/money'
import {
    STOREFRONT_CART_CHANGED,
    type StorefrontCartChangedDetail,
    dispatchCartChanged,
} from '@/lib/storefrontCart'
import {
    STOREFRONT_WISHLIST_CHANGED,
    type StorefrontWishlistChangedDetail,
} from '@/lib/storefrontWishlist'
import type {
    StorefrontCart,
    StorefrontCartItem,
    StorefrontCategoryMenu,
    StorefrontSearchSuggestion,
    StorefrontWishlist,
} from '@/types/storefront'

interface Props {
    config: HeaderConfig
    wishlist?: StorefrontWishlist
    cart?: StorefrontCart
}

interface StorefrontAuthUser {
    id: number
    name: string
    email: string
    email_verified_at?: string | null
}

type HeaderPageProps = PageProps<{
    auth?: {
        user?: StorefrontAuthUser | null
    }
    storefrontCategoryMenu?: StorefrontCategoryMenu
}>

export default function MegaMenuHeader({ config, wishlist, cart }: Props) {
    const { auth, storefrontCategoryMenu } = usePage<HeaderPageProps>().props

    const categoryMenu = storefrontCategoryMenu ?? []
    const user = auth?.user ?? null

    const [searchQuery, setSearchQuery] = useState('')
    const [searchOpen, setSearchOpen] = useState(false)
    const [megaMenuOpen, setMegaMenuOpen] = useState(false)
    const [accountOpen, setAccountOpen] = useState(false)
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false)
    const [wishlistCount, setWishlistCount] = useState(wishlist?.count ?? 0)
    const [cartState, setCartState] = useState<StorefrontCart>(
        cart ?? {
            items: [],
            item_count: 0,
            subtotal: 0,
        },
    )
    const [cartOpen, setCartOpen] = useState(false)

    const searchPopupId = useId()
    const megaMenuId = useId()
    const accountMenuId = useId()
    const mobileMenuId = useId()
    const cartDrawerId = useId()

    const cartTriggerRef = useRef<HTMLButtonElement>(null)

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

    useEffect(() => {
        const handleCartChanged = (event: Event) => {
            const customEvent = event as CustomEvent<StorefrontCartChangedDetail>

            setCartState(customEvent.detail.cart)
        }

        window.addEventListener(STOREFRONT_CART_CHANGED, handleCartChanged)

        return () => {
            window.removeEventListener(STOREFRONT_CART_CHANGED, handleCartChanged)
        }
    }, [])

    useEffect(() => {
        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key !== 'Escape') {
                return
            }

            setSearchOpen(false)
            setMegaMenuOpen(false)
            setAccountOpen(false)
            setMobileMenuOpen(false)

            if (cartOpen) {
                setCartOpen(false)

                window.setTimeout(() => {
                    cartTriggerRef.current?.focus()
                }, 0)
            }
        }

        window.addEventListener('keydown', handleKeyDown)

        return () => {
            window.removeEventListener('keydown', handleKeyDown)
        }
    }, [cartOpen])

    useEffect(() => {
        if (!cartOpen) {
            return
        }

        const previousOverflow = document.body.style.overflow

        document.body.style.overflow = 'hidden'

        return () => {
            document.body.style.overflow = previousOverflow
        }
    }, [cartOpen])

    const closeTransientMenus = () => {
        setSearchOpen(false)
        setMegaMenuOpen(false)
        setAccountOpen(false)
        setMobileMenuOpen(false)
    }

    const closeCart = () => {
        setCartOpen(false)

        window.setTimeout(() => {
            cartTriggerRef.current?.focus()
        }, 0)
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

    const handleCartToggle = () => {
        const next = !cartOpen

        closeTransientMenus()
        setCartOpen(next)
    }

    return (
        <header className="relative z-40 w-full min-w-0 bg-neutral-950 text-white">
            <AnnouncementBar config={config} />

            <MainHeader
                config={config}
                user={user}
                wishlistCount={wishlistCount}
                cartCount={cartState.item_count}
                cartOpen={cartOpen}
                searchQuery={searchQuery}
                searchOpen={searchOpen}
                accountOpen={accountOpen}
                mobileMenuOpen={mobileMenuOpen}
                searchPopupId={searchPopupId}
                accountMenuId={accountMenuId}
                mobileMenuId={mobileMenuId}
                cartDrawerId={cartDrawerId}
                cartTriggerRef={cartTriggerRef}
                onSearchQueryChange={setSearchQuery}
                onSearchToggle={handleSearchToggle}
                onAccountToggle={handleAccountToggle}
                onMobileMenuToggle={handleMobileMenuToggle}
                onCartToggle={handleCartToggle}
            />

            <DesktopNavigation
                config={config}
                megaMenuId={megaMenuId}
                megaMenuOpen={megaMenuOpen}
                onMegaMenuToggle={handleMegaMenuToggle}
            />

            {megaMenuOpen && <MegaMenu id={megaMenuId} config={config} categories={categoryMenu} />}

            {mobileMenuOpen && (
                <MobileMenu
                    id={mobileMenuId}
                    config={config}
                    user={user}
                    categories={categoryMenu}
                    searchQuery={searchQuery}
                    onSearchQueryChange={setSearchQuery}
                    onClose={() => setMobileMenuOpen(false)}
                />
            )}

            {cartOpen && <CartDrawer id={cartDrawerId} cart={cartState} onClose={closeCart} />}
        </header>
    )
}

function AnnouncementBar({ config }: { config: HeaderConfig }) {
    if (!config.announcement.enabled) {
        return null
    }

    return (
        <div className="min-w-0 border-b border-indigo-500/30 bg-gradient-to-r from-indigo-700 via-violet-700 to-indigo-800 text-white">
            <div className="mx-auto flex min-h-9 w-full max-w-7xl min-w-0 items-center justify-between gap-3 px-2 py-2 sm:gap-6 sm:px-6 lg:px-8">
                <div className="flex min-w-0 flex-1 items-center gap-2">
                    {config.announcement.badge && (
                        <span className="shrink-0 rounded-full bg-white/20 px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wide">
                            {config.announcement.badge}
                        </span>
                    )}

                    <p
                        title={config.announcement.message}
                        className="min-w-0 flex-1 truncate text-[11px] font-medium"
                    >
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

                <div className="hidden shrink-0 items-center gap-4 text-[10px] text-indigo-100 md:flex lg:gap-6">
                    {config.announcement.links
                        .filter((link) => link.label.trim() !== '')
                        .map((link, index) => (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url || '#'}
                                title={link.label}
                                className="max-w-40 truncate whitespace-nowrap transition hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80"
                            >
                                {link.label}
                            </Link>
                        ))}

                    {config.announcement.currency_label && (
                        <span className="inline-flex max-w-28 items-center gap-1 whitespace-nowrap">
                            <span className="truncate">{config.announcement.currency_label}</span>
                            <ChevronDown className="h-3 w-3 shrink-0" aria-hidden="true" />
                        </span>
                    )}
                </div>
            </div>
        </div>
    )
}

function MainHeader({
    config,
    user,
    wishlistCount,
    cartCount,
    cartOpen,
    searchQuery,
    searchOpen,
    accountOpen,
    mobileMenuOpen,
    searchPopupId,
    accountMenuId,
    mobileMenuId,
    cartDrawerId,
    cartTriggerRef,
    onSearchQueryChange,
    onSearchToggle,
    onAccountToggle,
    onMobileMenuToggle,
    onCartToggle,
}: {
    config: HeaderConfig
    user: StorefrontAuthUser | null
    wishlistCount: number
    cartCount: number
    cartOpen: boolean
    searchQuery: string
    searchOpen: boolean
    accountOpen: boolean
    mobileMenuOpen: boolean
    searchPopupId: string
    accountMenuId: string
    mobileMenuId: string
    cartDrawerId: string
    cartTriggerRef: RefObject<HTMLButtonElement>
    onSearchQueryChange: (value: string) => void
    onSearchToggle: () => void
    onAccountToggle: () => void
    onMobileMenuToggle: () => void
    onCartToggle: () => void
}) {
    return (
        <div className="relative z-30 min-w-0 border-b border-neutral-800 bg-neutral-900">
            <div className="mx-auto flex min-h-16 w-full max-w-7xl min-w-0 items-center gap-1.5 px-2 sm:min-h-20 sm:gap-3 sm:px-6 lg:gap-5 lg:px-8">
                <button
                    type="button"
                    aria-label={mobileMenuOpen ? 'Close navigation' : 'Open navigation'}
                    aria-expanded={mobileMenuOpen}
                    aria-controls={mobileMenuId}
                    onClick={onMobileMenuToggle}
                    className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-neutral-400 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 lg:hidden"
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
                        popupId={searchPopupId}
                        value={searchQuery}
                        open={searchOpen}
                        onChange={onSearchQueryChange}
                        onToggle={onSearchToggle}
                    />
                </div>

                <HeaderActions
                    config={config}
                    user={user}
                    wishlistCount={wishlistCount}
                    cartCount={cartCount}
                    cartOpen={cartOpen}
                    accountOpen={accountOpen}
                    accountMenuId={accountMenuId}
                    cartDrawerId={cartDrawerId}
                    cartTriggerRef={cartTriggerRef}
                    onAccountToggle={onAccountToggle}
                    onCartToggle={onCartToggle}
                />
            </div>
        </div>
    )
}

function Brand({ config }: { config: HeaderConfig }) {
    return (
        <Link
            href={config.brand.home_url || '/'}
            aria-label={`${config.brand.name} home`}
            className="flex min-w-0 shrink-0 items-center gap-2.5 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
        >
            {config.brand.logo_url ? (
                <span className="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white sm:h-10 sm:w-10">
                    <img
                        src={config.brand.logo_url}
                        alt=""
                        className="h-full w-full object-contain"
                    />
                </span>
            ) : (
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-sm font-black text-white shadow-lg shadow-indigo-600/20 sm:h-10 sm:w-10">
                    {config.brand.fallback_mark || 'A'}
                </span>
            )}

            <span className="hidden max-w-48 truncate whitespace-nowrap text-xl font-black tracking-tight text-white lg:inline xl:max-w-64">
                {config.brand.name}
                <span className="text-indigo-500">{config.brand.accent ?? ''}</span>
            </span>
        </Link>
    )
}

function SearchBox({
    config,
    popupId,
    value,
    open,
    onChange,
    onToggle,
}: {
    config: HeaderConfig
    popupId: string
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
                    if (!controller.signal.aborted) {
                        setProductSuggestions(payload.data)
                    }
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

    const searchStatus = !liveSearchEnabled
        ? configuredSuggestions.length > 0
            ? `${configuredSuggestions.length} trending searches available.`
            : 'No trending searches configured.'
        : suggestionsLoading
          ? 'Loading product suggestions.'
          : suggestionsFailed
            ? 'Search suggestions are temporarily unavailable.'
            : productSuggestions.length > 0
              ? `${productSuggestions.length} product suggestions available.`
              : 'No matching products found.'

    return (
        <div className="relative mx-auto min-w-0 max-w-xl">
            <form
                role="search"
                onSubmit={submit}
                className={[
                    'flex min-h-11 w-full min-w-0 items-center gap-2 rounded-xl border bg-neutral-950 px-3 transition focus-within:ring-2 focus-within:ring-indigo-500 sm:gap-3 sm:px-4',
                    open ? 'border-indigo-500' : 'border-neutral-700 hover:border-neutral-600',
                ].join(' ')}
            >
                <Search className="h-4 w-4 shrink-0 text-neutral-500" aria-hidden="true" />

                <input
                    type="search"
                    role="combobox"
                    aria-label="Search products"
                    aria-expanded={open && config.search.suggestions_enabled}
                    aria-controls={popupId}
                    aria-autocomplete="list"
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
                        className="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                    >
                        <X className="h-3.5 w-3.5" aria-hidden="true" />
                    </button>
                )}

                <button
                    type="submit"
                    className="inline-flex min-h-9 shrink-0 items-center justify-center rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white transition hover:bg-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                    Search
                </button>
            </form>

            <span className="sr-only" role="status" aria-live="polite">
                {open && config.search.suggestions_enabled ? searchStatus : ''}
            </span>

            {open && config.search.suggestions_enabled && (
                <div
                    id={popupId}
                    role="listbox"
                    aria-label="Search suggestions"
                    className="absolute left-0 right-0 top-full z-50 mt-2 max-h-[min(28rem,70vh)] overflow-y-auto overscroll-contain rounded-xl border border-neutral-700 bg-neutral-900 shadow-2xl"
                >
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
                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
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
                                            role="option"
                                            aria-selected="false"
                                            onClick={() => searchFor(item)}
                                            className="max-w-full truncate rounded-full border border-neutral-700 bg-neutral-800 px-2.5 py-1.5 text-[10px] font-medium text-neutral-300 transition hover:border-indigo-500 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
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
                            <div className="mt-3 space-y-2" role="status" aria-live="polite">
                                <span className="sr-only">Loading search suggestions</span>

                                {[0, 1, 2].map((item) => (
                                    <div
                                        key={item}
                                        className="h-10 animate-pulse rounded-lg bg-neutral-800"
                                        aria-hidden="true"
                                    />
                                ))}
                            </div>
                        ) : suggestionsFailed ? (
                            <p className="mt-3 text-xs text-neutral-500" role="status">
                                Suggestions are temporarily unavailable.
                            </p>
                        ) : productSuggestions.length > 0 ? (
                            <div className="mt-3 space-y-1">
                                {productSuggestions.map((product) => (
                                    <Link
                                        key={product.id}
                                        href={productUrl(product.slug)}
                                        role="option"
                                        aria-selected="false"
                                        onClick={() => {
                                            if (open) {
                                                onToggle()
                                            }
                                        }}
                                        className="flex min-h-11 min-w-0 items-center rounded-lg px-3 text-sm font-medium text-neutral-200 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
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
                                    className="mt-2 flex min-h-11 w-full items-center justify-center rounded-lg border border-neutral-700 px-3 text-xs font-semibold text-neutral-300 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                >
                                    View all results
                                </button>
                            </div>
                        ) : (
                            <div className="mt-3">
                                <p className="text-xs text-neutral-500" role="status">
                                    No matching products found.
                                </p>

                                <button
                                    type="button"
                                    onClick={() => searchFor(value)}
                                    className="mt-3 min-h-10 text-xs font-semibold text-indigo-400 transition hover:text-indigo-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
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
    user,
    wishlistCount,
    cartCount,
    cartOpen,
    accountOpen,
    accountMenuId,
    cartDrawerId,
    cartTriggerRef,
    onAccountToggle,
    onCartToggle,
}: {
    config: HeaderConfig
    user: StorefrontAuthUser | null
    wishlistCount: number
    cartCount: number
    cartOpen: boolean
    accountOpen: boolean
    accountMenuId: string
    cartDrawerId: string
    cartTriggerRef: RefObject<HTMLButtonElement>
    onAccountToggle: () => void
    onCartToggle: () => void
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
                    className="relative hidden h-11 w-11 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-950 text-neutral-300 transition hover:border-neutral-700 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 sm:inline-flex"
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
                <button
                    ref={cartTriggerRef}
                    type="button"
                    onClick={onCartToggle}
                    aria-label={
                        cartCount > 0 ? `Shopping cart, ${cartCount} items` : 'Shopping cart'
                    }
                    aria-expanded={cartOpen}
                    aria-controls={cartDrawerId}
                    aria-haspopup="dialog"
                    className="relative inline-flex h-11 w-11 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-950 text-neutral-300 transition hover:border-neutral-700 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                >
                    <ShoppingBag className="h-4 w-4" aria-hidden="true" />

                    {cartCount > 0 && (
                        <span
                            className="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-emerald-500 px-1 text-[8px] font-extrabold text-neutral-950"
                            aria-hidden="true"
                        >
                            {cartCount > 99 ? '99+' : cartCount}
                        </span>
                    )}
                </button>
            )}

            {config.actions.account.enabled && (
                <div className="relative hidden sm:block">
                    <button
                        type="button"
                        aria-label={user ? `Account menu for ${user.name}` : 'Account menu'}
                        aria-expanded={accountOpen}
                        aria-controls={accountMenuId}
                        aria-haspopup="menu"
                        onClick={onAccountToggle}
                        className={[
                            'inline-flex min-h-11 items-center gap-2 rounded-xl border bg-neutral-950 px-2.5 text-neutral-300 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500',
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

                    {accountOpen && (
                        <AccountMenu
                            id={accountMenuId}
                            config={config}
                            user={user}
                            onClose={onAccountToggle}
                        />
                    )}
                </div>
            )}
        </div>
    )
}

function CartDrawer({
    id,
    cart,
    onClose,
}: {
    id: string
    cart: StorefrontCart
    onClose: () => void
}) {
    const [processingKey, setProcessingKey] = useState<string | null>(null)
    const [mutationError, setMutationError] = useState<string | null>(null)
    const drawerRef = useRef<HTMLElement | null>(null)

    useEffect(() => {
        drawerRef.current?.focus()
    }, [])

    const updateQuantity = async (item: StorefrontCartItem, quantity: number) => {
        if (processingKey !== null) {
            return
        }

        setProcessingKey(item.key)
        setMutationError(null)

        try {
            const response = await fetch(`/cart/items/${encodeURIComponent(item.key)}`, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: jsonRequestHeaders(),
                body: JSON.stringify({
                    quantity,
                }),
            })

            if (!response.ok) {
                throw new Error('Unable to update cart item.')
            }

            const nextCart = (await response.json()) as StorefrontCart

            dispatchCartChanged(nextCart)
        } catch (error: unknown) {
            setMutationError(error instanceof Error ? error.message : 'Unable to update cart item.')
        } finally {
            setProcessingKey(null)
        }
    }

    const removeItem = async (item: StorefrontCartItem) => {
        if (processingKey !== null) {
            return
        }

        setProcessingKey(item.key)
        setMutationError(null)

        try {
            const response = await fetch(`/cart/items/${encodeURIComponent(item.key)}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: jsonRequestHeaders(),
            })

            if (!response.ok) {
                throw new Error('Unable to remove cart item.')
            }

            const nextCart = (await response.json()) as StorefrontCart

            dispatchCartChanged(nextCart)
        } catch (error: unknown) {
            setMutationError(error instanceof Error ? error.message : 'Unable to remove cart item.')
        } finally {
            setProcessingKey(null)
        }
    }

    return (
        <div className="fixed inset-0 z-[100] overflow-hidden">
            <button
                type="button"
                aria-label="Close cart"
                onClick={onClose}
                className="absolute inset-0 bg-black/60"
            />

            <aside
                ref={drawerRef}
                id={id}
                role="dialog"
                aria-modal="true"
                aria-labelledby="cart-drawer-title"
                aria-describedby="cart-drawer-summary"
                tabIndex={-1}
                className="absolute right-0 top-0 flex h-full w-full max-w-md min-w-0 flex-col overflow-hidden border-l border-neutral-800 bg-neutral-950 shadow-2xl outline-none"
            >
                <div className="flex shrink-0 items-center justify-between gap-4 border-b border-neutral-800 px-4 py-4 sm:px-5">
                    <div className="min-w-0">
                        <h2
                            id="cart-drawer-title"
                            className="truncate text-sm font-bold text-white"
                        >
                            Your Cart
                        </h2>

                        <p id="cart-drawer-summary" className="mt-1 text-xs text-neutral-500">
                            {cart.item_count === 1 ? '1 item' : `${cart.item_count} items`}
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Close cart drawer"
                        className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-neutral-800 text-neutral-400 transition hover:bg-neutral-900 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                    >
                        <X className="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>

                {mutationError && (
                    <div
                        role="alert"
                        className="shrink-0 border-b border-red-500/20 bg-red-500/10 px-4 py-3 text-xs text-red-300 sm:px-5"
                    >
                        {mutationError}
                    </div>
                )}

                {cart.items.length === 0 ? (
                    <div className="flex min-h-0 flex-1 items-center justify-center overflow-y-auto px-6 py-8 text-center">
                        <div className="max-w-xs">
                            <span className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-neutral-900 text-neutral-500">
                                <ShoppingBag className="h-6 w-6" aria-hidden="true" />
                            </span>

                            <h3 className="mt-5 text-base font-semibold text-white">
                                Your cart is empty
                            </h3>

                            <p className="mt-2 text-sm leading-6 text-neutral-500">
                                Add products from the store and they will appear here.
                            </p>

                            <Link
                                href="/products"
                                onClick={onClose}
                                className="mt-5 inline-flex min-h-11 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                            >
                                Browse products
                            </Link>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 py-4 sm:px-5">
                            <ul className="space-y-4">
                                {cart.items.map((item) => {
                                    const processing = processingKey === item.key

                                    return (
                                        <li
                                            key={item.key}
                                            className="min-w-0 rounded-xl border border-neutral-800 bg-neutral-900/70 p-3"
                                        >
                                            <div className="flex min-w-0 gap-3">
                                                <Link
                                                    href={`/products/${item.slug}`}
                                                    onClick={onClose}
                                                    className="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                                    aria-label={`View ${item.name}`}
                                                >
                                                    {item.image_url ? (
                                                        <img
                                                            src={item.image_url}
                                                            alt=""
                                                            className="h-full w-full object-cover"
                                                        />
                                                    ) : (
                                                        <span className="flex h-full w-full items-center justify-center text-neutral-600">
                                                            <ShoppingBag
                                                                className="h-5 w-5"
                                                                aria-hidden="true"
                                                            />
                                                        </span>
                                                    )}
                                                </Link>

                                                <div className="min-w-0 flex-1">
                                                    <Link
                                                        href={`/products/${item.slug}`}
                                                        onClick={onClose}
                                                        className="line-clamp-2 break-words text-sm font-semibold text-white transition hover:text-indigo-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                                    >
                                                        {item.name}
                                                    </Link>

                                                    {item.variant_name && (
                                                        <p
                                                            title={item.variant_name}
                                                            className="mt-1 min-w-0 truncate text-xs text-neutral-500"
                                                        >
                                                            {item.variant_name}
                                                        </p>
                                                    )}

                                                    <p className="mt-2 break-words text-xs font-semibold text-neutral-300">
                                                        {formatMoney(item.unit_price)}
                                                    </p>
                                                </div>

                                                <button
                                                    type="button"
                                                    disabled={processing}
                                                    onClick={() => {
                                                        void removeItem(item)
                                                    }}
                                                    aria-label={`Remove ${item.name} from cart`}
                                                    className="h-fit shrink-0 rounded px-1 py-1 text-xs font-semibold text-neutral-500 transition hover:text-red-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400 disabled:cursor-wait disabled:opacity-50"
                                                >
                                                    Remove
                                                </button>
                                            </div>

                                            <div className="mt-3 flex min-w-0 items-center justify-between gap-3 border-t border-neutral-800 pt-3">
                                                <div
                                                    className="inline-flex shrink-0 items-center rounded-lg border border-neutral-700"
                                                    aria-label={`Quantity for ${item.name}`}
                                                >
                                                    <button
                                                        type="button"
                                                        disabled={processing}
                                                        onClick={() => {
                                                            void updateQuantity(
                                                                item,
                                                                item.quantity - 1,
                                                            )
                                                        }}
                                                        aria-label={`Decrease quantity of ${item.name}`}
                                                        className="inline-flex h-10 w-10 items-center justify-center text-neutral-300 transition hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 disabled:cursor-wait disabled:opacity-50"
                                                    >
                                                        −
                                                    </button>

                                                    <span
                                                        className="min-w-8 text-center text-xs font-bold text-white"
                                                        aria-live="polite"
                                                    >
                                                        {item.quantity}
                                                    </span>

                                                    <button
                                                        type="button"
                                                        disabled={processing || item.quantity >= 99}
                                                        onClick={() => {
                                                            void updateQuantity(
                                                                item,
                                                                item.quantity + 1,
                                                            )
                                                        }}
                                                        aria-label={`Increase quantity of ${item.name}`}
                                                        className="inline-flex h-10 w-10 items-center justify-center text-neutral-300 transition hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 disabled:cursor-wait disabled:opacity-50"
                                                    >
                                                        +
                                                    </button>
                                                </div>

                                                <p className="min-w-0 break-words text-right text-sm font-bold text-white">
                                                    {formatMoney(item.line_total)}
                                                </p>
                                            </div>
                                        </li>
                                    )
                                })}
                            </ul>
                        </div>

                        <div className="shrink-0 border-t border-neutral-800 p-4 sm:p-5">
                            <div className="flex min-w-0 items-center justify-between gap-4 text-sm">
                                <span className="shrink-0 font-medium text-neutral-400">
                                    Subtotal
                                </span>

                                <strong className="min-w-0 break-words text-right text-base text-white">
                                    {formatMoney(cart.subtotal)}
                                </strong>
                            </div>

                            <p className="mt-2 text-xs leading-5 text-neutral-500">
                                Shipping, discounts, and taxes are calculated later in checkout.
                            </p>

                            <Link
                                href={route('checkout.index')}
                                className="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-neutral-950 px-5 text-sm font-semibold text-white transition hover:bg-neutral-800"
                            >
                                Proceed to checkout
                            </Link>
                        </div>
                    </>
                )}
            </aside>
        </div>
    )
}

function AccountMenu({
    id,
    config,
    user,
    onClose,
}: {
    id: string
    config: HeaderConfig
    user: StorefrontAuthUser | null
    onClose: () => void
}) {
    const links = config.actions.account.menu_links.filter((link) => link.label.trim() !== '')

    const signOut = () => {
        onClose()

        router.post(
            '/logout',
            {},
            {
                preserveScroll: false,
            },
        )
    }

    return (
        <div
            id={id}
            role="menu"
            aria-label="Account options"
            className="absolute right-0 top-full z-50 mt-2 w-[min(18rem,calc(100vw-1rem))] overflow-hidden rounded-xl border border-neutral-700 bg-neutral-900 shadow-2xl"
        >
            <div className="flex items-start justify-between gap-3 border-b border-neutral-800 p-4">
                <div className="min-w-0">
                    {user ? (
                        <div className="flex min-w-0 items-center gap-3">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">
                                {accountInitials(user.name)}
                            </span>

                            <div className="min-w-0">
                                <p
                                    className="truncate text-sm font-semibold text-white"
                                    title={user.name}
                                >
                                    {user.name}
                                </p>

                                <p
                                    className="mt-0.5 truncate text-[11px] text-neutral-500"
                                    title={user.email}
                                >
                                    {user.email}
                                </p>
                            </div>
                        </div>
                    ) : (
                        <>
                            <p className="text-xs font-semibold text-white">Account</p>
                            <p className="mt-1 text-[10px] text-neutral-500">
                                Sign in to manage your account
                            </p>
                        </>
                    )}
                </div>

                <button
                    type="button"
                    onClick={onClose}
                    aria-label="Close account menu"
                    className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                >
                    <X className="h-3.5 w-3.5" aria-hidden="true" />
                </button>
            </div>

            {user && (
                <div className="grid grid-cols-2 gap-2 border-b border-neutral-800 p-2">
                    <Link
                        href="/account"
                        role="menuitem"
                        onClick={onClose}
                        className="flex min-h-10 min-w-0 items-center justify-center truncate rounded-lg bg-indigo-600 px-2 text-xs font-semibold text-white transition hover:bg-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                    >
                        Dashboard
                    </Link>

                    <Link
                        href="/profile"
                        role="menuitem"
                        onClick={onClose}
                        className="flex min-h-10 min-w-0 items-center justify-center truncate rounded-lg border border-neutral-700 px-2 text-xs font-semibold text-neutral-300 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                    >
                        Profile
                    </Link>
                </div>
            )}

            <div className="max-h-64 overflow-y-auto overscroll-contain p-2">
                {links.map((link, index) => (
                    <Link
                        key={`${link.label}-${index}`}
                        href={link.url || '#'}
                        role="menuitem"
                        onClick={onClose}
                        title={link.label}
                        className="flex min-h-10 min-w-0 items-center rounded-lg px-3 text-xs font-medium text-neutral-300 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                    >
                        <span className="min-w-0 truncate">{link.label}</span>
                    </Link>
                ))}

                {links.length === 0 && !user && (
                    <p className="px-3 py-2 text-xs text-neutral-500">
                        No account links configured.
                    </p>
                )}
            </div>

            {user ? (
                <div className="border-t border-neutral-800 p-2">
                    <button
                        type="button"
                        role="menuitem"
                        onClick={signOut}
                        className="flex min-h-10 w-full items-center justify-center rounded-lg border border-red-500/20 bg-red-500/10 px-3 text-xs font-semibold text-red-300 transition hover:bg-red-500/20 hover:text-red-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400"
                    >
                        Sign out
                    </button>
                </div>
            ) : (
                <div className="grid grid-cols-2 gap-2 border-t border-neutral-800 p-2">
                    <Link
                        href={config.actions.account.guest_login_url || '/login'}
                        role="menuitem"
                        onClick={onClose}
                        className="flex min-h-10 min-w-0 items-center justify-center truncate rounded-lg border border-neutral-700 px-2 text-xs font-semibold text-neutral-300 transition hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                    >
                        Sign in
                    </Link>

                    <Link
                        href={config.actions.account.guest_register_url || '/register'}
                        role="menuitem"
                        onClick={onClose}
                        className="flex min-h-10 min-w-0 items-center justify-center truncate rounded-lg bg-white px-2 text-xs font-bold text-neutral-950 transition hover:bg-neutral-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                    >
                        Register
                    </Link>
                </div>
            )}
        </div>
    )
}

function accountInitials(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean)

    if (parts.length === 0) {
        return '?'
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase()
    }

    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}

function DesktopNavigation({
    config,
    megaMenuId,
    megaMenuOpen,
    onMegaMenuToggle,
}: {
    config: HeaderConfig
    megaMenuId: string
    megaMenuOpen: boolean
    onMegaMenuToggle: () => void
}) {
    if (!config.navigation.enabled) {
        return null
    }

    return (
        <div className="hidden min-w-0 border-b border-neutral-800 bg-neutral-950 lg:block">
            <div className="mx-auto flex min-h-12 w-full max-w-7xl min-w-0 items-center gap-5 px-4 sm:px-6 lg:px-8 xl:gap-7">
                {config.mega_menu.enabled && (
                    <button
                        type="button"
                        aria-expanded={megaMenuOpen}
                        aria-controls={megaMenuId}
                        aria-haspopup="true"
                        onClick={onMegaMenuToggle}
                        className={[
                            'inline-flex min-h-10 shrink-0 items-center gap-2 rounded-lg px-3 text-xs font-bold text-white transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500',
                            megaMenuOpen ? 'bg-violet-600' : 'bg-indigo-600 hover:bg-indigo-500',
                        ].join(' ')}
                    >
                        {megaMenuOpen ? (
                            <X className="h-4 w-4" aria-hidden="true" />
                        ) : (
                            <Menu className="h-4 w-4" aria-hidden="true" />
                        )}

                        <span className="max-w-40 truncate">
                            {config.navigation.mega_menu_label}
                        </span>

                        <ChevronDown
                            className={[
                                'h-3.5 w-3.5 shrink-0 transition-transform',
                                megaMenuOpen ? 'rotate-180' : '',
                            ].join(' ')}
                            aria-hidden="true"
                        />
                    </button>
                )}

                <nav
                    aria-label="Primary navigation"
                    className="flex min-w-0 flex-1 items-center gap-5 overflow-hidden xl:gap-7"
                >
                    {config.navigation.links
                        .filter((link) => link.label.trim() !== '')
                        .map((link, index) => (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url || '#'}
                                title={link.label}
                                className={[
                                    'max-w-40 shrink-0 truncate whitespace-nowrap rounded-sm text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500',
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

function MegaMenu({
    id,
    config,
    categories,
}: {
    id: string
    config: HeaderConfig
    categories: StorefrontCategoryMenu
}) {
    if (!config.navigation.enabled || !config.mega_menu.enabled) {
        return null
    }

    const hasCatalogCategories = categories.length > 0

    return (
        <nav
            id={id}
            aria-label="Product categories"
            className="hidden min-w-0 border-b border-neutral-800 bg-neutral-900 lg:block"
        >
            <div className="mx-auto grid w-full max-w-7xl min-w-0 grid-cols-12 gap-6 px-4 py-7 sm:px-6 lg:px-8 xl:gap-8">
                <div className="col-span-8 grid min-w-0 grid-cols-2 gap-x-6 gap-y-6 xl:grid-cols-3 xl:gap-x-8">
                    {hasCatalogCategories
                        ? categories.map((category) => (
                              <section key={category.id} className="min-w-0">
                                  <Link
                                      href={`/category/${encodeURIComponent(category.slug)}`}
                                      title={category.name}
                                      className="inline-flex max-w-full items-center gap-2 rounded-sm text-xs font-bold uppercase tracking-[0.08em] text-white transition hover:text-indigo-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                  >
                                      <span className="min-w-0 truncate">{category.name}</span>
                                      <ChevronDown
                                          className="-rotate-90 h-3 w-3 shrink-0 text-neutral-600"
                                          aria-hidden="true"
                                      />
                                  </Link>

                                  {category.children.length > 0 ? (
                                      <div className="mt-3 space-y-2.5">
                                          {category.children.map((child) => (
                                              <Link
                                                  key={child.id}
                                                  href={`/category/${encodeURIComponent(child.slug)}`}
                                                  title={child.name}
                                                  className="block truncate rounded-sm text-xs text-neutral-400 transition hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                              >
                                                  {child.name}
                                              </Link>
                                          ))}
                                      </div>
                                  ) : (
                                      <p className="mt-3 text-xs text-neutral-600">
                                          Browse products
                                      </p>
                                  )}
                              </section>
                          ))
                        : config.mega_menu.groups
                              .filter(
                                  (group) =>
                                      group.heading.trim() !== '' ||
                                      group.links.some((link) => link.label.trim() !== ''),
                              )
                              .map((group, groupIndex) => (
                                  <section
                                      key={`${group.heading}-${groupIndex}`}
                                      className="min-w-0"
                                  >
                                      {group.heading && (
                                          <h2
                                              title={group.heading}
                                              className="truncate text-xs font-bold uppercase tracking-[0.08em] text-white"
                                          >
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
                                                      title={link.label}
                                                      className="block truncate rounded-sm text-xs text-neutral-400 transition hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
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
                            className="flex h-full min-h-44 min-w-0 flex-col justify-between overflow-hidden rounded-2xl border border-indigo-500/20 bg-gradient-to-br from-indigo-950 via-violet-950 to-neutral-950 p-5 transition hover:border-indigo-400/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                        >
                            <div className="min-w-0">
                                {config.mega_menu.promotion.eyebrow && (
                                    <p className="truncate text-[10px] font-bold uppercase tracking-[0.12em] text-indigo-400">
                                        {config.mega_menu.promotion.eyebrow}
                                    </p>
                                )}

                                <p className="mt-2 line-clamp-4 max-w-xs break-words text-lg font-black leading-tight text-white">
                                    {config.mega_menu.promotion.title}
                                </p>
                            </div>

                            {config.mega_menu.promotion.button_label && (
                                <span className="mt-5 inline-flex min-h-9 max-w-full items-center rounded-lg bg-white px-3 text-xs font-bold text-neutral-950">
                                    <span className="truncate">
                                        {config.mega_menu.promotion.button_label}
                                    </span>
                                </span>
                            )}
                        </Link>
                    </div>
                )}
            </div>
        </nav>
    )
}

function MobileMenu({
    id,
    config,
    user,
    categories,
    searchQuery,
    onSearchQueryChange,
    onClose,
}: {
    id: string
    config: HeaderConfig
    user: StorefrontAuthUser | null
    categories: StorefrontCategoryMenu
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
        <div
            id={id}
            className="max-h-[calc(100dvh-4rem)] min-w-0 overflow-y-auto overscroll-contain border-b border-neutral-800 bg-neutral-950 p-3 sm:p-4 lg:hidden"
        >
            <div className="mx-auto w-full max-w-7xl min-w-0">
                <div className="mb-4 flex min-w-0 items-center justify-between gap-3">
                    <div className="min-w-0">
                        <p className="text-[10px] font-bold uppercase tracking-[0.1em] text-neutral-500">
                            Menu
                        </p>
                        <p className="mt-1 truncate text-sm font-semibold text-white">
                            Browse store
                        </p>
                    </div>

                    <button
                        type="button"
                        aria-label="Close navigation"
                        onClick={onClose}
                        className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-neutral-800 bg-neutral-900 text-neutral-400 transition hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                    >
                        <X className="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>

                {config.mobile.search_enabled && (
                    <form
                        role="search"
                        onSubmit={submitSearch}
                        className="mb-4 flex min-h-11 min-w-0 items-center gap-2 rounded-xl border border-neutral-800 bg-neutral-900 px-3 focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/20"
                    >
                        <Search className="h-4 w-4 shrink-0 text-neutral-500" aria-hidden="true" />

                        <input
                            type="search"
                            aria-label="Search products"
                            value={searchQuery}
                            placeholder={config.mobile.search_placeholder}
                            autoComplete="off"
                            onChange={(event) => onSearchQueryChange(event.target.value)}
                            className="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-white outline-none placeholder:text-neutral-500 focus:ring-0"
                        />

                        <button
                            type="submit"
                            aria-label="Search products"
                            className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white transition hover:bg-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                        >
                            <Search className="h-4 w-4" aria-hidden="true" />
                        </button>
                    </form>
                )}

                <nav aria-label="Mobile navigation" className="min-w-0 space-y-2">
                    {categories.length > 0 && (
                        <section className="mb-4 min-w-0">
                            <p className="mb-2 text-[10px] font-bold uppercase tracking-[0.1em] text-neutral-500">
                                Categories
                            </p>

                            <div className="space-y-2">
                                {categories.map((category) => (
                                    <div
                                        key={category.id}
                                        className="min-w-0 overflow-hidden rounded-xl border border-neutral-800 bg-neutral-900"
                                    >
                                        <Link
                                            href={`/category/${encodeURIComponent(category.slug)}`}
                                            onClick={onClose}
                                            title={category.name}
                                            className="flex min-h-11 min-w-0 items-center justify-between gap-3 px-3 text-sm font-semibold text-white transition hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                        >
                                            <span className="min-w-0 truncate">
                                                {category.name}
                                            </span>
                                            <ChevronDown
                                                className="-rotate-90 h-3.5 w-3.5 shrink-0 text-neutral-600"
                                                aria-hidden="true"
                                            />
                                        </Link>

                                        {category.children.length > 0 && (
                                            <div className="border-t border-neutral-800 px-2 py-2">
                                                {category.children.map((child) => (
                                                    <Link
                                                        key={child.id}
                                                        href={`/category/${encodeURIComponent(child.slug)}`}
                                                        onClick={onClose}
                                                        title={child.name}
                                                        className="flex min-h-10 min-w-0 items-center rounded-lg px-3 text-xs text-neutral-400 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                                    >
                                                        <span className="min-w-0 truncate">
                                                            {child.name}
                                                        </span>
                                                    </Link>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </section>
                    )}

                    {config.mobile.menu_links
                        .filter((link) => link.label.trim() !== '')
                        .map((link, index) => (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url || '#'}
                                onClick={onClose}
                                title={link.label}
                                className={[
                                    'flex min-h-11 min-w-0 items-center justify-between rounded-xl px-3 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500',
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
                    <div className="mt-4 min-w-0 border-t border-neutral-800 pt-4">
                        {user ? (
                            <>
                                <div className="mb-3 flex min-w-0 items-center gap-3 rounded-xl border border-neutral-800 bg-neutral-900 p-3">
                                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">
                                        {accountInitials(user.name)}
                                    </span>

                                    <div className="min-w-0">
                                        <p
                                            className="truncate text-sm font-semibold text-white"
                                            title={user.name}
                                        >
                                            {user.name}
                                        </p>
                                        <p
                                            className="mt-0.5 truncate text-xs text-neutral-500"
                                            title={user.email}
                                        >
                                            {user.email}
                                        </p>
                                    </div>
                                </div>

                                <div className="grid min-w-0 grid-cols-2 gap-2">
                                    <Link
                                        href="/account"
                                        onClick={onClose}
                                        className="flex min-h-11 min-w-0 items-center justify-center truncate rounded-xl bg-indigo-600 px-3 text-xs font-semibold text-white transition hover:bg-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                                    >
                                        Dashboard
                                    </Link>

                                    <Link
                                        href="/profile"
                                        onClick={onClose}
                                        className="flex min-h-11 min-w-0 items-center justify-center truncate rounded-xl border border-neutral-800 bg-neutral-900 px-3 text-xs font-semibold text-neutral-300 transition hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                    >
                                        Profile
                                    </Link>
                                </div>

                                <button
                                    type="button"
                                    onClick={() => {
                                        onClose()
                                        router.post('/logout')
                                    }}
                                    className="mt-2 flex min-h-11 w-full items-center justify-center rounded-xl border border-red-500/20 bg-red-500/10 px-3 text-xs font-semibold text-red-300 transition hover:bg-red-500/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400"
                                >
                                    Sign out
                                </button>
                            </>
                        ) : (
                            <div className="grid min-w-0 grid-cols-2 gap-2">
                                <Link
                                    href={config.actions.account.guest_login_url || '/login'}
                                    onClick={onClose}
                                    className="flex min-h-11 min-w-0 items-center justify-center truncate rounded-xl border border-neutral-800 bg-neutral-900 px-3 text-xs font-semibold text-neutral-300 transition hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                >
                                    Sign in
                                </Link>

                                <Link
                                    href={config.actions.account.guest_register_url || '/register'}
                                    onClick={onClose}
                                    className="flex min-h-11 min-w-0 items-center justify-center truncate rounded-xl bg-white px-3 text-xs font-bold text-neutral-950 transition hover:bg-neutral-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                >
                                    Register
                                </Link>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    )
}
