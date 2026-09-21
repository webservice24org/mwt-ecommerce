import HomeCategoryShowcase from '@/Components/Frontend/Home/HomeCategoryShowcase'
import HomeHero from '@/Components/Frontend/Home/HomeHero'
import HomeProductSection from '@/Components/Frontend/Home/HomeProductSection'
import StorefrontSeo from '@/Components/Frontend/Seo/StorefrontSeo'
import FrontendLayout from '@/Layouts/Frontend/FrontendLayout'
import type { StorefrontHome } from '@/types/storefront'

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
                <div className="mx-auto w-full max-w-7xl min-w-0 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
                    <div className="min-w-0 space-y-12 sm:space-y-16">
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
