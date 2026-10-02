import type { StorefrontPageBuilderCategory } from '@/types/storefront-page'
import { Link } from '@inertiajs/react'
import { ArrowRight } from 'lucide-react'

interface CategoryCardProps {
    category: StorefrontPageBuilderCategory
    showName: boolean
    showProductCount: boolean
}

export default function CategoryCard({ category, showName, showProductCount }: CategoryCardProps) {
    return (
        <Link
            href={`/category/${category.slug}`}
            className="group flex h-full min-w-0 flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-white transition hover:border-neutral-300 hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2"
        >
            <div className="aspect-[4/3] w-full overflow-hidden bg-neutral-100">
                {category.imageUrl ? (
                    <img
                        src={category.imageUrl}
                        alt=""
                        loading="lazy"
                        decoding="async"
                        className="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                    />
                ) : (
                    <div
                        className="flex h-full w-full items-center justify-center bg-neutral-100 px-4 text-center text-sm text-neutral-400"
                        aria-hidden="true"
                    >
                        No image
                    </div>
                )}
            </div>

            <div className="flex min-w-0 flex-1 flex-col p-4 sm:p-5">
                {showName && (
                    <h3 className="min-w-0 break-words text-base font-semibold leading-6 text-neutral-950">
                        {category.name}
                    </h3>
                )}

                {showProductCount && category.productCount !== null && (
                    <p
                        className={
                            showName ? 'mt-1 text-sm text-neutral-500' : 'text-sm text-neutral-500'
                        }
                    >
                        {formatProductCount(category.productCount)}
                    </p>
                )}

                <span
                    className={[
                        'inline-flex min-w-0 items-center gap-1 text-xs font-medium text-neutral-500 transition group-hover:text-neutral-900',
                        showName || (showProductCount && category.productCount !== null)
                            ? 'mt-5'
                            : '',
                    ].join(' ')}
                >
                    <span>Explore</span>

                    <ArrowRight className="size-3.5 shrink-0" aria-hidden="true" />
                </span>
            </div>
        </Link>
    )
}

function formatProductCount(count: number): string {
    return count === 1 ? '1 product' : `${count} products`
}
