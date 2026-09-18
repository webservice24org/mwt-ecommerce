import ProductPrice from '@/Components/Frontend/Catalog/ProductPrice'
import type { StorefrontProductDetail } from '@/types/storefront'
import VariableProductPanel from '@/Components/Frontend/Catalog/ProductDetails/VariableProductPanel'
import { Link } from '@inertiajs/react'

interface ProductSummaryProps {
    product: StorefrontProductDetail
}

export default function ProductSummary({ product }: ProductSummaryProps) {
    return (
        <div className="space-y-6">
            <div>
                {product.brand && (
                    <p className="mb-2 text-sm font-medium text-neutral-500">
                        {product.brand.name}
                    </p>
                )}

                <h1 className="text-3xl font-bold tracking-tight text-neutral-950 sm:text-4xl">
                    {product.name}
                </h1>

                {product.short_description && (
                    <p className="mt-4 max-w-2xl text-base leading-7 text-neutral-600">
                        {product.short_description}
                    </p>
                )}
            </div>

            {product.type === 'simple' && (
                <div className="border-y border-neutral-200 py-5">
                    <ProductPrice mode="resolved" pricing={product.pricing} />
                </div>
            )}

            <ProductMetadata product={product} />

            {product.type === 'simple' ? (
                <SimpleProductPanel product={product} />
            ) : (
                <VariableProductPanel product={product} />
            )}
        </div>
    )
}

function ProductMetadata({ product }: ProductSummaryProps) {
    return (
        <dl className="space-y-3 text-sm">
            {product.type === 'simple' && product.sku && (
                <div className="flex gap-3">
                    <dt className="w-24 shrink-0 font-medium text-neutral-500">SKU</dt>

                    <dd className="min-w-0 break-all text-neutral-900">{product.sku}</dd>
                </div>
            )}

            {product.brand && (
                <div className="flex gap-3">
                    <dt className="w-24 shrink-0 font-medium text-neutral-500">Brand</dt>

                    <dd className="text-neutral-900">{product.brand.name}</dd>
                </div>
            )}

            {product.categories.length > 0 && (
                <div className="flex gap-3">
                    <dt className="w-24 shrink-0 font-medium text-neutral-500">Categories</dt>

                    <dd className="min-w-0 flex flex-wrap gap-x-2 gap-y-1">
                        {product.categories.map((category) => (
                            <Link
                                key={category.id}
                                href={`/category/${category.slug}`}
                                className="text-neutral-900 underline-offset-4 hover:underline"
                            >
                                {category.name}
                            </Link>
                        ))}
                    </dd>
                </div>
            )}
        </dl>
    )
}

function SimpleProductPanel({ product }: ProductSummaryProps) {
    return (
        <section
            className="rounded-xl border border-neutral-200 bg-neutral-50 p-4"
            aria-labelledby="product-purchase-heading"
        >
            <h2 id="product-purchase-heading" className="font-semibold text-neutral-950">
                Purchase
            </h2>

            <p className="mt-2 text-sm leading-6 text-neutral-600">
                {product.pricing.price !== null
                    ? 'This product is available for purchase.'
                    : 'Price is currently unavailable.'}
            </p>

            <button
                type="button"
                disabled
                className="mt-4 w-full rounded-lg bg-neutral-900 px-4 py-3 text-sm font-semibold text-white opacity-50"
            >
                Add to cart
            </button>

            <p className="mt-2 text-xs text-neutral-500">
                Cart functionality will be enabled in the cart phase.
            </p>
        </section>
    )
}
