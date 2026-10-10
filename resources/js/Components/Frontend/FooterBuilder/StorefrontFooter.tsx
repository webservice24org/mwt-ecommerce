import LuxeNewsletterFooter from '@/Components/Frontend/FooterBuilder/LuxeNewsletterFooter'
import MarketplaceTrustFooter from '@/Components/Frontend/FooterBuilder/MarketplaceTrustFooter'
import MinimalLocalizationFooter from '@/Components/Frontend/FooterBuilder/MinimalLocalizationFooter'
import type { StorefrontFooterData } from '@/types/footer-builder'

interface Props {
    footer: StorefrontFooterData | null | undefined
}

export default function StorefrontFooter({ footer }: Props) {
    if (!footer) {
        return null
    }

    switch (footer.template) {
        case 'luxe_newsletter':
            return <LuxeNewsletterFooter config={footer.config} />

        case 'minimal_localized':
            return <MinimalLocalizationFooter config={footer.config} />

        case 'marketplace_trust':
            return <MarketplaceTrustFooter config={footer.config} />
    }
}
