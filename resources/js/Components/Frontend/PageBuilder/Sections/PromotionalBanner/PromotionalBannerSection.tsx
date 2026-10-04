import type { StorefrontResolvedSection } from '@/types/storefront-page'

import ContentBanner from './ContentBanner'
import ImageBanner from './ImageBanner'
import SplitBanner from './SplitBanner'
import { readPromotionalBannerConfig } from './promotional-banner-config'

interface Props {
    section: StorefrontResolvedSection
}

export default function PromotionalBannerSection({ section }: Props) {
    const config = readPromotionalBannerConfig(section.config)

    switch (section.template) {
        case 'image_banner':
            return <ImageBanner config={config} />

        case 'content_banner':
            return <ContentBanner config={config} />

        case 'split_banner':
            return <SplitBanner config={config} />

        default:
            return null
    }
}
