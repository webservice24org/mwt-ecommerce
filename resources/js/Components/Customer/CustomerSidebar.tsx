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
]

export default function CustomerSidebar({ open, onClose }: Props) {
    const { url } = usePage()

    const isActive = (item: NavigationItem) => {
        if (item.match === '/account') {
            return url === '/account'
        }

        return url.startsWith(item.match)
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
                        className="rounded-md p-2 text-neutral-500 hover:bg-neutral-100 lg:hidden"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            className="h-5 w-5"
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

                <nav className="flex-1 overflow-y-auto p-4">
                    <div className="space-y-1">
                        {navigation.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                onClick={onClose}
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

                    <p className="mb-2 mt-8 px-3 text-xs font-semibold uppercase tracking-wider text-neutral-400">
                        Shopping
                    </p>

                    <div className="space-y-1">
                        <span className="block cursor-default rounded-lg px-3 py-2.5 text-sm text-neutral-400">
                            Orders
                        </span>

                        <span className="block cursor-default rounded-lg px-3 py-2.5 text-sm text-neutral-400">
                            Addresses
                        </span>

                        <span className="block cursor-default rounded-lg px-3 py-2.5 text-sm text-neutral-400">
                            Profile
                        </span>
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
