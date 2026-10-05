import CallToActionEditor from './CallToActionEditor'
import ContentEditor from './ContentEditor'
import FeaturedProductsEditor from './FeaturedProductsEditor'
import HeroEditor from './HeroEditor'
import ProductCategoriesEditor from './ProductCategoriesEditor'
import ProductCollectionEditor from './ProductCollectionEditor'
import PromotionalBannerEditor from './PromotionalBannerEditor'
import UnsupportedSectionEditor from './UnsupportedSectionEditor'

import type { SectionEditorProps } from '../types'

export default function SectionEditorRenderer(props: SectionEditorProps) {
    switch (props.section.type) {
        case 'call_to_action':
            return <CallToActionEditor {...props} />

        case 'content':
            return <ContentEditor {...props} />

        case 'featured_products':
            return <FeaturedProductsEditor {...props} />

        case 'hero':
            return <HeroEditor {...props} />

        case 'product_categories':
            return <ProductCategoriesEditor {...props} />

        case 'product_collection':
            return <ProductCollectionEditor {...props} />

        case 'promotional_banner':
            return <PromotionalBannerEditor {...props} />

        default:
            return <UnsupportedSectionEditor {...props} />
    }
}
