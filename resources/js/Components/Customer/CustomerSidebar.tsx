import CustomerAvatar from '@/Components/Customer/CustomerAvatar'
import { Link, usePage } from '@inertiajs/react'

type Props = {
    open: boolean
    onClose: () => void
}

type NavigationItem = {
    label: string
    href: string
    match: string
}

const navigation: NavigationItem[] = [
    {
        label: 'Dashboard',
        href: route('customer.dashboard'),
        match: '/account',
    },
    {
        label: 'Addresses',
        href: route('customer.addresses.index'),
        match: '/account/addresses',
    },
    {
        label: 'Orders',
        href: route('customer.orders.index'),
        match: '/account/orders',
    },
    {
        label: 'Profile',
        href: route('profile.edit'),
        match: '/profile',
    },
]

export default function CustomerSidebar({ open, onClose }: Props) {
    const { url, props } = usePage()
    const user = props.auth.user
    const avatarUrl = typeof props.customerAvatar === 'string' ? props.customerAvatar : null

    const isActive = (item: NavigationItem) => {
        if (item.match === '/account') {
            return url.split('?')[0] === '/account'
        }

        const pathname = url.split('?')[0]
        return pathname === item.match || pathname.startsWith(`${item.match}/`)
    }

    return (
        <>
            {open && (
                <button
                    type="button"
                    aria-label="Close navigation"
                    onClick={onClose}
                    className="fixed inset-0 z-40 bg-black/40 lg:hidden"
                />
            )}

            <aside
                className={[
                    'fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-neutral-200 bg-white transition-transform duration-200',
                    'lg:translate-x-0',
                    open ? 'translate-x-0' : '-translate-x-full',
                ].join(' ')}
            >
                <div className="flex h-16 items-center justify-between border-b border-neutral-200 px-5">
                    <Link
                        href={route('customer.dashboard')}
                        className="text-lg font-bold text-neutral-900"
                        onClick={onClose}
                    >
                        My Account
                    </Link>

                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Close navigation"
                        className="rounded-md p-2 text-neutral-500 hover:bg-neutral-100 lg:hidden"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            className="h-5 w-5"
                            aria-hidden="true"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <Link href={route('profile.edit')} onClick={onClose} className="flex items-center gap-3 border-b border-neutral-200 p-4 hover:bg-neutral-50">
                    <CustomerAvatar name={user.name} src={avatarUrl} />
                    <span className="min-w-0">
                        <span className="block truncate text-sm font-semibold text-neutral-900">{user.name}</span>
                        <span className="block text-xs text-neutral-500">Edit profile</span>
                    </span>
                </Link>

                <nav className="flex-1 overflow-y-auto p-4" aria-label="Customer account">
                    <div className="space-y-1">
                        {navigation.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                onClick={onClose}
                                aria-current={isActive(item) ? 'page' : undefined}
                                className={[
                                    'block rounded-lg px-3 py-2.5 text-sm font-medium transition',
                                    isActive(item)
                                        ? 'bg-neutral-900 text-white'
                                        : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900',
                                ].join(' ')}
                            >
                                {item.label}
                            </Link>
                        ))}
                    </div>
                </nav>

                <div className="border-t border-neutral-200 p-4">
                    <Link
                        href="/"
                        className="block rounded-lg px-3 py-2 text-sm text-neutral-600 hover:bg-neutral-100"
                    >
                        Continue Shopping
                    </Link>
                </div>
            </aside>
        </>
    )
}
