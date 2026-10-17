import { ChevronDown, Heart, Menu, Search, ShoppingBag, X } from 'lucide-react'
import {
    type ReactNode,
    type RefObject,
    useEffect,
    useId,
    useRef,
    useState,
} from 'react'

import { formatMoney } from '@/lib/money'

import type { HeaderConfig, HeaderPreviewDevice } from '@/types/header-builder'

interface Props {
    config: HeaderConfig
    isEnabled: boolean
    device: HeaderPreviewDevice
}

export default function HeaderDesign1Preview({ config, isEnabled, device }: Props) {
    const [cartOpen, setCartOpen] = useState(false)
    const [searchOpen, setSearchOpen] = useState(false)
    const [megaMenuOpen, setMegaMenuOpen] = useState(false)
    const [accountOpen, setAccountOpen] = useState(false)
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false)

    const searchId = useId()
    const accountId = useId()
    const mobileMenuId = useId()
    const megaMenuId = useId()
    const cartDrawerId = useId()

    const cartTriggerRef = useRef<HTMLButtonElement>(null)

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

    useEffect(() => {
        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key !== 'Escape') {
                return
            }

            closeTransientMenus()

            if (cartOpen) {
                closeCart()
            }
        }

        window.addEventListener('keydown', handleKeyDown)

        return () => {
            window.removeEventListener('keydown', handleKeyDown)
        }
    }, [cartOpen])

    const handleSearchToggle = () => {
        const next = !searchOpen

        closeTransientMenus()
        setCartOpen(false)
        setSearchOpen(next)
    }

    const handleAccountToggle = () => {
        const next = !accountOpen

        closeTransientMenus()
        setCartOpen(false)
        setAccountOpen(next)
    }

    const handleMegaMenuToggle = () => {
        const next = !megaMenuOpen

        closeTransientMenus()
        setCartOpen(false)
        setMegaMenuOpen(next)
    }

    const handleMobileMenuToggle = () => {
        const next = !mobileMenuOpen

        closeTransientMenus()
        setCartOpen(false)
        setMobileMenuOpen(next)
    }

    const handleCartToggle = () => {
        const next = !cartOpen

        closeTransientMenus()
        setCartOpen(next)
    }

    return (
        <div
            className={[
                'relative w-full min-w-0 overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-950 shadow-2xl transition-opacity',
                isEnabled ? 'opacity-100' : 'pointer-events-none opacity-40',
            ].join(' ')}
            aria-disabled={!isEnabled}
        >
            <AnnouncementBar config={config} device={device} />

            <MainHeader
                config={config}
                device={device}
                searchId={searchId}
                accountId={accountId}
                mobileMenuId={mobileMenuId}
                cartDrawerId={cartDrawerId}
                searchOpen={searchOpen}
                accountOpen={accountOpen}
                mobileMenuOpen={mobileMenuOpen}
                cartOpen={cartOpen}
                cartTriggerRef={cartTriggerRef}
                onSearchToggle={handleSearchToggle}
                onAccountToggle={handleAccountToggle}
                onMobileMenuToggle={handleMobileMenuToggle}
                onCartToggle={handleCartToggle}
            />

            {device === 'mobile' ? (
                mobileMenuOpen && (
                    <MobileMenu
                        id={mobileMenuId}
                        config={config}
                        onClose={() => setMobileMenuOpen(false)}
                    />
                )
            ) : (
                <>
                    <DesktopNavigation
                        config={config}
                        device={device}
                        megaMenuId={megaMenuId}
                        mobileMenuId={mobileMenuId}
                        megaMenuOpen={megaMenuOpen}
                        mobileMenuOpen={mobileMenuOpen}
                        onMegaMenuToggle={handleMegaMenuToggle}
                        onTabletMenuToggle={handleMobileMenuToggle}
                    />

                    {device === 'tablet' && mobileMenuOpen && (
                        <MobileMenu
                            id={mobileMenuId}
                            config={config}
                            onClose={() => setMobileMenuOpen(false)}
                        />
                    )}

                    {megaMenuOpen && (
                        <MegaMenu
                            id={megaMenuId}
                            config={config}
                            device={device}
                        />
                    )}
                </>
            )}

            {cartOpen && (
                <CartPreviewDrawer
                    id={cartDrawerId}
                    device={device}
                    onClose={closeCart}
                />
            )}
        </div>
    )
}

