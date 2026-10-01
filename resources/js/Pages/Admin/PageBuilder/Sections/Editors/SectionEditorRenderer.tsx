import FeaturedProductsEditor from './FeaturedProductsEditor'
import HeroEditor from './HeroEditor'
import UnsupportedSectionEditor from './UnsupportedSectionEditor'

import type { SectionEditorProps } from '../types'

export default function SectionEditorRenderer(props: SectionEditorProps) {
    switch (props.section.type) {
        case 'featured_products':
            return <FeaturedProductsEditor {...props} />

        case 'hero':
            return <HeroEditor {...props} />

        default:
            return <UnsupportedSectionEditor {...props} />
    }
}
