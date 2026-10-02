import CategoryGrid from '@/Components/Frontend/Catalog/CategoryGrid'
import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'
import type { StorefrontProductCategoriesSectionData } from '@/types/storefront-page'

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

    return typeof columns === 'number' && [2, 3, 4, 5, 6].includes(columns) ? columns : 4
}

function getBoolean(config: Record<string, unknown>, key: string, fallback: boolean): boolean {
    const value = config[key]

    return typeof value === 'boolean' ? value : fallback
}

export default function ProductCategoriesGridSection({ section }: StorefrontSectionProps) {
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

    const showProductCount = getBoolean(section.config, 'show_product_count', false)

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

            <CategoryGrid
                categories={categories}
                columns={columns}
                showName={showName}
                showProductCount={showProductCount}
            />
        </section>
    )
}