function AnnouncementBar({
    config,
    device,
}: {
    config: HeaderConfig
    device: HeaderPreviewDevice
}) {
    if (!config.announcement.enabled) {
        return null
    }

    return (
        <div className="w-full border-b border-indigo-500/30 bg-gradient-to-r from-indigo-700 via-violet-700 to-indigo-800 text-white">
            <div
                className={[
                    'mx-auto flex w-full max-w-7xl min-w-0 items-center py-2',
                    device === 'mobile'
                        ? 'min-h-8 justify-center px-2'
                        : 'min-h-9 justify-between gap-4 px-4 sm:px-6 lg:px-8',
                ].join(' ')}
            >
                <div className="flex min-w-0 items-center gap-2">
                    {config.announcement.badge && (
                        <span
                            title={config.announcement.badge}
                            className="max-w-24 shrink-0 truncate rounded-full bg-white/20 px-2 py-0.5 text-[8px] font-extrabold uppercase tracking-wide text-white"
                        >
                            {config.announcement.badge}
                        </span>
                    )}

                    <p
                        title={config.announcement.message}
                        className={[
                            'min-w-0 truncate font-medium',
                            device === 'mobile' ? 'text-[9px]' : 'text-[11px]',
                        ].join(' ')}
                    >
                        {config.announcement.message}

                        {device !== 'mobile' && config.announcement.promo_code && (
                            <>
                                {' Use code '}
                                <strong className="font-extrabold underline">
                                    {config.announcement.promo_code}
                                </strong>
                            </>
                        )}

                        {device !== 'mobile' && config.announcement.promo_suffix && (
                            <> {config.announcement.promo_suffix}</>
                        )}
                    </p>
                </div>

                {device !== 'mobile' && (
                    <div className="hidden min-w-0 shrink-0 items-center gap-4 text-[10px] text-indigo-100 md:flex">
                        {config.announcement.links
                            .filter((link) => link.label.trim() !== '')
                            .map((link, index) => (
                                <span
                                    key={`${link.label}-${index}`}
                                    title={link.label}
                                    className="max-w-36 truncate whitespace-nowrap"
                                >
                                    {link.label}
                                </span>
                            ))}

                        {config.announcement.currency_label && (
                            <span className="inline-flex max-w-28 items-center gap-1 truncate whitespace-nowrap">
                                {config.announcement.currency_label}
                                <ChevronDown className="h-3 w-3 shrink-0" aria-hidden="true" />
                            </span>
                        )}
                    </div>
                )}
            </div>
        </div>
    )
}

function MainHeader({
    config,
    device,
    searchId,
    accountId,
    mobileMenuId,
    cartDrawerId,
    searchOpen,
    accountOpen,
    mobileMenuOpen,
    cartOpen,
    cartTriggerRef,
    onSearchToggle,
    onAccountToggle,
    onMobileMenuToggle,
    onCartToggle,
}: {
    config: HeaderConfig
    device: HeaderPreviewDevice
    searchId: string
    accountId: string
    mobileMenuId: string
    cartDrawerId: string
    searchOpen: boolean
    accountOpen: boolean
    mobileMenuOpen: boolean
    cartOpen: boolean
    cartTriggerRef: RefObject<HTMLButtonElement>
    onSearchToggle: () => void
    onAccountToggle: () => void
    onMobileMenuToggle: () => void
    onCartToggle: () => void
}) {
    const compact = device === 'tablet' || device === 'mobile'

    return (
        <div className="relative z-30 w-full border-b border-neutral-800 bg-neutral-900">
            <div
                className={[
                    'mx-auto flex w-full max-w-7xl min-w-0 items-center',
                    device === 'mobile'
                        ? 'min-h-16 gap-1.5 px-2'
                        : device === 'tablet'
                          ? 'min-h-20 gap-3 px-3'
                          : 'min-h-20 gap-5 px-4 sm:px-6 lg:px-8',
                ].join(' ')}
            >
                {compact && (
                    <button
                        type="button"
                        aria-label={mobileMenuOpen ? 'Close menu preview' : 'Open menu preview'}
                        aria-expanded={mobileMenuOpen}
                        aria-controls={mobileMenuId}
                        onClick={onMobileMenuToggle}
                        className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-neutral-400 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                    >
                        {mobileMenuOpen ? (
                            <X className="h-5 w-5" aria-hidden="true" />
                        ) : (
                            <Menu className="h-5 w-5" aria-hidden="true" />
                        )}
                    </button>
                )}

                <Brand config={config} device={device} />

                {device !== 'mobile' && (
                    <SearchPreview
                        id={searchId}
                        config={config}
                        device={device}
                        open={searchOpen}
                        onToggle={onSearchToggle}
                    />
                )}

                <HeaderActions
                    config={config}
                    device={device}
                    accountMenuId={accountId}
                    cartDrawerId={cartDrawerId}
                    accountOpen={accountOpen}
                    cartOpen={cartOpen}
                    cartTriggerRef={cartTriggerRef}
                    onAccountToggle={onAccountToggle}
                    onCartToggle={onCartToggle}
                />
            </div>
        </div>
    )
}

