import ProductImage from '@/Components/Frontend/Catalog/ProductImage'
import type { StorefrontImage } from '@/types/storefront'

interface ProductPrimaryMediaProps {
    productName: string
    images: StorefrontImage[]
}

export default function ProductPrimaryMedia({ productName, images }: ProductPrimaryMediaProps) {
    const primaryImage = images[0] ?? null

    return (
        <div className="overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-50">
            <ProductImage
                image={primaryImage}
                productName={productName}
                eager
                sizes="(min-width: 1024px) 50vw, 100vw"
                className="aspect-square w-full"
            />
        </div>
    )
}
