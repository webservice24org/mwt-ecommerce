import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'

import {
    readSpacerDividerConfig,
    type SpacerDividerAlignment,
    type SpacerDividerLabelStyle,
    type SpacerDividerLineStyle,
    type SpacerDividerWidth,
} from './spacer-divider-storefront'

interface Props {
    section: StorefrontSectionProps['section']
}

export default function LabelDividerSection({ section }: Props) {
    const config = readSpacerDividerConfig(section.config)

    const label = config.label

    const hasLabel = label !== null

    return (
        <div
            role={hasLabel ? 'separator' : undefined}
            aria-label={hasLabel ? label : undefined}
            aria-orientation={hasLabel ? 'horizontal' : undefined}
            aria-hidden={hasLabel ? undefined : true}
            className="w-full min-w-0 overflow-x-clip"
        >
            <div className="mx-auto w-full min-w-0 max-w-7xl px-3 sm:px-6 lg:px-8">
                <ResponsiveLabelDivider
                    className="sm:hidden"
                    height={config.mobile_height}
                    label={label}
                    labelStyle={config.label_style}
                    textColor={config.text_color}
                    lineStyle={config.line_style}
                    lineColor={config.line_color}
                    lineThickness={config.line_thickness}
                    width={config.width}
                    alignment={config.alignment}
                />

                <ResponsiveLabelDivider
                    className="hidden sm:flex lg:hidden"
                    height={config.tablet_height}
                    label={label}
                    labelStyle={config.label_style}
                    textColor={config.text_color}
                    lineStyle={config.line_style}
                    lineColor={config.line_color}
                    lineThickness={config.line_thickness}
                    width={config.width}
                    alignment={config.alignment}
                />

                <ResponsiveLabelDivider
                    className="hidden lg:flex"
                    height={config.desktop_height}
                    label={label}
                    labelStyle={config.label_style}
                    textColor={config.text_color}
                    lineStyle={config.line_style}
                    lineColor={config.line_color}
                    lineThickness={config.line_thickness}
                    width={config.width}
                    alignment={config.alignment}
                />
            </div>
        </div>
    )
}

interface ResponsiveLabelDividerProps {
    className: string

    height: number

    label: string | null

    labelStyle: SpacerDividerLabelStyle

    textColor: string

    lineStyle: SpacerDividerLineStyle

    lineColor: string

    lineThickness: number

    width: SpacerDividerWidth

    alignment: SpacerDividerAlignment
}

function ResponsiveLabelDivider({
    className,
    height,
    label,
    labelStyle,
    textColor,
    lineStyle,
    lineColor,
    lineThickness,
    width,
    alignment,
}: ResponsiveLabelDividerProps) {
    return (
        <div
            className={['w-full min-w-0 items-center', className].join(' ')}
            style={{
                minHeight: Math.max(height, lineThickness),
            }}
        >
            <div
                className={[
                    'flex min-w-0 items-center',
                    dividerWidthClass(width),
                    dividerAlignmentClass(alignment),
                ].join(' ')}
            >
                <DividerLine color={lineColor} thickness={lineThickness} lineStyle={lineStyle} />

                {label && (
                    <DividerLabel
                        label={label}
                        labelStyle={labelStyle}
                        textColor={textColor}
                        lineColor={lineColor}
                    />
                )}

                <DividerLine color={lineColor} thickness={lineThickness} lineStyle={lineStyle} />
            </div>
        </div>
    )
}

interface DividerLineProps {
    color: string

    thickness: number

    lineStyle: SpacerDividerLineStyle
}

function DividerLine({ color, thickness, lineStyle }: DividerLineProps) {
    return (
        <span
            aria-hidden="true"
            className="min-w-2 flex-1 sm:min-w-3"
            style={{
                borderTopColor: color,
                borderTopStyle: lineStyle,
                borderTopWidth: thickness,
            }}
        />
    )
}

interface DividerLabelProps {
    label: string

    labelStyle: SpacerDividerLabelStyle

    textColor: string

    lineColor: string
}

function DividerLabel({ label, labelStyle, textColor, lineColor }: DividerLabelProps) {
    const sharedClasses = [
        'mx-2 min-w-0 shrink-0',
        'max-w-[calc(100%-2rem)]',
        'break-words whitespace-normal',
        'text-center text-xs leading-5',
        '[overflow-wrap:anywhere]',
        'sm:mx-3',
        'sm:max-w-[calc(100%-3rem)]',
    ]

    if (labelStyle === 'pill') {
        return (
            <span
                aria-hidden="true"
                className={[
                    ...sharedClasses,
                    'rounded-full border px-3 py-1 font-bold',
                    'sm:px-3.5',
                ].join(' ')}
                style={{
                    color: textColor,
                    borderColor: lineColor,
                }}
            >
                {label}
            </span>
        )
    }

    return (
        <span
            aria-hidden="true"
            className={[...sharedClasses, 'px-1 font-semibold'].join(' ')}
            style={{
                color: textColor,
            }}
        >
            {label}
        </span>
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
