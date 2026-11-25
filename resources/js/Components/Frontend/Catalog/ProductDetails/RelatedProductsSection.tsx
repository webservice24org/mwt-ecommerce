import ProductGrid from '@/Components/Frontend/Catalog/ProductGrid'
import type { StorefrontProductCard } from '@/types/storefront'

export default function RelatedProductsSection({ products }: { products: StorefrontProductCard[] }) {
    if (products.length === 0) return null

    return (
        <section aria-labelledby="related-products-heading" className="mt-12 min-w-0 border-t border-neutral-200 pt-8 lg:mt-16 lg:pt-10">
            <div className="mb-6">
                <h2 id="related-products-heading" className="text-2xl font-bold tracking-tight text-neutral-950 sm:text-3xl">
                    Related products
                </h2>
                <p className="mt-2 text-sm text-neutral-600">You may also like these products.</p>
            </div>
            <ProductGrid products={products} columns={4} />
        </section>
    )
}
