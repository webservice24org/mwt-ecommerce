import ProductCollectionCard from '@/Components/Frontend/PageBuilder/Sections/ProductCollection/ProductCollectionCard'
import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'
import type { StorefrontProductCard } from '@/types/storefront'

interface ProductCollectionSectionData extends Record<string, unknown> {
    products: StorefrontProductCard[]
}

export default function ProductCollectionCardsSection({ section }: StorefrontSectionProps) {
    if (!isProductCollectionData(section.data)) {
        return null
    }

    const products = section.data.products

    if (products.length === 0) {
        return null
    }

    const title = getSectionTitle(section.config)

    const columns = getColumns(section.config.columns)

    const showPrice = getBoolean(section.config.show_price, true)

    const showBadges = getBoolean(section.config.show_badges, true)

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

            <ul
                className={[
                    'grid min-w-0 grid-cols-1 gap-6 sm:grid-cols-2',
                    getColumnClass(columns),
                ].join(' ')}
                aria-label={`${title} products`}
            >
                {products.map((product) => (
                    <li key={product.id} className="min-w-0">
                        <ProductCollectionCard
                            product={product}
                            showPrice={showPrice}
                            showBadges={showBadges}
                        />
                    </li>
                ))}
            </ul>
        </section>
    )
}

function isProductCollectionData(
    data: Record<string, unknown>,
): data is ProductCollectionSectionData {
    return Array.isArray(data.products)
}

function getSectionTitle(config: Record<string, unknown>): string {
    const title = config.title

    return typeof title === 'string' && title.trim() !== '' ? title : 'Products'
}

function getColumns(value: unknown): number {
    if (typeof value === 'number' && Number.isInteger(value) && [2, 3, 4, 5, 6].includes(value)) {
        return value
    }

    return 3
}

function getBoolean(value: unknown, fallback: boolean): boolean {
    return typeof value === 'boolean' ? value : fallback
}

function getColumnClass(columns: number): string {
    switch (columns) {
        case 2:
            return 'lg:grid-cols-2'

        case 4:
            return 'lg:grid-cols-3 xl:grid-cols-4'

        case 5:
            return 'lg:grid-cols-3 xl:grid-cols-5'

        case 6:
            return 'lg:grid-cols-3 xl:grid-cols-6'

        case 3:
        default:
            return 'lg:grid-cols-3'
    }
}