function Brand({ config, device }: { config: HeaderConfig; device: HeaderPreviewDevice }) {
    const logoSize = device === 'mobile' ? 'h-9 w-9' : 'h-10 w-10'

    return (
        <div className="flex min-w-0 shrink-0 items-center gap-2.5">
            {config.brand.logo_url ? (
                <div className={['flex shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white', logoSize].join(' ')}>
                    <img src={config.brand.logo_url} alt="" className="h-full w-full object-contain" />
                </div>
            ) : (
                <div className={['flex shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-sm font-black text-white shadow-lg shadow-indigo-600/20', logoSize].join(' ')}>
                    {config.brand.fallback_mark || 'A'}
                </div>
            )}

            {device === 'desktop' && (
                <p
                    title={`${config.brand.name}${config.brand.accent ?? ''}`}
                    className="max-w-48 truncate whitespace-nowrap text-xl font-black tracking-tight text-white xl:max-w-64"
                >
                    {config.brand.name}
                    <span className="text-indigo-500">{config.brand.accent ?? ''}</span>
                </p>
            )}
        </div>
    )
}

function SearchPreview({ id, config, device, open, onToggle }: {
    id: string
    config: HeaderConfig
    device: HeaderPreviewDevice
    open: boolean
    onToggle: () => void
}) {
    if (!config.search.enabled) {
        return <div className="min-w-0 flex-1" />
    }

    const suggestions = config.search.trending_searches.filter((item) => item.trim() !== '')

    return (
        <div className={['relative mx-auto min-w-0 flex-1', device === 'desktop' ? 'max-w-xl' : 'max-w-sm'].join(' ')}>
            <button
                type="button"
                role="combobox"
                aria-label="Search products preview"
                aria-expanded={open}
                aria-controls={id}
                aria-haspopup="listbox"
                onClick={onToggle}
                className={[
                    'flex min-h-11 w-full min-w-0 items-center gap-3 rounded-xl border bg-neutral-950 px-3 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 sm:px-4',
                    open ? 'border-indigo-500' : 'border-neutral-700 hover:border-neutral-600',
                ].join(' ')}
            >
                <Search className="h-4 w-4 shrink-0 text-neutral-500" aria-hidden="true" />
                <span className="min-w-0 flex-1 truncate text-xs text-neutral-500">{config.search.placeholder}</span>
                <span className="hidden rounded-md border border-neutral-700 bg-neutral-900 px-1.5 py-0.5 text-[9px] font-semibold text-neutral-500 sm:inline" aria-hidden="true">/</span>
            </button>

            {open && config.search.suggestions_enabled && (
                <div
                    id={id}
                    role="listbox"
                    aria-label="Search suggestions preview"
                    className="absolute left-0 right-0 top-full z-50 mt-2 max-h-64 overflow-y-auto overscroll-contain rounded-xl border border-neutral-700 bg-neutral-900 shadow-2xl"
                >
                    <div className="p-4">
                        <div className="flex items-center justify-between gap-3">
                            <p className="min-w-0 truncate text-[10px] font-bold uppercase tracking-[0.12em] text-neutral-500">{config.search.suggestion_heading}</p>
                            <button type="button" aria-label="Close search suggestions preview" onClick={onToggle} className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                                <X className="h-3.5 w-3.5" aria-hidden="true" />
                            </button>
                        </div>

                        {suggestions.length > 0 ? (
                            <div className="mt-3 flex flex-wrap gap-2">
                                {suggestions.map((item, index) => (
                                    <button key={`${item}-${index}`} type="button" role="option" aria-selected="false" title={item} className="max-w-full truncate rounded-full border border-neutral-700 bg-neutral-800 px-2.5 py-1.5 text-[10px] font-medium text-neutral-300 transition hover:border-indigo-500 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                                        {item}
                                    </button>
                                ))}
                            </div>
                        ) : (
                            <p className="mt-3 text-xs text-neutral-500" role="status" aria-live="polite">No search suggestions configured.</p>
                        )}
                    </div>
                </div>
            )}
        </div>
    )
}

