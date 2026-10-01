import HomeCategoryShowcase from '@/Components/Frontend/Home/HomeCategoryShowcase'
import HomeHero from '@/Components/Frontend/Home/HomeHero'
import HomeProductSection from '@/Components/Frontend/Home/HomeProductSection'
import StorefrontSectionRenderer from '@/Components/Frontend/PageBuilder/StorefrontSectionRenderer'
import StorefrontSeo from '@/Components/Frontend/Seo/StorefrontSeo'
import FrontendLayout from '@/Layouts/Frontend/FrontendLayout'
import type { StorefrontHome } from '@/types/storefront'

import type {
    StorefrontBuilderHomepage,
    StorefrontResolvedSection,
} from '@/types/storefront-page'

interface Props {
    home: StorefrontHome
    builderPage: StorefrontBuilderHomepage | null
}

export default function Home({ home, builderPage }: Props) {
    const hasBuilderHomepage = builderPage !== null

    const title = builderPage?.meta_title ?? builderPage?.title ?? 'Home'

    const description =
        builderPage?.meta_description ??
        'Discover featured products, new arrivals, and popular categories.'

    return (
        <FrontendLayout>
            <StorefrontSeo title={title} description={description} canonicalPath="/" />

            {hasBuilderHomepage ? (
                <BuilderHomepage sections={builderPage.sections} />
            ) : (
                <LegacyHomepage home={home} />
            )}
        </FrontendLayout>
    )
}

function BuilderHomepage({ sections }: { sections: StorefrontResolvedSection[] }) {
    return (
        <main className="min-w-0">
            {sections.map((section) => (
                <StorefrontSectionRenderer key={section.id} section={section} />
            ))}
        </main>
    )
}

function LegacyHomepage({ home }: { home: StorefrontHome }) {
    return (
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
    )
}
