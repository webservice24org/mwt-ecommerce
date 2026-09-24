import type { PageSection } from '@/types/page-builder'

import FeaturedProductsPreview from './FeaturedProductsPreview'
import GenericSectionPreview from './GenericSectionPreview'

interface Props {
    section: PageSection
    sectionLabel: string
    templateLabel: string
}

export default function SectionPreviewRenderer({ section, sectionLabel, templateLabel }: Props) {
    switch (section.type) {
        case 'featured_products':
            return (
                <FeaturedProductsPreview
                    section={section}
                    sectionLabel={sectionLabel}
                    templateLabel={templateLabel}
                />
            )

        default:
            return (
                <GenericSectionPreview
                    section={section}
                    sectionLabel={sectionLabel}
                    templateLabel={templateLabel}
                />
            )
    }
}
