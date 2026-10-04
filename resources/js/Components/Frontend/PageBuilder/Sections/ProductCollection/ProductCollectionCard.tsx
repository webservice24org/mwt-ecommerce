import ProductImage from '@/Components/Frontend/Catalog/ProductImage'
import ProductPrice from '@/Components/Frontend/Catalog/ProductPrice'
import type { StorefrontProductCard } from '@/types/storefront'
import { Link } from '@inertiajs/react'
import { ArrowRight } from 'lucide-react'

interface ProductCollectionCardProps {
    product: StorefrontProductCard
    showPrice: boolean
    showBadges: boolean
}

export default function ProductCollectionCard({
    product,
    showPrice,
    showBadges,
}: ProductCollectionCardProps) {
    const productUrl = `/products/${product.slug}`

    return (
        <article className="group flex h-full min-w-0 flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-white transition duration-200 hover:border-neutral-300 hover:shadow-md focus-within:ring-2 focus-within:ring-neutral-950 focus-within:ring-offset-2">
            <Link
                href={productUrl}
                className="relative block min-w-0 overflow-hidden focus-visible:outline-none"
                aria-label={`View ${product.name}`}
            >
                <div className="aspect-[4/3] overflow-hidden bg-neutral-100">
                    <ProductImage
                        image={product.image}
                        alt={product.name}
                        className="h-full w-full transition duration-300 group-hover:scale-[1.03]"
                        sizes="(min-width: 1280px) 33vw, (min-width: 768px) 50vw, 100vw"
                    />
                </div>

                {showBadges && product.is_featured && (
                    <span className="absolute left-3 top-3 max-w-[calc(100%-1.5rem)] truncate rounded-full bg-neutral-950 px-2.5 py-1 text-xs font-medium text-white">
                        Featured
                    </span>
                )}
            </Link>

            <div className="flex min-w-0 flex-1 flex-col p-5 sm:p-6">
                {product.brand && (
                    <p
                        className="min-w-0 truncate text-xs font-semibold uppercase tracking-wider text-neutral-500"
                        title={product.brand.name}
                    >
                        {product.brand.name}
                    </p>
                )}

                <h3
                    className={
                        product.brand
                            ? 'mt-2 min-w-0 text-lg font-semibold leading-7 text-neutral-950'
                            : 'min-w-0 text-lg font-semibold leading-7 text-neutral-950'
                    }
                >
                    <Link
                        href={productUrl}
                        className="line-clamp-2 block min-w-0 break-words rounded-sm underline-offset-4 transition hover:text-neutral-600 hover:underline focus-visible:outline-none"
                    >
                        {product.name}
                    </Link>
                </h3>

                {product.short_description && (
                    <p className="mt-3 min-w-0 line-clamp-3 break-words text-sm leading-6 text-neutral-600">
                        {product.short_description}
                    </p>
                )}

                <div className="mt-auto pt-5">
                    {showPrice && (
                        <ProductPrice
                            mode="listing"
                            pricing={product.pricing}
                            className="mb-4 min-w-0"
                        />
                    )}

                    <Link
                        href={productUrl}
                        className="inline-flex min-w-0 items-center gap-1.5 rounded-sm text-sm font-semibold text-neutral-700 transition hover:text-neutral-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2"
                    >
                        <span>View product</span>

                        <ArrowRight
                            className="size-4 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5"
                            aria-hidden="true"
                        />
                    </Link>
                </div>
            </div>
        </article>
    )
}
