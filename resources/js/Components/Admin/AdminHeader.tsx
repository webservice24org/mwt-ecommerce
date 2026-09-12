import { router } from '@inertiajs/react'

type Props = {
    title?: string
    onMenuClick: () => void
}

export default function AdminHeader({ title, onMenuClick }: Props) {
    const logout = () => {
        router.post(route('admin.logout'))
    }

    return (
        <header className="sticky top-0 z-30 border-b border-neutral-200 bg-white/95 backdrop-blur">
            <div className="flex h-16 min-w-0 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <div className="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        onClick={onMenuClick}
                        className="shrink-0 rounded-md p-2 text-neutral-600 hover:bg-neutral-100 lg:hidden"
                    >
                        <span className="sr-only">Open navigation</span>

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
                    <button
                        type="button"
                        onClick={logout}
                        className="rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50"
                    >
                        Logout
                    </button>
                </div>
            </div>
        </header>
    )
}
