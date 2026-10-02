import type { StorefrontPageBuilderCategory } from '@/types/storefront-page'
import { Link } from '@inertiajs/react'
import { ArrowRight } from 'lucide-react'

interface CategoryVisualCardProps {
    category: StorefrontPageBuilderCategory
    showName: boolean
    showProductCount: boolean
}

export default function CategoryVisualCard({
    category,
    showName,
    showProductCount,
}: CategoryVisualCardProps) {
    const hasMeta = showName || (showProductCount && category.productCount !== null)

    return (
        <Link
            href={`/category/${category.slug}`}
            className="group relative block min-w-0 overflow-hidden rounded-2xl bg-neutral-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2"
        >
            <div className="aspect-[4/3] min-w-0 overflow-hidden bg-neutral-200">
                {category.imageUrl ? (
                    <img
                        src={category.imageUrl}
                        alt=""
                        loading="lazy"
                        decoding="async"
                        className="h-full w-full object-cover transition duration-500 ease-out group-hover:scale-105"
                    />
                ) : (
                    <div
                        className="flex h-full w-full items-center justify-center bg-neutral-200 px-6 text-center text-sm text-neutral-500"
                        aria-hidden="true"
                    >
                        No image
                    </div>
                )}
            </div>

            <div
                className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"
                aria-hidden="true"
            />

            <div className="absolute inset-x-0 bottom-0 min-w-0 p-5 sm:p-6">
                {showName && (
                    <h3 className="min-w-0 break-words text-xl font-semibold leading-tight text-white sm:text-2xl">
                        {category.name}
                    </h3>
                )}

                {showProductCount && category.productCount !== null && (
                    <p className={['text-sm text-white/75', showName ? 'mt-1.5' : ''].join(' ')}>
                        {formatProductCount(category.productCount)}
                    </p>
                )}

                <span
                    className={[
                        'inline-flex items-center gap-1.5 text-sm font-medium text-white',
                        hasMeta ? 'mt-4' : '',
                    ].join(' ')}
                >
                    Explore
                    <ArrowRight
                        className="size-4 transition-transform duration-200 group-hover:translate-x-1"
                        aria-hidden="true"
                    />
                </span>
            </div>
        </Link>
    )
}

function formatProductCount(count: number): string {
    return count === 1 ? '1 product' : `${count} products`
}
