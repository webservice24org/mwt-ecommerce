import ContentSection from '@/Components/Frontend/PageBuilder/Sections/Content/ContentSection'
import FeaturedProductsSection from '@/Components/Frontend/PageBuilder/Sections/FeaturedProductsSection'
import HeroSection from '@/Components/Frontend/PageBuilder/Sections/Hero/HeroSection'
import ProductCategoriesCardsSection from '@/Components/Frontend/PageBuilder/Sections/ProductCategories/ProductCategoriesCardsSection'
import ProductCategoriesCarouselSection from '@/Components/Frontend/PageBuilder/Sections/ProductCategories/ProductCategoriesCarouselSection'
import ProductCategoriesGridSection from '@/Components/Frontend/PageBuilder/Sections/ProductCategories/ProductCategoriesGridSection'
import ProductCollectionCardsSection from '@/Components/Frontend/PageBuilder/Sections/ProductCollection/ProductCollectionCardsSection'
import ProductCollectionCarouselSection from '@/Components/Frontend/PageBuilder/Sections/ProductCollection/ProductCollectionCarouselSection'
import ProductCollectionGridSection from '@/Components/Frontend/PageBuilder/Sections/ProductCollection/ProductCollectionGridSection'

import PromotionalBannerSection from '@/Components/Frontend/PageBuilder/Sections/PromotionalBanner/PromotionalBannerSection'

import type {
    StorefrontSectionProps,
    StorefrontSectionRender,
} from '@/Components/Frontend/PageBuilder/types'

interface StorefrontSectionRegistration {
    templates: Record<string, StorefrontSectionRender>
}

function renderContent({ section }: StorefrontSectionProps) {
    return <ContentSection section={section} />
}

function renderFeaturedProducts({ section }: StorefrontSectionProps) {
    return <FeaturedProductsSection section={section} />
}

function renderPromotionalBanner({ section }: StorefrontSectionProps) {
    return <PromotionalBannerSection section={section} />
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

function renderProductCollectionGrid({ section }: StorefrontSectionProps) {
    return <ProductCollectionGridSection section={section} />
}

function renderProductCollectionCards({ section }: StorefrontSectionProps) {
    return <ProductCollectionCardsSection section={section} />
}

function renderProductCollectionCarousel({ section }: StorefrontSectionProps) {
    return <ProductCollectionCarouselSection section={section} />
}

const storefrontSectionRegistry: Record<string, StorefrontSectionRegistration> = {
    content: {
        templates: {
            text: renderContent,
            image_text: renderContent,
            text_image: renderContent,
            centered_content: renderContent,
        },
    },

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

    promotional_banner: {
        templates: {
            image_banner: renderPromotionalBanner,
            content_banner: renderPromotionalBanner,
            split_banner: renderPromotionalBanner,
        },
    },

    product_categories: {
        templates: {
            grid: renderProductCategoriesGrid,
            cards: renderProductCategoriesCards,
            carousel: renderProductCategoriesCarousel,
        },
    },

    product_collection: {
        templates: {
            grid: renderProductCollectionGrid,
            cards: renderProductCollectionCards,
            carousel: renderProductCollectionCarousel,
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
