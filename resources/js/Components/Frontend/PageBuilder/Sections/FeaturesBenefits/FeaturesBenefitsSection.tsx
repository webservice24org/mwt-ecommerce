import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'

import { readFeaturesBenefitsConfig } from './features-benefits-config'
import IconGrid from './IconGrid'
import ImageGrid from './ImageGrid'
import HorizontalBenefits from './HorizontalBenefits'

interface Props {
    section: StorefrontSectionProps['section']
}

export default function FeaturesBenefitsSection({ section }: Props) {
    const config = readFeaturesBenefitsConfig(section.config)

    switch (section.template) {
        case 'icon_grid':
            return <IconGrid config={config} />

        case 'image_grid':
            return <ImageGrid config={config} />

        case 'horizontal_benefits':
            return <HorizontalBenefits config={config} />

        default:
            return null
    }
}
