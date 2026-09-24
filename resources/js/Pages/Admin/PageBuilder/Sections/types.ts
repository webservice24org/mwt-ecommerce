import type { PageSection, SectionConfig, SectionDefinition } from '@/types/page-builder'

export interface SectionEditorProps {
    section: PageSection
    definition: SectionDefinition
    config: SectionConfig
    onChange: (config: SectionConfig) => void
}
