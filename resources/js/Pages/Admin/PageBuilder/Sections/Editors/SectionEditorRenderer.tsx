import type {
    CatalogCategoryOption,
    CatalogProductOption,
    CatalogSourceDefinition,
    PageSection,
    SectionConfig,
    SectionDefinition,
} from '@/types/page-builder'

import type { SectionEditorProps } from '../types'

import FeaturedProductsEditor from './FeaturedProductsEditor'
import UnsupportedSectionEditor from './UnsupportedSectionEditor'

interface Props extends SectionEditorProps {
    type: string
}

interface Props {
    type: string
    pageId: number
    section: PageSection
    definition: SectionDefinition
    value: SectionConfig
    catalogSources: CatalogSourceDefinition[]
    categoryOptions: CatalogCategoryOption[]
    selectedProductOptions: CatalogProductOption[]
    onChange: (config: SectionConfig) => void
}

export default function SectionEditorRenderer({ type, ...editorProps }: Props) {
    switch (type) {
        case 'featured_products':
            return <FeaturedProductsEditor {...editorProps} />

        default:
            return <UnsupportedSectionEditor {...editorProps} />
    }
}
