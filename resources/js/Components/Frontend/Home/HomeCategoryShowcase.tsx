import type { StorefrontCategory } from '@/types/storefront'
import { Link } from '@inertiajs/react'
import { ArrowRight } from 'lucide-react'

interface HomeCategoryShowcaseProps {
    categories: StorefrontCategory[]
}

export default function HomeCategoryShowcase({ categories }: HomeCategoryShowcaseProps) {
    if (categories.length === 0) {
        return null
    }

    return (
        <section className="min-w-0" aria-labelledby="home-categories-title">
            <div className="mb-6 min-w-0">
                <h2
                    id="home-categories-title"
                    className="break-words text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl"
                >
                    Shop by category
                </h2>

                <p className="mt-2 break-words text-sm leading-6 text-neutral-600">
                    Explore products by category.
                </p>
            </div>

            <div className="grid min-w-0 grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-6">
                {categories.map((category) => (
                    <Link
                        key={category.id}
                        href={`/category/${category.slug}`}
                        className="group flex min-h-32 min-w-0 flex-col justify-between rounded-2xl border border-neutral-200 bg-white p-4 transition hover:border-neutral-300 hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 sm:p-5"
                    >
                        <span className="min-w-0 break-words font-semibold leading-6 text-neutral-900">
                            {category.name}
                        </span>

                        <span className="mt-6 inline-flex min-w-0 items-center gap-1 text-xs font-medium text-neutral-500 transition group-hover:text-neutral-900">
                            <span className="min-w-0">Explore</span>

                            <ArrowRight className="size-3.5 shrink-0" aria-hidden="true" />
                        </span>
                    </Link>
                ))}
            </div>
        </section>
    )
}
