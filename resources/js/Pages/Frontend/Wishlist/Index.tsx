import { Link, router } from '@inertiajs/react'
import { Heart } from 'lucide-react'
import { useEffect } from 'react'

import ProductGrid from '@/Components/Frontend/Catalog/ProductGrid'
import StorefrontBreadcrumbs from '@/Components/Frontend/Navigation/StorefrontBreadcrumbs'
import StorefrontSeo from '@/Components/Frontend/Seo/StorefrontSeo'
import FrontendLayout from '@/Layouts/Frontend/FrontendLayout'
import { buildBreadcrumbJsonLd } from '@/lib/storefrontSeo'
import {
    STOREFRONT_WISHLIST_CHANGED,
    type StorefrontWishlistChangedDetail,
} from '@/lib/storefrontWishlist'
import type { StorefrontBreadcrumbItem, StorefrontProductCard } from '@/types/storefront'

interface WishlistIndexProps {
    products: StorefrontProductCard[]
}

export default function Index({ products }: WishlistIndexProps) {
    const breadcrumbs: StorefrontBreadcrumbItem[] = [
        {
            label: 'Home',
            href: '/',
        },
        {
            label: 'Wishlist',
        },
    ]

    const breadcrumbJsonLd = buildBreadcrumbJsonLd(breadcrumbs)

    useEffect(() => {
        const handleChanged = (event: Event) => {
            const wishlistEvent = event as CustomEvent<StorefrontWishlistChangedDetail>

            if (wishlistEvent.detail.count === products.length) {
                return
            }

            router.reload({
                only: ['products', 'storefrontWishlist'],
            })
        }

        window.addEventListener(STOREFRONT_WISHLIST_CHANGED, handleChanged)

        return () => {
            window.removeEventListener(STOREFRONT_WISHLIST_CHANGED, handleChanged)
        }
    }, [products.length])

    const clearWishlist = () => {
        if (!window.confirm('Clear all products from your wishlist?')) {
            return
        }

        router.delete('/wishlist', {
            preserveScroll: true,
        })
    }

    return (
        <FrontendLayout>
            <StorefrontSeo
                title="Wishlist"
                description="View products saved to your wishlist."
                canonicalPath="/wishlist"
                jsonLd={breadcrumbJsonLd}
            />

            <main className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6">
                    <StorefrontBreadcrumbs items={breadcrumbs} />
                </div>

                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-medium text-neutral-500">Saved products</p>

                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-neutral-950 sm:text-3xl">
                            My Wishlist
                        </h1>

                        <p className="mt-2 text-sm text-neutral-600">
                            {products.length === 1
                                ? '1 product saved'
                                : `${products.length} products saved`}
                        </p>
                    </div>

                    {products.length > 0 && (
                        <button
                            type="button"
                            onClick={clearWishlist}
                            className="inline-flex min-h-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:border-neutral-400 hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2"
                        >
                            Clear wishlist
                        </button>
                    )}
                </div>

                {products.length > 0 ? (
                    <div className="mt-8">
                        <ProductGrid products={products} />
                    </div>
                ) : (
                    <div className="mt-8 rounded-2xl border border-dashed border-neutral-300 px-6 py-16 text-center">
                        <span className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-neutral-100 text-neutral-500">
                            <Heart className="h-6 w-6" aria-hidden="true" />
                        </span>

                        <h2 className="mt-5 text-lg font-semibold text-neutral-950">
                            Your wishlist is empty
                        </h2>

                        <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-neutral-600">
                            Save products you like and they will appear here for later.
                        </p>

                        <Link
                            href="/products"
                            className="mt-6 inline-flex min-h-10 items-center justify-center rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2"
                        >
                            Browse products
                        </Link>
                    </div>
                )}
            </main>
        </FrontendLayout>
    )
}
