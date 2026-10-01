import type { StorefrontResolvedSection } from '@/types/storefront-page'

import ContentSliderHero from './ContentSliderHero'
import ImageSliderHero from './ImageSliderHero'
import StaticHero from './StaticHero'
import { readHeroConfig } from './hero-config'

interface Props {
    section: StorefrontResolvedSection
}

export default function HeroSection({ section }: Props) {
    const config = readHeroConfig(section.config)

    switch (section.template) {
        case 'content_slider':
            return <ContentSliderHero config={config} />

        case 'image_slider':
            return <ImageSliderHero config={config} />

        case 'static':
            return <StaticHero config={config} />

        default:
            return null
    }
}