function HeaderActions({
    config,
    device,
    accountMenuId,
    cartDrawerId,
    accountOpen,
    cartOpen,
    cartTriggerRef,
    onAccountToggle,
    onCartToggle,
}: {
    config: HeaderConfig
    device: HeaderPreviewDevice
    accountMenuId: string
    cartDrawerId: string
    accountOpen: boolean
    cartOpen: boolean
    cartTriggerRef: RefObject<HTMLButtonElement>
    onAccountToggle: () => void
    onCartToggle: () => void
}) {
    return (
        <div className="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
            {device !== 'mobile' && config.actions.wishlist.enabled && (
                <button type="button" aria-label="Wishlist preview, 4 items" className="relative inline-flex h-11 w-11 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-950 text-neutral-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                    <Heart className="h-4 w-4" aria-hidden="true" />
                    <PreviewCount>4</PreviewCount>
                </button>
            )}

            {config.actions.cart.enabled && (
                <button
                    ref={cartTriggerRef}
                    type="button"
                    aria-label="Cart preview, 2 items"
                    aria-expanded={cartOpen}
                    aria-controls={cartDrawerId}
                    aria-haspopup="dialog"
                    onClick={onCartToggle}
                    className="relative inline-flex h-11 w-11 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-950 text-neutral-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                >
                    <ShoppingBag className="h-4 w-4" aria-hidden="true" />
                    <PreviewCount>2</PreviewCount>
                </button>
            )}

            {device !== 'mobile' && config.actions.account.enabled && (
                <div className="relative">
                    <button
                        type="button"
                        aria-label="Account menu preview"
                        aria-expanded={accountOpen}
                        aria-controls={accountMenuId}
                        aria-haspopup="menu"
                        onClick={onAccountToggle}
                        className={[
                            'inline-flex min-h-11 items-center gap-2 rounded-xl border bg-neutral-950 px-2.5 text-neutral-300 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500',
                            accountOpen ? 'border-indigo-500' : 'border-neutral-800 hover:border-neutral-700',
                        ].join(' ')}
                    >
                        <span className="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-600 text-[9px] font-black text-white">JD</span>
                        <ChevronDown className={['h-3.5 w-3.5 text-neutral-500 transition-transform', accountOpen ? 'rotate-180' : ''].join(' ')} aria-hidden="true" />
                    </button>

                    {accountOpen && <AccountDropdown id={accountMenuId} config={config} onClose={onAccountToggle} />}
                </div>
            )}
        </div>
    )
}

function AccountDropdown({ id, config, onClose }: { id: string; config: HeaderConfig; onClose: () => void }) {
    const links = config.actions.account.menu_links.filter((link) => link.label.trim() !== '')

    return (
        <div id={id} role="menu" aria-label="Account options preview" className="absolute right-0 top-full z-50 mt-2 w-[min(16rem,calc(100vw-1rem))] overflow-hidden rounded-xl border border-neutral-700 bg-neutral-900 shadow-2xl">
            <div className="flex items-start justify-between gap-3 border-b border-neutral-800 p-4">
                <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-white">John Doe</p>
                    <p className="mt-1 truncate text-[10px] text-neutral-500">Preview customer</p>
                </div>
                <button type="button" aria-label="Close account preview" onClick={onClose} className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                    <X className="h-3.5 w-3.5" aria-hidden="true" />
                </button>
            </div>

            <div className="p-2">
                {links.map((link, index) => (
                    <button key={`${link.label}-${index}`} type="button" role="menuitem" title={link.label} className="flex min-h-10 w-full items-center rounded-lg px-3 text-left text-xs font-medium text-neutral-300 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                        <span className="min-w-0 truncate">{link.label}</span>
                    </button>
                ))}

                {links.length === 0 && <p className="px-3 py-2 text-xs text-neutral-500">No account links configured.</p>}
            </div>
        </div>
    )
}

