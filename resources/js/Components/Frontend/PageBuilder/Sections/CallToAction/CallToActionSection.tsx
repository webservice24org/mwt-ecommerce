import type { StorefrontResolvedSection } from '@/types/storefront-page'

import { readCallToActionConfig } from './call-to-action-config'
import ContactGrid from './ContactGrid'
import HighImpact from './HighImpact'
import SplitLeadCapture from './SplitLeadCapture'

interface Props {
    section: StorefrontResolvedSection
}

export default function CallToActionSection({ section }: Props) {
    const config = readCallToActionConfig(section.config)

    switch (section.template) {
        case 'high_impact':
            return <HighImpact config={config} />

        case 'split_lead_capture':
            return <SplitLeadCapture config={config} />

        case 'contact_grid':
            return <ContactGrid config={config} />

        default:
            return null
    }
}
