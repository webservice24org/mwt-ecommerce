import ProductCard from '@/Components/Frontend/Catalog/ProductCard'
import type { StorefrontProductCard } from '@/types/storefront'
import { Link } from '@inertiajs/react'
import { ArrowRight } from 'lucide-react'

interface HomeProductSectionProps {
    id: string
    title: string
    products: StorefrontProductCard[]
    viewAllHref?: string
}

export default function HomeProductSection({
    id,
    title,
    products,
    viewAllHref = '/products',
}: HomeProductSectionProps) {
    if (products.length === 0) {
        return null
    }

    return (
        <section className="min-w-0" aria-labelledby={id}>
            <div className="mb-6 flex items-end justify-between gap-4">
                <div className="min-w-0">
                    <h2
                        id={id}
                        className="text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl"
                    >
                        {title}
                    </h2>
                </div>

                <Link
                    href={viewAllHref}
                    className="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-neutral-700 transition hover:text-neutral-950"
                >
                    View all
                    <ArrowRight className="h-4 w-4" aria-hidden="true" />
                </Link>
            </div>

            <div className="grid min-w-0 grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                {products.map((product) => (
                    <ProductCard key={product.id} product={product} />
                ))}
            </div>
        </section>
    )
}
