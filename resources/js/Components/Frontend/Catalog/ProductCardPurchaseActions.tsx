import { Link } from '@inertiajs/react'

import ProductPurchaseActions, { addButtonClass, orderButtonClass } from '@/Components/Frontend/Catalog/ProductPurchaseActions'
import type { StorefrontProductCard } from '@/types/storefront'

export default function ProductCardPurchaseActions({ product }: { product: StorefrontProductCard }) {
    if (product.type === 'variable' && product.pricing.min_price !== null) {
        const optionsUrl = `/products/${product.slug}#product-purchase`
        return (
            <div className="mt-4 min-w-0">
                <div className="flex min-w-0 flex-wrap gap-2">
                    <Link href={optionsUrl} className={addButtonClass} aria-label={`Select options to add ${product.name} to cart`}>Add to Cart</Link>
                    <Link href={optionsUrl} className={orderButtonClass} aria-label={`Select options to order ${product.name}`}>Order Now</Link>
                </div>
                <p className="mwt-card-copy mt-1 text-xs text-neutral-500">Select product options first.</p>
            </div>
        )
    }

    return <ProductPurchaseActions productId={product.id} available={product.type === 'simple' && product.pricing.min_price !== null} />
}
