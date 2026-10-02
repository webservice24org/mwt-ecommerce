import ProductCard from '@/Components/Frontend/Catalog/ProductCard'
import type { StorefrontProductCard } from '@/types/storefront'

interface ProductGridProps {
    products: StorefrontProductCard[]
}

export default function ProductGrid({ products }: ProductGridProps) {
    return (
        <ul
            className="grid min-w-0 grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            aria-label="Products"
        >
            {products.map((product) => (
                <li key={product.id} className="min-w-0">
                    <ProductCard product={product} />
                </li>
            ))}
        </ul>
    )
}
