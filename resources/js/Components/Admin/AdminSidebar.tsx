import { Link, usePage } from '@inertiajs/react'
import {
    //Boxes,
    ChevronDown,
    CircleUserRound,
    ExternalLink,
    FolderTree,
    Gauge,
    Layers3,
    Package,
    PlusCircle,
    //Settings2,
    ShieldCheck,
    Store,
    Tag,
    Tags,
    X,
    ListTree,
    type LucideIcon,
} from 'lucide-react'

import { useState } from 'react'

type Props = {
    open: boolean
    onClose: () => void
}

type NavigationItem = {
    label: string
    href?: string
    match?: string
    icon: LucideIcon
    disabled?: boolean
}

type NavigationGroup = {
    label: string
    icon: LucideIcon
    match: string
    children: NavigationItem[]
}

const mainNavigation: NavigationItem[] = [
    {
        label: 'Dashboard',
        href: route('admin.dashboard'),
        match: '/admin',
        icon: Gauge,
    },
    {
        label: 'Administrators',
        href: route('admin.admins.index'),
        match: '/admin/admins',
        icon: ShieldCheck,
    },
]

const productNavigation: NavigationGroup = {
    label: 'Products',
    icon: Package,
    match: '/admin/products',
    children: [
        {
            label: 'All Products',
            href: route('admin.products.index'),
            match: '/admin/products',
            icon: Package,
        },
        {
            label: 'Add new products',
            href: route('admin.products.create'),
            match: '/admin/products/create',
            icon: PlusCircle,
        },
        {
            label: 'Categories',
            href: route('admin.categories.index'),
            match: '/admin/categories',
            icon: FolderTree,
        },
        {
            label: 'Brands',
            href: route('admin.brands.index'),
            match: '/admin/brands',
            icon: Tags,
        },
        {
            label: 'Tags',
            icon: Tag,
            disabled: true,
        },
        {
            label: 'Attributes',
            href: route('admin.attributes.index'),
            match: '/admin/attributes',
            icon: ListTree,
        },
    ],
}

