import type { ComponentType } from 'react'

import type { PageSection, SectionConfig, SectionDefinition } from '@/types/page-builder'

export interface SectionEditorProps {
    section: PageSection
    definition: SectionDefinition
    value: SectionConfig
    onChange: (config: SectionConfig) => void
}

export type SectionEditorComponent = ComponentType<SectionEditorProps>
