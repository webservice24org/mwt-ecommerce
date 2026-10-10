import { ChevronDown, Heart, Menu, Search, ShoppingBag, X } from 'lucide-react'
import { useState } from 'react'

import type { HeaderConfig, HeaderPreviewDevice } from '@/types/header-builder'

interface Props {
    config: HeaderConfig
    isEnabled: boolean
    device: HeaderPreviewDevice
}

export default function HeaderDesign1Preview({ config, isEnabled, device }: Props) {
    const [searchOpen, setSearchOpen] = useState(false)

    const [megaMenuOpen, setMegaMenuOpen] = useState(false)

    const [accountOpen, setAccountOpen] = useState(false)

    const [mobileMenuOpen, setMobileMenuOpen] = useState(false)

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

    const handleAccountToggle = () => {
        const next = !accountOpen

        closeTransientMenus()
        setAccountOpen(next)
    }

    const handleMegaMenuToggle = () => {
        const next = !megaMenuOpen

        closeTransientMenus()
        setMegaMenuOpen(next)
    }

    const handleMobileMenuToggle = () => {
        const next = !mobileMenuOpen

        closeTransientMenus()
        setMobileMenuOpen(next)
    }

    return (
        <div
            className={[
                'w-full min-w-0 overflow-visible rounded-2xl border border-neutral-800 bg-neutral-950 shadow-2xl transition-opacity',
                isEnabled ? 'opacity-100' : 'pointer-events-none opacity-40',
            ].join(' ')}
        >
            <AnnouncementBar config={config} device={device} />

            <MainHeader
                config={config}
                device={device}
                searchOpen={searchOpen}
                accountOpen={accountOpen}
                mobileMenuOpen={mobileMenuOpen}
                onSearchToggle={handleSearchToggle}
                onAccountToggle={handleAccountToggle}
                onMobileMenuToggle={handleMobileMenuToggle}
            />

            {device === 'mobile' ? (
                mobileMenuOpen && (
                    <MobileMenu config={config} onClose={() => setMobileMenuOpen(false)} />
                )
            ) : (
                <>
                    <DesktopNavigation
                        config={config}
                        device={device}
                        megaMenuOpen={megaMenuOpen}
                        onMegaMenuToggle={handleMegaMenuToggle}
                    />

                    {device === 'tablet' && mobileMenuOpen && (
                        <MobileMenu config={config} onClose={() => setMobileMenuOpen(false)} />
                    )}

                    <MegaMenu config={config} device={device} open={megaMenuOpen} />
                </>
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
                    'mx-auto flex w-full max-w-7xl items-center px-4 py-2',
                    device === 'mobile'
                        ? 'min-h-8 justify-center'
                        : 'min-h-9 justify-between gap-6 sm:px-6 lg:px-8',
                ].join(' ')}
            >
                <div
                    className={[
                        'flex min-w-0 items-center gap-2',
                        device === 'mobile' ? 'justify-center' : '',
                    ].join(' ')}
                >
                    {config.announcement.badge && (
                        <span className="shrink-0 rounded-full bg-white/20 px-2 py-0.5 text-[8px] font-extrabold uppercase tracking-wide text-white">
                            {config.announcement.badge}
                        </span>
                    )}

                    <p
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
                    <div className="flex shrink-0 items-center gap-6 text-[10px] text-indigo-100">
                        {config.announcement.links
                            .filter((link) => link.label.trim() !== '')
                            .map((link, index) => (
                                <span key={`${link.label}-${index}`} className="whitespace-nowrap">
                                    {link.label}
                                </span>
                            ))}

                        {config.announcement.currency_label && (
                            <span className="inline-flex items-center gap-1 whitespace-nowrap">
                                {config.announcement.currency_label}

                                <ChevronDown className="h-3 w-3" aria-hidden="true" />
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
    searchOpen,
    accountOpen,
    mobileMenuOpen,
    onSearchToggle,
    onAccountToggle,
    onMobileMenuToggle,
}: {
    config: HeaderConfig
    device: HeaderPreviewDevice
    searchOpen: boolean
    accountOpen: boolean
    mobileMenuOpen: boolean
    onSearchToggle: () => void
    onAccountToggle: () => void
    onMobileMenuToggle: () => void
}) {
    const compact = device === 'tablet' || device === 'mobile'

    return (
        <div className="relative z-30 w-full border-b border-neutral-800 bg-neutral-900">
            <div
                className={[
                    'mx-auto flex w-full max-w-7xl items-center',
                    device === 'mobile'
                        ? 'min-h-16 gap-2 px-3'
                        : 'min-h-20 gap-5 px-4 sm:px-6 lg:px-8',
                ].join(' ')}
            >
                {compact && (
                    <button
                        type="button"
                        aria-label={mobileMenuOpen ? 'Close menu preview' : 'Open menu preview'}
                        aria-expanded={mobileMenuOpen}
                        onClick={onMobileMenuToggle}
                        className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-neutral-400 transition hover:bg-neutral-800 hover:text-white"
                    >
                        {mobileMenuOpen ? (
                            <X className="h-5 w-5" strokeWidth={1.9} aria-hidden="true" />
                        ) : (
                            <Menu className="h-5 w-5" strokeWidth={1.9} aria-hidden="true" />
                        )}
                    </button>
                )}

                <Brand config={config} device={device} />

                {device !== 'mobile' && (
                    <SearchPreview
                        config={config}
                        device={device}
                        open={searchOpen}
                        onToggle={onSearchToggle}
                    />
                )}

                <HeaderActions
                    config={config}
                    device={device}
                    accountOpen={accountOpen}
                    onAccountToggle={onAccountToggle}
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
                <div
                    className={[
                        'flex shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white',
                        logoSize,
                    ].join(' ')}
                >
                    <img
                        src={config.brand.logo_url}
                        alt=""
                        className="h-full w-full object-contain"
                    />
                </div>
            ) : (
                <div
                    className={[
                        'flex shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-sm font-black text-white shadow-lg shadow-indigo-600/20',
                        logoSize,
                    ].join(' ')}
                >
                    {config.brand.fallback_mark || 'A'}
                </div>
            )}

            {device === 'desktop' && (
                <p className="whitespace-nowrap text-xl font-black tracking-tight text-white">
                    {config.brand.name}

                    <span className="text-indigo-500">{config.brand.accent ?? ''}</span>
                </p>
            )}
        </div>
    )
}