export default function AdminSidebar({ open, onClose }: Props) {
    const { url } = usePage()

    const productSectionActive =
        url.startsWith('/admin/products') ||
        url.startsWith('/admin/categories') ||
        url.startsWith('/admin/brands') ||
        url.startsWith('/admin/tags') ||
        url.startsWith('/admin/attributes')

    const [productsOpen, setProductsOpen] = useState(productSectionActive)

    const showProducts = productSectionActive || productsOpen

    const isActive = (item: NavigationItem): boolean => {
        if (!item.match) {
            return false
        }

        if (item.match === '/admin') {
            return url === '/admin'
        }

        return url.startsWith(item.match)
    }

    const renderNavigationItem = (item: NavigationItem, nested = false) => {
        const Icon = item.icon
        const active = isActive(item)

        if (item.disabled || !item.href) {
            return (
                <div
                    key={item.label}
                    className={[
                        'group flex cursor-not-allowed items-center gap-3 rounded-lg text-sm font-medium text-neutral-400',
                        nested ? 'px-3 py-2' : 'px-3 py-2.5',
                    ].join(' ')}
                    title="Coming soon"
                >
                    <Icon className="h-4 w-4 shrink-0" strokeWidth={1.8} />

                    <span className="min-w-0 flex-1 truncate">{item.label}</span>

                    <span className="rounded bg-neutral-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-neutral-400">
                        Soon
                    </span>
                </div>
            )
        }

        return (
            <Link
                key={item.label}
                href={item.href}
                onClick={onClose}
                className={[
                    'group relative flex items-center gap-3 rounded-lg text-sm font-medium transition-all duration-150',
                    nested ? 'px-3 py-2' : 'px-3 py-2.5',
                    active
                        ? 'bg-neutral-900 text-white shadow-sm'
                        : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-950',
                ].join(' ')}
            >
                <Icon
                    className={[
                        'h-4 w-4 shrink-0 transition',
                        active ? 'text-white' : 'text-neutral-400 group-hover:text-neutral-700',
                    ].join(' ')}
                    strokeWidth={1.9}
                />

                <span className="min-w-0 flex-1 truncate">{item.label}</span>

                {active && <span className="absolute right-2 h-1.5 w-1.5 rounded-full bg-white" />}
            </Link>
        )
    }

    return (
        <>
            {open && (
                <button
                    type="button"
                    aria-label="Close navigation"
                    onClick={onClose}
                    className="fixed inset-0 z-40 bg-black/50 backdrop-blur-[1px] lg:hidden"
                />
            )}

            <aside
                className={[
                    'fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-neutral-200 bg-white shadow-xl transition-transform duration-200 lg:shadow-none',
                    'lg:translate-x-0',
                    open ? 'translate-x-0' : '-translate-x-full',
                ].join(' ')}
            >
                {/* Brand */}
                <div className="flex h-16 shrink-0 items-center justify-between border-b border-neutral-200 px-4">
                    <Link
                        href={route('admin.dashboard')}
                        onClick={onClose}
                        className="group flex min-w-0 items-center gap-3"
                    >
                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-neutral-900 text-white shadow-sm">
                            <Store className="h-5 w-5" strokeWidth={1.9} />
                        </div>

                        <div className="min-w-0">
                            <div className="truncate text-sm font-bold text-neutral-950">
                                MWT Admin
                            </div>

                            <div className="truncate text-[11px] text-neutral-400">
                                Ecommerce Control
                            </div>
                        </div>
                    </Link>

                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-lg p-2 text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900 lg:hidden"
                    >
                        <span className="sr-only">Close sidebar</span>

                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Navigation */}
                <nav className="flex-1 overflow-y-auto px-3 py-5">
                    {/* Main */}
                    <div>
                        <p className="mb-2 px-3 text-[11px] font-bold uppercase tracking-[0.14em] text-neutral-400">
                            Main
                        </p>

                        <div className="space-y-1">
                            {mainNavigation.map((item) => renderNavigationItem(item))}
                        </div>
                    </div>

                    {/* Catalog */}
                    <div className="mt-7">
                        <p className="mb-2 px-3 text-[11px] font-bold uppercase tracking-[0.14em] text-neutral-400">
                            Catalog
                        </p>

                        <div className="space-y-1">
                            <button
                                type="button"
                                onClick={() => setProductsOpen((current) => !current)}
                                className={[
                                    'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all',
                                    productSectionActive
                                        ? 'bg-neutral-100 text-neutral-950'
                                        : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-950',
                                ].join(' ')}
                            >
                                <productNavigation.icon
                                    className={[
                                        'h-4 w-4 shrink-0',
                                        productSectionActive
                                            ? 'text-neutral-900'
                                            : 'text-neutral-400',
                                    ].join(' ')}
                                    strokeWidth={1.9}
                                />

                                <span className="flex-1 text-left">{productNavigation.label}</span>

                                <ChevronDown
                                    className={[
                                        'h-4 w-4 text-neutral-400 transition-transform duration-200',

                                        showProducts ? 'rotate-180' : '',
                                    ].join(' ')}
                                />
                            </button>

                            <div
                                className={[
                                    'grid transition-all duration-200',
                                    showProducts ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]',
                                ].join(' ')}
                            >
                                <div className="overflow-hidden">
                                    <div className="relative ml-5 mt-1 space-y-1 border-l border-neutral-200 pl-3">
                                        {productNavigation.children.map((item) =>
                                            renderNavigationItem(item, true),
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Future section */}
                    <div className="mt-7">
                        <p className="mb-2 px-3 text-[11px] font-bold uppercase tracking-[0.14em] text-neutral-400">
                            Store
                        </p>

                        <div className="space-y-1">
                            <div className="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-neutral-400">
                                <Layers3 className="h-4 w-4" strokeWidth={1.8} />

                                <span className="flex-1">Orders</span>

                                <span className="rounded bg-neutral-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide">
                                    Soon
                                </span>
                            </div>

                            <div className="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-neutral-400">
                                <CircleUserRound className="h-4 w-4" strokeWidth={1.8} />

                                <span className="flex-1">Customers</span>

                                <span className="rounded bg-neutral-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide">
                                    Soon
                                </span>
                            </div>
                        </div>
                    </div>
                </nav>

                {/* Footer */}
                <div className="shrink-0 border-t border-neutral-200 p-3">
                    <Link
                        href="/"
                        onClick={onClose}
                        className="group flex items-center gap-3 rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-3 text-sm font-medium text-neutral-700 transition hover:border-neutral-300 hover:bg-white hover:text-neutral-950"
                    >
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-neutral-200">
                            <Store className="h-4 w-4 text-neutral-500" strokeWidth={1.9} />
                        </div>

                        <div className="min-w-0 flex-1">
                            <div>View Store</div>

                            <div className="mt-0.5 text-[11px] font-normal text-neutral-400">
                                Open storefront
                            </div>
                        </div>

                        <ExternalLink
                            className="h-4 w-4 text-neutral-400 transition group-hover:text-neutral-700"
                            strokeWidth={1.8}
                        />
                    </Link>
                </div>
            </aside>
        </>
    )
}
