import FeaturedProductsSection from '@/Components/Frontend/PageBuilder/Sections/FeaturedProductsSection'
import type { StorefrontResolvedSection } from '@/types/storefront-page'

interface Props {
    section: StorefrontResolvedSection
}

export default function StorefrontSectionRenderer({ section }: Props) {
    switch (section.type) {
        case 'featured_products':
            return renderFeaturedProducts(section)

        default:
            return null
    }
}

function renderFeaturedProducts(section: StorefrontResolvedSection) {
    switch (section.template) {
        case 'grid':
            return <FeaturedProductsSection section={section} />

        default:
            return null
    }
}
