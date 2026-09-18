import type { NavigationItem } from '@/Components/Frontend/Navigation/StorefrontHeader'
import { Link } from '@inertiajs/react'
import { X } from 'lucide-react'
import { useEffect } from 'react'

interface Props {
    open: boolean
    currentUrl: string
    navigation: NavigationItem[]
    onClose: () => void
}

export default function StorefrontMobileNav({ open, currentUrl, navigation, onClose }: Props) {
    useEffect(() => {
        if (!open) {
            return
        }

        const previousOverflow = document.body.style.overflow

        document.body.style.overflow = 'hidden'

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose()
            }
        }

        window.addEventListener('keydown', handleKeyDown)

        return () => {
            document.body.style.overflow = previousOverflow
            window.removeEventListener('keydown', handleKeyDown)
        }
    }, [open, onClose])

    if (!open) {
        return null
    }

    return (
        <div
            id="storefront-mobile-navigation"
            className="fixed inset-0 z-50 w-full max-w-full overflow-hidden lg:hidden"
        >
            <button
                type="button"
                className="absolute inset-0 bg-black/40"
                aria-label="Close navigation"
                onClick={onClose}
            />

            <div
                role="dialog"
                aria-modal="true"
                aria-label="Mobile navigation"
                className="relative flex h-full w-80 max-w-[85vw] flex-col overflow-x-hidden bg-white shadow-xl"
            >
                <div className="flex h-16 min-w-0 items-center gap-3 border-b border-neutral-200 px-4">
                    <Link
                        href="/"
                        className="min-w-0 flex-1 truncate font-bold text-neutral-950"
                        onClick={onClose}
                    >
                        MWT Ecommerce
                    </Link>

                    <button
                        type="button"
                        className="inline-flex size-10 shrink-0 items-center justify-center rounded-md text-neutral-700 hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
                        aria-label="Close navigation"
                        onClick={onClose}
                    >
                        <X className="size-5" aria-hidden="true" />
                    </button>
                </div>

                <nav
                    className="flex flex-1 flex-col gap-1 overflow-y-auto p-4"
                    aria-label="Mobile navigation"
                >
                    {navigation.map((item) => {
                        const isActive = item.active(currentUrl)

                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                aria-current={isActive ? 'page' : undefined}
                                onClick={onClose}
                                className={[
                                    'rounded-md px-3 py-3 text-sm font-medium transition',
                                    isActive
                                        ? 'bg-neutral-100 text-neutral-950'
                                        : 'text-neutral-700 hover:bg-neutral-50 hover:text-neutral-950',
                                ].join(' ')}
                            >
                                {item.label}
                            </Link>
                        )
                    })}
                </nav>

                <div className="border-t border-neutral-200 p-4">
                    <Link
                        href="/dashboard"
                        onClick={onClose}
                        className="flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-neutral-700 hover:bg-neutral-100"
                    >
                        My account
                    </Link>
                </div>
            </div>
        </div>
    )
}