function PreviewCount({ children }: { children: ReactNode }) {
    return <span className="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-indigo-600 px-1 text-[8px] font-bold text-white" aria-hidden="true">{children}</span>
}

function MobileMenu({ id, config, onClose }: { id: string; config: HeaderConfig; onClose: () => void }) {
    return (
        <div id={id} className="max-h-[min(32rem,70dvh)] w-full overflow-y-auto overscroll-contain border-b border-neutral-800 bg-neutral-950 p-3">
            <div className="mb-3 flex items-center justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-[10px] font-bold uppercase tracking-[0.1em] text-neutral-500">Menu</p>
                    <p className="mt-1 truncate text-sm font-semibold text-white">Browse store</p>
                </div>
                <button type="button" aria-label="Close menu preview" onClick={onClose} className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-900 text-neutral-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                    <X className="h-4 w-4" aria-hidden="true" />
                </button>
            </div>

            {config.mobile.search_enabled && (
                <div role="search" className="mb-4 flex min-h-11 items-center gap-2 rounded-xl border border-neutral-800 bg-neutral-900 px-3">
                    <Search className="h-4 w-4 shrink-0 text-neutral-500" aria-hidden="true" />
                    <span className="min-w-0 flex-1 truncate text-xs text-neutral-500">{config.mobile.search_placeholder}</span>
                </div>
            )}

            <nav aria-label="Mobile navigation preview" className="space-y-2">
                {config.mobile.menu_links.filter((link) => link.label.trim() !== '').map((link, index) => (
                    <button
                        key={`${link.label}-${index}`}
                        type="button"
                        title={link.label}
                        onClick={onClose}
                        className={[
                            'flex min-h-11 w-full min-w-0 items-center justify-between rounded-xl px-3 text-left text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500',
                            link.style === 'primary'
                                ? 'bg-indigo-600 text-white'
                                : link.style === 'highlight'
                                  ? 'border border-amber-500/30 bg-amber-500/10 text-amber-400'
                                  : 'border border-neutral-800 bg-neutral-900 text-neutral-300',
                        ].join(' ')}
                    >
                        <span className="min-w-0 truncate">{link.label}</span>
                        <ChevronDown className="-rotate-90 h-3.5 w-3.5 shrink-0 opacity-60" aria-hidden="true" />
                    </button>
                ))}
            </nav>

            {config.actions.account.enabled && (
                <div className="mt-4 border-t border-neutral-800 pt-4">
                    <div className="grid grid-cols-2 gap-2">
                        <button type="button" onClick={onClose} className="flex min-h-11 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-900 px-3 text-xs font-semibold text-neutral-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">Sign in</button>
                        <button type="button" onClick={onClose} className="flex min-h-11 items-center justify-center rounded-xl bg-white px-3 text-xs font-bold text-neutral-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">Register</button>
                    </div>
                </div>
            )}
        </div>
    )
}

