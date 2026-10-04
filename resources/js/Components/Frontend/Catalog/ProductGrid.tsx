import ProductCard from '@/Components/Frontend/Catalog/ProductCard'
import type { StorefrontProductCard } from '@/types/storefront'

interface ProductGridProps {
    products: StorefrontProductCard[]
    columns?: number
    showPrice?: boolean
    showBadges?: boolean
}

export default function ProductGrid({
    products,
    columns = 4,
    showPrice = true,
    showBadges = true,
}: ProductGridProps) {
    return (
        <ul
            className={[
                'grid min-w-0 grid-cols-1 gap-5 sm:grid-cols-2',
                getColumnClass(columns),
            ].join(' ')}
            aria-label="Products"
        >
            {products.map((product) => (
                <li key={product.id} className="min-w-0">
                    <ProductCard product={product} showPrice={showPrice} showBadges={showBadges} />
                </li>
            ))}
        </ul>
    )
}

function getColumnClass(columns: number): string {
    switch (columns) {
        case 2:
            return 'lg:grid-cols-2'

        case 3:
            return 'lg:grid-cols-3'

        case 5:
            return 'lg:grid-cols-3 xl:grid-cols-5'

        case 6:
            return 'lg:grid-cols-3 xl:grid-cols-6'

        case 4:
        default:
            return 'lg:grid-cols-3 xl:grid-cols-4'
    }
}
