import { Link } from '@inertiajs/react'
import { ArrowRight, ShoppingBag } from 'lucide-react'

export default function HomeHero() {
    return (
        <section
            className="overflow-hidden rounded-3xl bg-neutral-950 px-6 py-14 text-white sm:px-10 sm:py-16 lg:px-16 lg:py-20"
            aria-labelledby="home-hero-title"
        >
            <div className="max-w-3xl">
                <p className="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-neutral-300">
                    Welcome to our store
                </p>

                <h1
                    id="home-hero-title"
                    className="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl"
                >
                    Discover products made for you
                </h1>

                <p className="mt-6 max-w-2xl text-base leading-7 text-neutral-300 sm:text-lg">
                    Explore our latest products, featured selections, and popular categories.
                </p>

                <div className="mt-8 flex flex-wrap gap-3">
                    <Link
                        href="/products"
                        className="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-neutral-950 transition hover:bg-neutral-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-950"
                    >
                        <ShoppingBag className="h-4 w-4" aria-hidden="true" />
                        Shop now
                    </Link>

                    <Link
                        href="/products?sort=newest"
                        className="inline-flex items-center gap-2 rounded-xl border border-neutral-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                    >
                        New arrivals
                        <ArrowRight className="h-4 w-4" aria-hidden="true" />
                    </Link>
                </div>
            </div>
        </section>
    )
}
