import FeaturedProductsSection from '@/Components/Frontend/PageBuilder/Sections/FeaturedProductsSection'
import HeroSection from '@/Components/Frontend/PageBuilder/Sections/Hero/HeroSection'
import ProductCategoriesGridSection from '@/Components/Frontend/PageBuilder/Sections/ProductCategories/ProductCategoriesGridSection'
import ProductCategoriesCardsSection from '@/Components/Frontend/PageBuilder/Sections/ProductCategories/ProductCategoriesCardsSection'
import ProductCategoriesCarouselSection from '@/Components/Frontend/PageBuilder/Sections/ProductCategories/ProductCategoriesCarouselSection'

import type {
    StorefrontSectionProps,
    StorefrontSectionRender,
} from '@/Components/Frontend/PageBuilder/types'

interface StorefrontSectionRegistration {
    templates: Record<string, StorefrontSectionRender>
}

function renderFeaturedProducts({ section }: StorefrontSectionProps) {
    return <FeaturedProductsSection section={section} />
}

function renderHero({ section }: StorefrontSectionProps) {
    return <HeroSection section={section} />
}

function renderProductCategoriesGrid({ section }: StorefrontSectionProps) {
    return <ProductCategoriesGridSection section={section} />
}

function renderProductCategoriesCards({ section }: StorefrontSectionProps) {
    return <ProductCategoriesCardsSection section={section} />
}

function renderProductCategoriesCarousel({ section }: StorefrontSectionProps) {
    return <ProductCategoriesCarouselSection section={section} />
}

const storefrontSectionRegistry: Record<string, StorefrontSectionRegistration> = {
    featured_products: {
        templates: {
            grid: renderFeaturedProducts,
        },
    },

    hero: {
        templates: {
            content_slider: renderHero,
            image_slider: renderHero,
            static: renderHero,
        },
    },

    product_categories: {
        templates: {
            grid: renderProductCategoriesGrid,
            cards: renderProductCategoriesCards,
            carousel: renderProductCategoriesCarousel,
        },
    },
}

export function renderStorefrontSection(props: StorefrontSectionProps) {
    const { section } = props

    const render = storefrontSectionRegistry[section.type]?.templates[section.template]

    if (!render) {
        return null
    }

    return render(props)
}

export function hasStorefrontSectionRenderer(type: string, template: string): boolean {
    return storefrontSectionRegistry[type]?.templates[template] !== undefined
}
