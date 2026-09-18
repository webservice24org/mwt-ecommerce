import ProductImage from '@/Components/Frontend/Catalog/ProductImage'
import ProductPrice from '@/Components/Frontend/Catalog/ProductPrice'
import type { StorefrontProductCard as ProductCardData } from '@/types/storefront'
import { Link } from '@inertiajs/react'

interface ProductCardProps {
    product: ProductCardData
    eagerImage?: boolean
}

export default function ProductCard({ product, eagerImage = false }: ProductCardProps) {
    const productUrl = `/products/${product.slug}`

    return (
        <article className="group flex h-full min-w-0 flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white transition-shadow duration-200 hover:shadow-sm focus-within:ring-2 focus-within:ring-neutral-950 focus-within:ring-offset-2">
            <Link
                href={productUrl}
                className="relative block overflow-hidden"
                aria-label={`View ${product.name}`}
            >
                <ProductImage
                    image={product.image}
                    productName={product.name}
                    eager={eagerImage}
                    sizes="(min-width: 1280px) 25vw, (min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
                    className="transition-transform duration-300 group-hover:scale-[1.02]"
                />

                {product.is_featured && (
                    <span className="absolute left-3 top-3 rounded-full bg-neutral-950 px-2.5 py-1 text-xs font-medium text-white">
                        Featured
                    </span>
                )}
            </Link>

            <div className="flex flex-1 flex-col p-4">
                {product.brand && (
                    <p className="truncate text-xs font-medium uppercase tracking-wide text-neutral-500">
                        {product.brand.name}
                    </p>
                )}

                <h2 className="mt-1 text-base font-medium leading-6 text-neutral-950">
                    <Link
                        href={productUrl}
                        className="line-clamp-2 rounded-sm transition-colors hover:text-neutral-600 focus-visible:outline-none"
                    >
                        {product.name}
                    </Link>
                </h2>

                {product.short_description && (
                    <p className="mt-2 line-clamp-2 text-sm leading-5 text-neutral-600">
                        {product.short_description}
                    </p>
                )}

                <ProductPrice mode="listing" pricing={product.pricing} className="mt-auto pt-4" />
            </div>
        </article>
    )
}
