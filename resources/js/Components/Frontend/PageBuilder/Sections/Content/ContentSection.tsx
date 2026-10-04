import type { StorefrontResolvedSection } from '@/types/storefront-page'

import CenteredContent from './CenteredContent'
import { readContentSectionConfig } from './content-config'
import ImageTextContent from './ImageTextContent'
import TextContent from './TextContent'
import TextImageContent from './TextImageContent'

interface Props {
    section: StorefrontResolvedSection
}

export default function ContentSection({ section }: Props) {
    const config = readContentSectionConfig(
        section.config,
        section.template === 'centered_content' ? 'center' : 'left',
    )

    switch (section.template) {
        case 'text':
            return <TextContent config={config} />

        case 'image_text':
            return <ImageTextContent config={config} />

        case 'text_image':
            return <TextImageContent config={config} />

        case 'centered_content':
            return <CenteredContent config={config} />

        default:
            return null
    }
}