function DesktopNavigation({
    config,
    device,
    megaMenuId,
    mobileMenuId,
    megaMenuOpen,
    mobileMenuOpen,
    onMegaMenuToggle,
    onTabletMenuToggle,
}: {
    config: HeaderConfig
    device: HeaderPreviewDevice
    megaMenuId: string
    mobileMenuId: string
    megaMenuOpen: boolean
    mobileMenuOpen: boolean
    onMegaMenuToggle: () => void
    onTabletMenuToggle: () => void
}) {
    if (!config.navigation.enabled) {
        return null
    }

    if (device === 'tablet') {
        return (
            <div className="w-full border-b border-neutral-800 bg-neutral-950 px-4 py-2">
                <button type="button" aria-expanded={mobileMenuOpen} aria-controls={mobileMenuId} onClick={onTabletMenuToggle} className="inline-flex min-h-11 items-center gap-2 rounded-lg border border-neutral-800 bg-neutral-900 px-3 text-xs font-semibold text-neutral-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                    {mobileMenuOpen ? <X className="h-4 w-4" aria-hidden="true" /> : <Menu className="h-4 w-4" aria-hidden="true" />}
                    Menu
                </button>
            </div>
        )
    }

    if (device !== 'desktop') {
        return null
    }

    return (
        <div className="w-full border-b border-neutral-800 bg-neutral-950">
            <div className="mx-auto flex min-h-12 w-full max-w-7xl min-w-0 items-center gap-7 px-4 sm:px-6 lg:px-8">
                {config.mega_menu.enabled && (
                    <button type="button" aria-expanded={megaMenuOpen} aria-controls={megaMenuId} aria-haspopup="true" onClick={onMegaMenuToggle} className={['inline-flex min-h-10 shrink-0 items-center gap-2 rounded-lg px-3 text-xs font-bold text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500', megaMenuOpen ? 'bg-violet-600' : 'bg-indigo-600'].join(' ')}>
                        {megaMenuOpen ? <X className="h-4 w-4" aria-hidden="true" /> : <Menu className="h-4 w-4" aria-hidden="true" />}
                        <span className="max-w-40 truncate">{config.navigation.mega_menu_label}</span>
                        <ChevronDown className={['h-3.5 w-3.5 shrink-0 transition-transform', megaMenuOpen ? 'rotate-180' : ''].join(' ')} aria-hidden="true" />
                    </button>
                )}

                <nav aria-label="Primary navigation preview" className="flex min-w-0 flex-1 items-center gap-7 overflow-hidden">
                    {config.navigation.links.filter((link) => link.label.trim() !== '').map((link, index) => (
                        <span key={`${link.label}-${index}`} title={link.label} className={['max-w-40 shrink-0 truncate whitespace-nowrap text-xs font-semibold', link.style === 'highlight' ? 'text-amber-400' : 'text-neutral-300'].join(' ')}>{link.label}</span>
                    ))}
                </nav>
            </div>
        </div>
    )
}

