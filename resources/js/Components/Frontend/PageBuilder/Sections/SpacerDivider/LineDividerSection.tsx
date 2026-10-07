import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'

import {
    readSpacerDividerConfig,
    type SpacerDividerAlignment,
    type SpacerDividerLineStyle,
    type SpacerDividerWidth,
} from './spacer-divider-storefront'

interface Props {
    section: StorefrontSectionProps['section']
}

export default function LineDividerSection({ section }: Props) {
    const config = readSpacerDividerConfig(section.config)

    return (
        <div aria-hidden="true" className="w-full min-w-0 overflow-x-clip">
            <div className="mx-auto w-full min-w-0 max-w-7xl px-3 sm:px-6 lg:px-8">
                <ResponsiveLine
                    className="sm:hidden"
                    height={config.mobile_height}
                    thickness={config.line_thickness}
                    color={config.line_color}
                    lineStyle={config.line_style}
                    width={config.width}
                    alignment={config.alignment}
                />

                <ResponsiveLine
                    className="hidden sm:flex lg:hidden"
                    height={config.tablet_height}
                    thickness={config.line_thickness}
                    color={config.line_color}
                    lineStyle={config.line_style}
                    width={config.width}
                    alignment={config.alignment}
                />

                <ResponsiveLine
                    className="hidden lg:flex"
                    height={config.desktop_height}
                    thickness={config.line_thickness}
                    color={config.line_color}
                    lineStyle={config.line_style}
                    width={config.width}
                    alignment={config.alignment}
                />
            </div>
        </div>
    )
}

interface ResponsiveLineProps {
    className: string

    height: number
    thickness: number

    color: string

    lineStyle: SpacerDividerLineStyle

    width: SpacerDividerWidth

    alignment: SpacerDividerAlignment
}

function ResponsiveLine({
    className,
    height,
    thickness,
    color,
    lineStyle,
    width,
    alignment,
}: ResponsiveLineProps) {
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
                    'min-w-0',
                    dividerWidthClass(width),
                    dividerAlignmentClass(alignment),
                ].join(' ')}
                style={{
                    borderTopColor: color,
                    borderTopStyle: lineStyle,
                    borderTopWidth: thickness,
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
