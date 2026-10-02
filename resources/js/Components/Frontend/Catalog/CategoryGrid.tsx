import CategoryCard from '@/Components/Frontend/Catalog/CategoryCard'
import type { StorefrontPageBuilderCategory } from '@/types/storefront-page'

interface CategoryGridProps {
    categories: StorefrontPageBuilderCategory[]
    columns: number
    showName: boolean
    showProductCount: boolean
}

const columnClasses: Record<number, string> = {
    2: 'sm:grid-cols-2',
    3: 'sm:grid-cols-2 lg:grid-cols-3',
    4: 'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
    5: 'sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5',
    6: 'sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6',
}

export default function CategoryGrid({
    categories,
    columns,
    showName,
    showProductCount,
}: CategoryGridProps) {
    const columnsClass = columnClasses[columns] ?? columnClasses[4]

    return (
        <ul
            className={['grid min-w-0 grid-cols-1 gap-4 sm:gap-5', columnsClass].join(' ')}
            aria-label="Product categories"
        >
            {categories.map((category) => (
                <li key={category.id} className="min-w-0">
                    <CategoryCard
                        category={category}
                        showName={showName}
                        showProductCount={showProductCount}
                    />
                </li>
            ))}
        </ul>
    )
}
