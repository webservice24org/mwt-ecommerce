import FeaturedProductsEditor from './FeaturedProductsEditor'
import HeroEditor from './HeroEditor'
import ProductCategoriesEditor from './ProductCategoriesEditor'
import UnsupportedSectionEditor from './UnsupportedSectionEditor'

import type { SectionEditorProps } from '../types'

export default function SectionEditorRenderer(props: SectionEditorProps) {
    switch (props.section.type) {
        case 'featured_products':
            return <FeaturedProductsEditor {...props} />

        case 'hero':
            return <HeroEditor {...props} />

        case 'product_categories':
            return <ProductCategoriesEditor {...props} />

        default:
            return <UnsupportedSectionEditor {...props} />
    }
}