function SearchPreview({
    config,
    device,
    open,
    onToggle,
}: {
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
        <div
            className={[
                'relative mx-auto min-w-0 flex-1',
                device === 'desktop' ? 'max-w-xl' : 'max-w-sm',
            ].join(' ')}
        >
            <button
                type="button"
                aria-expanded={open}
                onClick={onToggle}
                className={[
                    'flex min-h-11 w-full min-w-0 items-center gap-3 rounded-xl border bg-neutral-950 px-4 text-left transition',
                    open
                        ? 'border-indigo-500 ring-2 ring-indigo-500/20'
                        : 'border-neutral-700 hover:border-neutral-600',
                ].join(' ')}
            >
                <Search
                    className="h-4 w-4 shrink-0 text-neutral-500"
                    strokeWidth={1.9}
                    aria-hidden="true"
                />

                <span className="min-w-0 flex-1 truncate text-xs text-neutral-500">
                    {config.search.placeholder}
                </span>

                <span className="rounded-md border border-neutral-700 bg-neutral-900 px-1.5 py-0.5 text-[9px] font-semibold text-neutral-500">
                    /
                </span>
            </button>

            {open && config.search.suggestions_enabled && (
                <div className="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-xl border border-neutral-700 bg-neutral-900 shadow-2xl">
                    <div className="p-4">
                        <div className="flex items-center justify-between gap-3">
                            <p className="text-[10px] font-bold uppercase tracking-[0.12em] text-neutral-500">
                                {config.search.suggestion_heading}
                            </p>

                            <button
                                type="button"
                                aria-label="Close search suggestions"
                                onClick={onToggle}
                                className="inline-flex h-7 w-7 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-neutral-800 hover:text-white"
                            >
                                <X className="h-3.5 w-3.5" aria-hidden="true" />
                            </button>
                        </div>

                        {suggestions.length > 0 ? (
                            <div className="mt-3 flex flex-wrap gap-2">
                                {suggestions.map((item, index) => (
                                    <button
                                        type="button"
                                        key={`${item}-${index}`}
                                        className="rounded-full border border-neutral-700 bg-neutral-800 px-2.5 py-1.5 text-[10px] font-medium text-neutral-300 transition hover:border-indigo-500 hover:text-white"
                                    >
                                        {item}
                                    </button>
                                ))}
                            </div>
                        ) : (
                            <p className="mt-3 text-xs text-neutral-500">
                                No search suggestions configured.
                            </p>
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
    accountOpen,
    onAccountToggle,
}: {
    config: HeaderConfig
    device: HeaderPreviewDevice
    accountOpen: boolean
    onAccountToggle: () => void
}) {
    return (
        <div className="ml-auto flex shrink-0 items-center gap-2">
            {device !== 'mobile' && config.actions.wishlist.enabled && (
                <button
                    type="button"
                    aria-label="Wishlist preview"
                    className="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-950 text-neutral-300"
                >
                    <Heart className="h-4 w-4" strokeWidth={1.9} aria-hidden="true" />

                    <PreviewCount>4</PreviewCount>
                </button>
            )}

            {config.actions.cart.enabled && (
                <button
                    type="button"
                    aria-label="Cart preview"
                    className="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-950 text-neutral-300"
                >
                    <ShoppingBag className="h-4 w-4" strokeWidth={1.9} aria-hidden="true" />

                    <PreviewCount>2</PreviewCount>
                </button>
            )}

            {device !== 'mobile' && config.actions.account.enabled && (
                <div className="relative">
                    <button
                        type="button"
                        aria-label="Account menu preview"
                        aria-expanded={accountOpen}
                        onClick={onAccountToggle}
                        className={[
                            'inline-flex min-h-10 items-center gap-2 rounded-xl border bg-neutral-950 px-2.5 text-neutral-300 transition',
                            accountOpen
                                ? 'border-indigo-500'
                                : 'border-neutral-800 hover:border-neutral-700',
                        ].join(' ')}
                    >
                        <span className="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-600 text-[9px] font-black text-white">
                            JD
                        </span>

                        <ChevronDown
                            className={[
                                'h-3.5 w-3.5 text-neutral-500 transition-transform',
                                accountOpen ? 'rotate-180' : '',
                            ].join(' ')}
                            strokeWidth={1.9}
                            aria-hidden="true"
                        />
                    </button>

                    {accountOpen && <AccountDropdown config={config} onClose={onAccountToggle} />}
                </div>
            )}
        </div>
    )
}

function AccountDropdown({ config, onClose }: { config: HeaderConfig; onClose: () => void }) {
    const links = config.actions.account.menu_links.filter((link) => link.label.trim() !== '')

    return (
        <div className="absolute right-0 top-full z-50 mt-2 w-64 overflow-hidden rounded-xl border border-neutral-700 bg-neutral-900 shadow-2xl">
            <div className="flex items-start justify-between gap-3 border-b border-neutral-800 p-4">
                <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-white">John Doe</p>

                    <p className="mt-1 text-[10px] text-neutral-500">Preview customer</p>
                </div>

                <button
                    type="button"
                    aria-label="Close account preview"
                    onClick={onClose}
                    className="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-neutral-800 hover:text-white"
                >
                    <X className="h-3.5 w-3.5" aria-hidden="true" />
                </button>
            </div>

            <div className="p-2">
                {links.map((link, index) => (
                    <button
                        type="button"
                        key={`${link.label}-${index}`}
                        className="flex min-h-9 w-full items-center rounded-lg px-3 text-left text-xs font-medium text-neutral-300 transition hover:bg-neutral-800 hover:text-white"
                    >
                        {link.label}
                    </button>
                ))}

                {links.length === 0 && (
                    <p className="px-3 py-2 text-xs text-neutral-500">
                        No account links configured.
                    </p>
                )}
            </div>
        </div>
    )
}

function PreviewCount({ children }: { children: React.ReactNode }) {
    return (
        <span className="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-indigo-600 px-1 text-[8px] font-bold text-white">
            {children}
        </span>
    )
}

function MobileMenu({ config, onClose }: { config: HeaderConfig; onClose: () => void }) {
    return (
        <div className="w-full border-b border-neutral-800 bg-neutral-950 p-3">
            <div className="mb-4 flex items-center justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-[10px] font-bold uppercase tracking-[0.1em] text-neutral-500">
                        Menu
                    </p>

                    <p className="mt-1 text-sm font-semibold text-white">Browse store</p>
                </div>

                <button
                    type="button"
                    aria-label="Close mobile menu preview"
                    onClick={onClose}
                    className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-neutral-800 bg-neutral-900 text-neutral-400 transition hover:text-white"
                >
                    <X className="h-4 w-4" aria-hidden="true" />
                </button>
            </div>

            {config.mobile.search_enabled && (
                <div className="mb-4 flex min-h-11 items-center gap-2 rounded-xl border border-neutral-800 bg-neutral-900 px-3">
                    <Search
                        className="h-4 w-4 shrink-0 text-neutral-500"
                        strokeWidth={1.9}
                        aria-hidden="true"
                    />

                    <span className="min-w-0 flex-1 truncate text-xs text-neutral-500">
                        {config.mobile.search_placeholder}
                    </span>
                </div>
            )}

            <div className="space-y-2">
                {config.mobile.menu_links
                    .filter((link) => link.label.trim() !== '')
                    .map((link, index) => (
                        <button
                            type="button"
                            key={`${link.label}-${index}`}
                            className={[
                                'flex min-h-11 w-full items-center justify-between rounded-xl px-3 text-left text-sm font-semibold transition',
                                link.style === 'primary'
                                    ? 'bg-indigo-600 text-white'
                                    : link.style === 'highlight'
                                      ? 'border border-amber-500/30 bg-amber-500/10 text-amber-400'
                                      : 'border border-neutral-800 bg-neutral-900 text-neutral-300 hover:bg-neutral-800',
                            ].join(' ')}
                        >
                            <span className="min-w-0 truncate">{link.label}</span>

                            <ChevronDown
                                className="-rotate-90 h-3.5 w-3.5 shrink-0 opacity-60"
                                aria-hidden="true"
                            />
                        </button>
                    ))}
            </div>

            {config.actions.account.enabled && (
                <div className="mt-4 border-t border-neutral-800 pt-4">
                    <div className="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            className="flex min-h-10 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-900 px-3 text-xs font-semibold text-neutral-300 transition hover:bg-neutral-800"
                        >
                            Sign in
                        </button>

                        <button
                            type="button"
                            className="flex min-h-10 items-center justify-center rounded-xl bg-white px-3 text-xs font-bold text-neutral-950 transition hover:bg-neutral-200"
                        >
                            Register
                        </button>
                    </div>
                </div>
            )}
        </div>
    )
}

function DesktopNavigation({
    config,
    device,
    megaMenuOpen,
    onMegaMenuToggle,
}: {
    config: HeaderConfig
    device: HeaderPreviewDevice
    megaMenuOpen: boolean
    onMegaMenuToggle: () => void
}) {
    if (device !== 'desktop' || !config.navigation.enabled) {
        return null
    }

    return (
        <div className="w-full border-b border-neutral-800 bg-neutral-950">
            <div className="mx-auto flex min-h-12 w-full max-w-7xl items-center gap-7 px-4 sm:px-6 lg:px-8">
                <button
                    type="button"
                    aria-expanded={megaMenuOpen}
                    onClick={onMegaMenuToggle}
                    className={[
                        'inline-flex min-h-9 shrink-0 items-center gap-2 rounded-lg px-3 text-xs font-bold text-white transition',
                        megaMenuOpen ? 'bg-violet-600' : 'bg-indigo-600 hover:bg-indigo-500',
                    ].join(' ')}
                >
                    {megaMenuOpen ? (
                        <X className="h-4 w-4" strokeWidth={1.9} aria-hidden="true" />
                    ) : (
                        <Menu className="h-4 w-4" strokeWidth={1.9} aria-hidden="true" />
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

                <div className="flex min-w-0 flex-1 items-center gap-7 overflow-hidden">
                    {config.navigation.links
                        .filter((link) => link.label.trim() !== '')
                        .map((link, index) => (
                            <button
                                type="button"
                                key={`${link.label}-${index}`}
                                className={[
                                    'shrink-0 whitespace-nowrap text-xs font-semibold transition-colors',
                                    link.style === 'highlight'
                                        ? 'text-amber-400 hover:text-amber-300'
                                        : 'text-neutral-300 hover:text-white',
                                ].join(' ')}
                            >
                                {link.label}
                            </button>
                        ))}
                </div>
            </div>
        </div>
    )
}

function MegaMenu({
    config,
    device,
    open,
}: {
    config: HeaderConfig
    device: HeaderPreviewDevice
    open: boolean
}) {
    if (device !== 'desktop' || !config.navigation.enabled || !config.mega_menu.enabled || !open) {
        return null
    }

    return (
        <div className="w-full border-b border-neutral-800 bg-neutral-900">
            <div className="mx-auto grid w-full max-w-7xl grid-cols-12 gap-8 px-4 py-7 sm:px-6 lg:px-8">
                <div className="col-span-8 grid min-w-0 grid-cols-2 gap-x-8 gap-y-6 xl:grid-cols-3">
                    {config.mega_menu.groups
                        .filter(
                            (group) =>
                                group.heading.trim() !== '' ||
                                group.links.some((link) => link.label.trim() !== ''),
                        )
                        .map((group, groupIndex) => (
                            <div key={`${group.heading}-${groupIndex}`} className="min-w-0">
                                <p className="text-xs font-bold uppercase tracking-[0.08em] text-white">
                                    {group.heading}
                                </p>

                                <div className="mt-3 space-y-2.5">
                                    {group.links
                                        .filter((link) => link.label.trim() !== '')
                                        .map((link, linkIndex) => (
                                            <button
                                                type="button"
                                                key={`${link.label}-${linkIndex}`}
                                                className="block w-full truncate text-left text-xs text-neutral-400 transition hover:text-white"
                                            >
                                                {link.label}
                                            </button>
                                        ))}
                                </div>
                            </div>
                        ))}
                </div>

                {config.mega_menu.promotion.enabled && (
                    <div className="col-span-4 min-w-0">
                        <div className="flex h-full min-h-44 flex-col justify-between rounded-2xl border border-indigo-500/20 bg-gradient-to-br from-indigo-950 via-violet-950 to-neutral-950 p-5">
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
                                <button
                                    type="button"
                                    className="mt-5 inline-flex min-h-9 w-fit items-center rounded-lg bg-white px-3 text-xs font-bold text-neutral-950 transition hover:bg-neutral-200"
                                >
                                    {config.mega_menu.promotion.button_label}
                                </button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </div>
    )
}