function MegaMenu({ id, config, device }: { id: string; config: HeaderConfig; device: HeaderPreviewDevice }) {
    if (device !== 'desktop' || !config.navigation.enabled || !config.mega_menu.enabled) {
        return null
    }

    return (
        <div id={id} aria-label="Product categories preview" className="w-full border-b border-neutral-800 bg-neutral-900">
            <div className="mx-auto grid w-full max-w-7xl grid-cols-12 gap-8 px-4 py-7 sm:px-6 lg:px-8">
                <div className="col-span-8 grid min-w-0 grid-cols-2 gap-x-8 gap-y-6 xl:grid-cols-3">
                    {config.mega_menu.groups
                        .filter((group) => group.heading.trim() !== '' || group.links.some((link) => link.label.trim() !== ''))
                        .map((group, groupIndex) => (
                            <section key={`${group.heading}-${groupIndex}`} className="min-w-0">
                                {group.heading && <h2 title={group.heading} className="truncate text-xs font-bold uppercase tracking-[0.08em] text-white">{group.heading}</h2>}
                                <div className="mt-3 space-y-2.5">
                                    {group.links.filter((link) => link.label.trim() !== '').map((link, linkIndex) => (
                                        <p key={`${link.label}-${linkIndex}`} title={link.label} className="truncate text-xs text-neutral-400">{link.label}</p>
                                    ))}
                                </div>
                            </section>
                        ))}
                </div>

                {config.mega_menu.promotion.enabled && (
                    <div className="col-span-4 min-w-0">
                        <div className="flex h-full min-h-44 min-w-0 flex-col justify-between overflow-hidden rounded-2xl border border-indigo-500/20 bg-gradient-to-br from-indigo-950 via-violet-950 to-neutral-950 p-5">
                            <div className="min-w-0">
                                {config.mega_menu.promotion.eyebrow && <p className="truncate text-[10px] font-bold uppercase tracking-[0.12em] text-indigo-400">{config.mega_menu.promotion.eyebrow}</p>}
                                <p className="mt-2 line-clamp-3 break-words text-lg font-black leading-tight text-white">{config.mega_menu.promotion.title}</p>
                            </div>

                            {config.mega_menu.promotion.button_label && (
                                <span className="mt-5 inline-flex min-h-10 max-w-full items-center rounded-lg bg-white px-3 text-xs font-bold text-neutral-950">
                                    <span className="truncate">{config.mega_menu.promotion.button_label}</span>
                                </span>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </div>
    )
}

function CartPreviewDrawer({ id, device, onClose }: { id: string; device: HeaderPreviewDevice; onClose: () => void }) {
    const drawerRef = useRef<HTMLElement>(null)

    useEffect(() => {
        drawerRef.current?.focus()
    }, [])

    const unitPrice = 425000

    return (
        <div className="absolute inset-0 z-[100] overflow-hidden">
            <button type="button" aria-label="Close cart preview" onClick={onClose} className="absolute inset-0 bg-black/60" />

            <aside
                ref={drawerRef}
                id={id}
                role="dialog"
                aria-modal="true"
                aria-labelledby={`${id}-title`}
                aria-describedby={`${id}-description`}
                tabIndex={-1}
                className={[
                    'absolute right-0 top-0 flex h-full min-h-0 flex-col overflow-hidden border-l border-neutral-800 bg-neutral-950 shadow-2xl outline-none',
                    device === 'mobile' ? 'w-full' : 'w-[min(24rem,90%)]',
                ].join(' ')}
            >
                <div className="flex shrink-0 items-center justify-between gap-4 border-b border-neutral-800 px-4 py-4">
                    <div className="min-w-0">
                        <h2 id={`${id}-title`} className="truncate text-sm font-bold text-white">Your Cart</h2>
                        <p className="mt-1 text-xs text-neutral-500">1 item</p>
                    </div>
                    <button type="button" aria-label="Close cart preview" onClick={onClose} className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-neutral-800 text-neutral-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                        <X className="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>

                <p id={`${id}-description`} className="sr-only">Preview of the storefront cart drawer.</p>

                <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-4">
                    <div className="rounded-xl border border-neutral-800 bg-neutral-900/70 p-3">
                        <div className="flex min-w-0 gap-3">
                            <span className="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-neutral-800 text-neutral-600">
                                <ShoppingBag className="h-5 w-5" aria-hidden="true" />
                            </span>
                            <div className="min-w-0 flex-1">
                                <p className="line-clamp-2 break-words text-sm font-semibold text-white">Preview product with a longer title to demonstrate responsive wrapping</p>
                                <p className="mt-1 truncate text-xs text-neutral-500">Preview option</p>
                                <p className="mt-2 whitespace-nowrap text-xs font-semibold text-neutral-300">{formatMoney(unitPrice)}</p>
                            </div>
                            <button type="button" className="h-fit shrink-0 text-xs font-semibold text-neutral-500">Remove</button>
                        </div>

                        <div className="mt-3 flex min-w-0 items-center justify-between gap-3 border-t border-neutral-800 pt-3">
                            <div className="inline-flex shrink-0 items-center rounded-lg border border-neutral-700">
                                <button type="button" aria-label="Decrease preview quantity" className="inline-flex h-9 w-9 items-center justify-center text-neutral-300">−</button>
                                <span className="min-w-8 text-center text-xs font-bold text-white">1</span>
                                <button type="button" aria-label="Increase preview quantity" className="inline-flex h-9 w-9 items-center justify-center text-neutral-300">+</button>
                            </div>
                            <p className="min-w-0 break-words text-right text-sm font-bold text-white">{formatMoney(unitPrice)}</p>
                        </div>
                    </div>
                </div>

                <div className="shrink-0 border-t border-neutral-800 p-4">
                    <div className="flex min-w-0 items-center justify-between gap-4 text-sm">
                        <span className="shrink-0 font-medium text-neutral-400">Subtotal</span>
                        <strong className="min-w-0 break-words text-right text-base text-white">{formatMoney(unitPrice)}</strong>
                    </div>
                    <p className="mt-2 text-xs leading-5 text-neutral-500">Shipping, discounts, and taxes are calculated later in checkout.</p>
                    <button type="button" disabled className="mt-4 min-h-11 w-full rounded-lg bg-indigo-600 px-4 text-sm font-bold text-white opacity-60">Checkout coming next</button>
                </div>
            </aside>
        </div>
    )
}
