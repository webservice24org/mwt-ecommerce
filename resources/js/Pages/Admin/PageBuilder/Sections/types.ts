import type { ComponentType } from 'react'

import type {
    CatalogCategoryOption,
    CatalogProductOption,
    CatalogSourceDefinition,
    PageSection,
    SectionConfig,
    SectionDefinition,
} from '@/types/page-builder'

export interface SectionEditorProps {
    pageId: number
    section: PageSection
    definition: SectionDefinition
    value: SectionConfig
    catalogSources: CatalogSourceDefinition[]
    categoryOptions: CatalogCategoryOption[]
    selectedProductOptions: CatalogProductOption[]
    onChange: (config: SectionConfig) => void
}

export type SectionEditorComponent = ComponentType<SectionEditorProps>
