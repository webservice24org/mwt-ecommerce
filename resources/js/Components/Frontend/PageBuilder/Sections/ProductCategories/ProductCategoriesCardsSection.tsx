import CategoryVisualCard from '@/Components/Frontend/Catalog/CategoryVisualCard'
import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'
import type { StorefrontProductCategoriesSectionData } from '@/types/storefront-page'

const columnClasses: Record<number, string> = {
    2: 'sm:grid-cols-2',
    3: 'sm:grid-cols-2 lg:grid-cols-3',
    4: 'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
    5: 'sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5',
    6: 'sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6',
}

function isProductCategoriesData(
    data: Record<string, unknown>,
): data is StorefrontProductCategoriesSectionData {
    return Array.isArray(data.categories)
}

function getSectionTitle(config: Record<string, unknown>): string {
    const title = config.title

    return typeof title === 'string' && title.trim() !== '' ? title : 'Shop by Category'
}

function getColumns(config: Record<string, unknown>): number {
    const columns = config.columns

    return typeof columns === 'number' && [2, 3, 4, 5, 6].includes(columns) ? columns : 3
}

function getBoolean(config: Record<string, unknown>, key: string, fallback: boolean): boolean {
    const value = config[key]

    return typeof value === 'boolean' ? value : fallback
}

export default function ProductCategoriesCardsSection({ section }: StorefrontSectionProps) {
    if (!isProductCategoriesData(section.data)) {
        return null
    }

    const categories = section.data.categories

    if (categories.length === 0) {
        return null
    }

    const title = getSectionTitle(section.config)

    const columns = getColumns(section.config)

    const showName = getBoolean(section.config, 'show_name', true)

    const showProductCount = getBoolean(section.config, 'show_product_count', true)

    const columnsClass = columnClasses[columns] ?? columnClasses[3]

    return (
        <section aria-labelledby={`section-${section.id}-title`} className="min-w-0 py-8 sm:py-10">
            <div className="mb-6 min-w-0">
                <h2
                    id={`section-${section.id}-title`}
                    className="break-words text-2xl font-semibold tracking-tight text-neutral-950 sm:text-3xl"
                >
                    {title}
                </h2>
            </div>

            <ul
                className={['grid min-w-0 grid-cols-1 gap-4 sm:gap-5', columnsClass].join(' ')}
                aria-label="Product categories"
            >
                {categories.map((category) => (
                    <li key={category.id} className="min-w-0">
                        <CategoryVisualCard
                            category={category}
                            showName={showName}
                            showProductCount={showProductCount}
                        />
                    </li>
                ))}
            </ul>
        </section>
    )
}
