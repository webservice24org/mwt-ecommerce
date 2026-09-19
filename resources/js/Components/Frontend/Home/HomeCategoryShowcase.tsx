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
            <div className="mb-6">
                <h2
                    id="home-categories-title"
                    className="text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl"
                >
                    Shop by category
                </h2>

                <p className="mt-2 text-sm text-neutral-600">Explore products by category.</p>
            </div>

            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                {categories.map((category) => (
                    <Link
                        key={category.id}
                        href={`/category/${category.slug}`}
                        className="group flex min-w-0 flex-col justify-between rounded-2xl border border-neutral-200 bg-white p-5 transition hover:border-neutral-300 hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
                    >
                        <span className="break-words font-semibold text-neutral-900">
                            {category.name}
                        </span>

                        <span className="mt-6 inline-flex items-center gap-1 text-xs font-medium text-neutral-500 transition group-hover:text-neutral-900">
                            Explore
                            <ArrowRight className="h-3.5 w-3.5" aria-hidden="true" />
                        </span>
                    </Link>
                ))}
            </div>
        </section>
    )
}
