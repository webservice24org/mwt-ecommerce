import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'

import { readSpacerDividerConfig } from './spacer-divider-storefront'

interface Props {
    section: StorefrontSectionProps['section']
}

export default function ResponsiveSpacerSection({ section }: Props) {
    const config = readSpacerDividerConfig(section.config)

    return (
        <div aria-hidden="true" className="w-full min-w-0 shrink-0 overflow-hidden">
            <div
                className="block w-full min-w-0 sm:hidden"
                style={{
                    height: config.mobile_height,
                }}
            />

            <div
                className="hidden w-full min-w-0 sm:block lg:hidden"
                style={{
                    height: config.tablet_height,
                }}
            />

            <div
                className="hidden w-full min-w-0 lg:block"
                style={{
                    height: config.desktop_height,
                }}
            />
        </div>
    )
}
