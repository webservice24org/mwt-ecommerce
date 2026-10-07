import ContentSection from '@/Components/Frontend/PageBuilder/Sections/Content/ContentSection'
import FeaturedProductsSection from '@/Components/Frontend/PageBuilder/Sections/FeaturedProductsSection'
import HeroSection from '@/Components/Frontend/PageBuilder/Sections/Hero/HeroSection'
import ProductCategoriesCardsSection from '@/Components/Frontend/PageBuilder/Sections/ProductCategories/ProductCategoriesCardsSection'
import ProductCategoriesCarouselSection from '@/Components/Frontend/PageBuilder/Sections/ProductCategories/ProductCategoriesCarouselSection'
import ProductCategoriesGridSection from '@/Components/Frontend/PageBuilder/Sections/ProductCategories/ProductCategoriesGridSection'
import ProductCollectionCardsSection from '@/Components/Frontend/PageBuilder/Sections/ProductCollection/ProductCollectionCardsSection'
import ProductCollectionCarouselSection from '@/Components/Frontend/PageBuilder/Sections/ProductCollection/ProductCollectionCarouselSection'
import ProductCollectionGridSection from '@/Components/Frontend/PageBuilder/Sections/ProductCollection/ProductCollectionGridSection'
import CallToActionSection from '@/Components/Frontend/PageBuilder/Sections/CallToAction/CallToActionSection'
import PromotionalBannerSection from '@/Components/Frontend/PageBuilder/Sections/PromotionalBanner/PromotionalBannerSection'
import FeaturesBenefitsSection from '@/Components/Frontend/PageBuilder/Sections/FeaturesBenefits/FeaturesBenefitsSection'
import BrandLogoStripSection from '@/Components/Frontend/PageBuilder/Sections/Brands/BrandLogoStripSection'
import BrandCardsSection from '@/Components/Frontend/PageBuilder/Sections/Brands/BrandCardsSection'
import BrandLogoMarqueeSection from '@/Components/Frontend/PageBuilder/Sections/Brands/BrandLogoMarqueeSection'
import BrandSpotlightSection from '@/Components/Frontend/PageBuilder/Sections/Brands/BrandSpotlightSection'
import TestimonialGridSliderSection from '@/Components/Frontend/PageBuilder/Sections/Testimonials/TestimonialGridSliderSection'
import TestimonialSpotlightSliderSection from '@/Components/Frontend/PageBuilder/Sections/Testimonials/TestimonialSpotlightSliderSection'
import TestimonialCardSliderSection from '@/Components/Frontend/PageBuilder/Sections/Testimonials/TestimonialCardSliderSection'
import FaqAccordionSection from '@/Components/Frontend/PageBuilder/Sections/Faq/FaqAccordionSection'
import FaqTwoColumnSection from '@/Components/Frontend/PageBuilder/Sections/Faq/FaqTwoColumnSection'
import FaqSidePanelSection from '@/Components/Frontend/PageBuilder/Sections/Faq/FaqSidePanelSection'

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

function renderCallToAction({ section }: StorefrontSectionProps) {
    return <CallToActionSection section={section} />
}

function renderFeaturesBenefits({ section }: StorefrontSectionProps) {
    return <FeaturesBenefitsSection section={section} />
}

function renderBrandLogoStrip({ section }: StorefrontSectionProps) {
    return <BrandLogoStripSection section={section} />
}

function renderBrandCards({ section }: StorefrontSectionProps) {
    return <BrandCardsSection section={section} />
}

function renderBrandLogoMarquee({ section }: StorefrontSectionProps) {
    return <BrandLogoMarqueeSection section={section} />
}
function renderBrandSpotlight({ section }: StorefrontSectionProps) {
    return <BrandSpotlightSection section={section} />
}

function renderTestimonialGridSlider({ section }: StorefrontSectionProps) {
    return <TestimonialGridSliderSection section={section} />
}

function renderTestimonialSpotlightSlider({ section }: StorefrontSectionProps) {
    return <TestimonialSpotlightSliderSection section={section} />
}

function renderTestimonialCardSlider({ section }: StorefrontSectionProps) {
    return <TestimonialCardSliderSection section={section} />
}

function renderFaqAccordion({ section }: StorefrontSectionProps) {
    return <FaqAccordionSection section={section} />
}
function renderFaqTwoColumn({ section }: StorefrontSectionProps) {
    return <FaqTwoColumnSection section={section} />
}
function renderFaqSidePanel({ section }: StorefrontSectionProps) {
    return <FaqSidePanelSection section={section} />
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

    call_to_action: {
        templates: {
            high_impact: renderCallToAction,

            split_lead_capture: renderCallToAction,

            contact_grid: renderCallToAction,
        },
    },

    features_benefits: {
        templates: {
            icon_grid: renderFeaturesBenefits,

            image_grid: renderFeaturesBenefits,

            horizontal_benefits: renderFeaturesBenefits,
        },
    },

    brands: {
        templates: {
            logo_strip: renderBrandLogoStrip,

            brand_cards: renderBrandCards,

            logo_marquee: renderBrandLogoMarquee,

            spotlight_banner: renderBrandSpotlight,
        },
    },

    testimonials: {
        templates: {
            grid_slider: renderTestimonialGridSlider,
            spotlight_slider: renderTestimonialSpotlightSlider,
            card_slider: renderTestimonialCardSlider,
        },
    },

    faq: {
        templates: {
            accordion: renderFaqAccordion,
            two_column: renderFaqTwoColumn,
            side_panel: renderFaqSidePanel,
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
