import type { ComponentType } from 'react'

import FeaturedProductsEditor from './FeaturedProductsEditor'
import UnsupportedSectionEditor from './UnsupportedSectionEditor'
import type { SectionEditorProps } from './types'

type SectionEditorComponent = ComponentType<SectionEditorProps>

const sectionEditorRegistry: Record<string, SectionEditorComponent> = {
    featured_products: FeaturedProductsEditor,
}

export function getSectionEditor(type: string): SectionEditorComponent {
    return sectionEditorRegistry[type] ?? UnsupportedSectionEditor
}
