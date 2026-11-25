import AppearanceSwitcher from '@/Components/Appearance/AppearanceSwitcher'
import CustomerAvatar from '@/Components/Customer/CustomerAvatar'
import { Link, router, usePage } from '@inertiajs/react'

type Props = {
    title?: string
    onMenuClick: () => void
}

export default function CustomerHeader({ title, onMenuClick }: Props) {
    const { auth, customerAvatar } = usePage().props
    const logout = () => {
        router.post(route('logout'))
    }

    return (
        <header className="sticky top-0 z-30 border-b border-neutral-200 bg-white">
            <div className="flex h-16 min-w-0 items-center justify-between gap-2 px-3 sm:gap-4 sm:px-6 lg:px-8">
                <div className="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        onClick={onMenuClick}
                        aria-label="Open account navigation"
                        className="shrink-0 rounded-md p-2 text-neutral-600 hover:bg-neutral-100 lg:hidden"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            className="h-6 w-6"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>

                    {title && (
                        <h1 className="truncate text-lg font-semibold text-neutral-900">{title}</h1>
                    )}
                </div>

                <div className="flex shrink-0 items-center gap-2">
                    <AppearanceSwitcher />
                    <Link href={route('profile.edit')} aria-label="Edit profile" className="rounded-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900">
                        <CustomerAvatar name={auth.user.name} src={typeof customerAvatar === 'string' ? customerAvatar : null} className="h-9 w-9 text-sm" />
                    </Link>
                    <button
                        type="button"
                        onClick={logout}
                        className="shrink-0 rounded-md border border-neutral-300 px-3 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50"
                    >
                        Logout
                    </button>
                </div>
            </div>
        </header>
    )
}
