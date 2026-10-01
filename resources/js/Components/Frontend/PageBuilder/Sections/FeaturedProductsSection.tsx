import ProductGrid from '@/Components/Frontend/Catalog/ProductGrid'
import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'
import type { StorefrontFeaturedProductsSectionData } from '@/types/storefront-page'

function isFeaturedProductsData(
    data: Record<string, unknown>,
): data is StorefrontFeaturedProductsSectionData {
    return Array.isArray(data.products)
}

function getSectionTitle(config: Record<string, unknown>): string {
    const title = config.title

    return typeof title === 'string' && title.trim() !== '' ? title : 'Featured Products'
}

export default function FeaturedProductsSection({ section }: StorefrontSectionProps) {
    if (!isFeaturedProductsData(section.data)) {
        return null
    }

    const title = getSectionTitle(section.config)
    const products = section.data.products

    if (products.length === 0) {
        return null
    }

    return (
        <section aria-labelledby={`section-${section.id}-title`} className="py-8 sm:py-10">
            <div className="mb-6 flex items-end justify-between gap-4">
                <h2
                    id={`section-${section.id}-title`}
                    className="text-2xl font-semibold tracking-tight text-neutral-950 sm:text-3xl"
                >
                    {title}
                </h2>
            </div>

            <ProductGrid products={products} />
        </section>
    )
}
