import ProductImage from '@/Components/Frontend/Catalog/ProductImage'
import ProductPrice from '@/Components/Frontend/Catalog/ProductPrice'
import type { StorefrontProductCard as ProductCardData } from '@/types/storefront'
import { Link } from '@inertiajs/react'

interface ProductCardProps {
    product: ProductCardData
}

export default function ProductCard({ product }: ProductCardProps) {
    const productUrl = `/products/${product.slug}`

    return (
        <article className="group flex h-full min-w-0 flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white transition-shadow duration-200 hover:shadow-sm focus-within:ring-2 focus-within:ring-neutral-950 focus-within:ring-offset-2">
            <Link
                href={productUrl}
                className="relative block min-w-0 overflow-hidden focus-visible:outline-none"
                aria-label={`View ${product.name}`}
            >
                <div className="aspect-square overflow-hidden bg-neutral-100">
                    <ProductImage
                        image={product.image}
                        alt={product.name}
                        className="h-full w-full transition duration-300 group-hover:scale-[1.02]"
                        sizes="(min-width: 1280px) 25vw, (min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
                    />
                </div>

                {product.is_featured && (
                    <span className="absolute left-3 top-3 max-w-[calc(100%-1.5rem)] truncate rounded-full bg-neutral-950 px-2.5 py-1 text-xs font-medium text-white">
                        Featured
                    </span>
                )}
            </Link>

            <div className="flex min-w-0 flex-1 flex-col p-4">
                {product.brand && (
                    <p
                        className="min-w-0 truncate text-xs font-medium uppercase tracking-wide text-neutral-500"
                        title={product.brand.name}
                    >
                        {product.brand.name}
                    </p>
                )}

                <h2 className="mt-1 min-w-0 text-base font-medium leading-6 text-neutral-950">
                    <Link
                        href={productUrl}
                        className="line-clamp-2 block min-w-0 break-words rounded-sm underline-offset-4 transition hover:text-neutral-600 hover:underline focus-visible:outline-none"
                    >
                        {product.name}
                    </Link>
                </h2>

                {product.short_description && (
                    <p className="mt-2 min-w-0 line-clamp-2 break-words text-sm leading-5 text-neutral-600">
                        {product.short_description}
                    </p>
                )}

                <ProductPrice
                    mode="listing"
                    pricing={product.pricing}
                    className="mt-auto min-w-0 pt-4"
                />
            </div>
        </article>
    )
}
