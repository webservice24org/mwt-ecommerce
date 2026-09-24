import type { PageSection, SectionConfig, SectionDefinition } from '@/types/page-builder'

import FeaturedProductsEditor from './FeaturedProductsEditor'
import UnsupportedSectionEditor from './UnsupportedSectionEditor'

interface Props {
    type: string
    section: PageSection
    definition: SectionDefinition
    value: SectionConfig
    onChange: (config: SectionConfig) => void
}

export default function SectionEditorRenderer({
    type,
    section,
    definition,
    value,
    onChange,
}: Props) {
    const editorProps = {
        section,
        definition,
        value,
        onChange,
    }

    switch (type) {
        case 'featured_products':
            return <FeaturedProductsEditor {...editorProps} />

        default:
            return <UnsupportedSectionEditor {...editorProps} />
    }
}
