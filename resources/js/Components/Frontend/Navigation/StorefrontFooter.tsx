import { Link } from '@inertiajs/react'

export default function StorefrontFooter() {
    const year = new Date().getFullYear()

    return (
        <footer className="border-t border-neutral-200 bg-neutral-50">
            <div className="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="min-w-0 sm:col-span-2">
                        <Link
                            href="/"
                            className="break-words text-lg font-bold tracking-tight text-neutral-950"
                        >
                            MWT Ecommerce
                        </Link>

                        <p className="mt-3 max-w-md break-words text-sm leading-6 text-neutral-600">
                            A modern ecommerce storefront built with Laravel, React and Inertia.
                        </p>
                    </div>

                    <nav aria-labelledby="footer-shop-heading">
                        <h2
                            id="footer-shop-heading"
                            className="text-sm font-semibold text-neutral-950"
                        >
                            Shop
                        </h2>

                        <div className="mt-3 flex flex-col items-start gap-2">
                            <Link
                                href="/products"
                                className="rounded-sm text-sm text-neutral-600 transition hover:text-neutral-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2"
                            >
                                All products
                            </Link>
                        </div>
                    </nav>

                    <nav aria-labelledby="footer-account-heading">
                        <h2
                            id="footer-account-heading"
                            className="text-sm font-semibold text-neutral-950"
                        >
                            Account
                        </h2>

                        <div className="mt-3 flex flex-col items-start gap-2">
                            <Link
                                href="/dashboard"
                                className="rounded-sm text-sm text-neutral-600 transition hover:text-neutral-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2"
                            >
                                My account
                            </Link>
                        </div>
                    </nav>
                </div>

                <div className="mt-10 border-t border-neutral-200 pt-6">
                    <p className="text-sm text-neutral-500">
                        © {year} MWT Ecommerce. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    )
}
