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
            <div className="mb-6 flex min-w-0 flex-wrap items-end justify-between gap-3 sm:gap-4">
                <div className="min-w-0 flex-1">
                    <h2
                        id={id}
                        className="break-words text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl"
                    >
                        {title}
                    </h2>
                </div>

                <Link
                    href={viewAllHref}
                    className="inline-flex min-h-11 shrink-0 items-center gap-1 rounded-md px-2 text-sm font-semibold text-neutral-700 transition hover:text-neutral-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2"
                >
                    View all
                    <ArrowRight className="size-4" aria-hidden="true" />
                </Link>
            </div>

            <div className="grid min-w-0 grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                {products.map((product) => (
                    <ProductCard key={product.id} product={product} />
                ))}
            </div>
        </section>
    )
}
