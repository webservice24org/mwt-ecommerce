import StorefrontSectionLayout from '@/Components/Frontend/PageBuilder/StorefrontSectionLayout'
import { renderStorefrontSection } from '@/Components/Frontend/PageBuilder/storefrontSectionRegistry'
import type { StorefrontResolvedSection } from '@/types/storefront-page'

interface Props {
    section: StorefrontResolvedSection
}

export default function StorefrontSectionRenderer({ section }: Props) {
    const content = renderStorefrontSection({
        section,
    })

    if (content === null) {
        return null
    }

    return <StorefrontSectionLayout layout={section.layout}>{content}</StorefrontSectionLayout>
}
