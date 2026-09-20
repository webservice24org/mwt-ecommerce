import HomeCategoryShowcase from '@/Components/Frontend/Home/HomeCategoryShowcase'
import HomeHero from '@/Components/Frontend/Home/HomeHero'
import HomeProductSection from '@/Components/Frontend/Home/HomeProductSection'
import FrontendLayout from '@/Layouts/Frontend/FrontendLayout'
import type { StorefrontHome } from '@/types/storefront'
import StorefrontSeo from '@/Components/Frontend/Seo/StorefrontSeo'

interface Props {
    home: StorefrontHome
}

export default function Home({ home }: Props) {
    return (
        <FrontendLayout>
            <StorefrontSeo
                title="Home"
                description="Discover featured products, new arrivals, and popular categories."
                canonicalPath="/"
            />
            <main className="min-w-0">
                <div className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <div className="space-y-14 sm:space-y-16">
                        <HomeHero />

                        <HomeCategoryShowcase categories={home.categories} />

                        <HomeProductSection
                            id="home-featured-products"
                            title="Featured products"
                            products={home.featured_products}
                        />

                        <HomeProductSection
                            id="home-new-arrivals"
                            title="New arrivals"
                            products={home.new_arrivals}
                            viewAllHref="/products?sort=newest"
                        />
                    </div>
                </div>
            </main>
        </FrontendLayout>
    )
}
