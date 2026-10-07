import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'

import {
    readSpacerDividerConfig,
    type SpacerDividerAlignment,
    type SpacerDividerWidth,
} from './spacer-divider-storefront'

interface Props {
    section: StorefrontSectionProps['section']
}

export default function GradientDividerSection({ section }: Props) {
    const config = readSpacerDividerConfig(section.config)

    return (
        <div aria-hidden="true" className="w-full min-w-0 overflow-x-clip">
            <div className="mx-auto w-full min-w-0 max-w-7xl px-3 sm:px-6 lg:px-8">
                <ResponsiveGradientDivider
                    className="sm:hidden"
                    height={config.mobile_height}
                    thickness={config.line_thickness}
                    width={config.width}
                    alignment={config.alignment}
                    gradientFrom={config.gradient_from}
                    gradientVia={config.gradient_via}
                    gradientTo={config.gradient_to}
                />

                <ResponsiveGradientDivider
                    className="hidden sm:flex lg:hidden"
                    height={config.tablet_height}
                    thickness={config.line_thickness}
                    width={config.width}
                    alignment={config.alignment}
                    gradientFrom={config.gradient_from}
                    gradientVia={config.gradient_via}
                    gradientTo={config.gradient_to}
                />

                <ResponsiveGradientDivider
                    className="hidden lg:flex"
                    height={config.desktop_height}
                    thickness={config.line_thickness}
                    width={config.width}
                    alignment={config.alignment}
                    gradientFrom={config.gradient_from}
                    gradientVia={config.gradient_via}
                    gradientTo={config.gradient_to}
                />
            </div>
        </div>
    )
}

interface ResponsiveGradientDividerProps {
    className: string

    height: number
    thickness: number

    width: SpacerDividerWidth
    alignment: SpacerDividerAlignment

    gradientFrom: string
    gradientVia: string
    gradientTo: string
}

function ResponsiveGradientDivider({
    className,
    height,
    thickness,
    width,
    alignment,
    gradientFrom,
    gradientVia,
    gradientTo,
}: ResponsiveGradientDividerProps) {
    const effectiveHeight = Math.max(height, thickness)

    return (
        <div
            className={['w-full min-w-0 items-center', className].join(' ')}
            style={{
                height: effectiveHeight,
            }}
        >
            <div
                className={[
                    'min-w-0 rounded-full',
                    dividerWidthClass(width),
                    dividerAlignmentClass(alignment),
                ].join(' ')}
                style={{
                    height: thickness,

                    background: `linear-gradient(
                        to right,
                        ${gradientFrom},
                        ${gradientVia},
                        ${gradientTo}
                    )`,
                }}
            />
        </div>
    )
}

function dividerWidthClass(width: SpacerDividerWidth): string {
    switch (width) {
        case 'half':
            return 'w-1/2'

        case 'three_quarter':
            return 'w-3/4'

        case 'full':
        default:
            return 'w-full'
    }
}

function dividerAlignmentClass(alignment: SpacerDividerAlignment): string {
    switch (alignment) {
        case 'left':
            return 'mr-auto'

        case 'right':
            return 'ml-auto'

        case 'center':
        default:
            return 'mx-auto'
    }
}
