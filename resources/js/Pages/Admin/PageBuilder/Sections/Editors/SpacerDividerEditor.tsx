import type { SectionConfig } from '@/types/page-builder'

import type { SectionEditorProps } from '../types'

const MAX_HEIGHT = 320
const MAX_LABEL_LENGTH = 120

type LineStyle = 'solid' | 'dashed' | 'dotted'

type DividerWidth = 'full' | 'three_quarter' | 'half'

type DividerAlignment = 'left' | 'center' | 'right'

type LabelStyle = 'plain' | 'pill'

interface SpacerDividerConfig {
    mobile_height: number
    tablet_height: number
    desktop_height: number

    line_style: LineStyle
    line_color: string
    line_thickness: number

    width: DividerWidth
    alignment: DividerAlignment

    label: string | null
    label_style: LabelStyle
    text_color: string

    gradient_from: string
    gradient_via: string
    gradient_to: string
}

export default function SpacerDividerEditor({ section, value, onChange }: SectionEditorProps) {
    const mobileHeight = getInteger(value.mobile_height, 32, 0, MAX_HEIGHT)

    const tabletHeight = getInteger(value.tablet_height, 64, 0, MAX_HEIGHT)

    const desktopHeight = getInteger(value.desktop_height, 96, 0, MAX_HEIGHT)

    const lineStyle = getLineStyle(value.line_style)

    const lineColor = getHexColor(value.line_color, '#e2e8f0')

    const lineThickness = getInteger(value.line_thickness, 1, 1, 4)

    const width = getWidth(value.width)

    const alignment = getAlignment(value.alignment)

    const label = getNullableString(value.label)

    const labelStyle = getLabelStyle(value.label_style)

    const textColor = getHexColor(value.text_color, '#64748b')

    const gradientFrom = getHexColor(value.gradient_from, '#6366f1')

    const gradientVia = getHexColor(value.gradient_via, '#8b5cf6')

    const gradientTo = getHexColor(value.gradient_to, '#ec4899')

    const isSpacer = section.template === 'responsive_spacer'

    const isLine = section.template === 'line_divider'

    const isLabel = section.template === 'label_divider'

    const isGradient = section.template === 'gradient_divider'

    const updateConfig = (overrides: Partial<SpacerDividerConfig>) => {
        const nextConfig: SectionConfig = {
            mobile_height: overrides.mobile_height ?? mobileHeight,

            tablet_height: overrides.tablet_height ?? tabletHeight,

            desktop_height: overrides.desktop_height ?? desktopHeight,

            line_style: overrides.line_style ?? lineStyle,

            line_color: overrides.line_color ?? lineColor,

            line_thickness: overrides.line_thickness ?? lineThickness,

            width: overrides.width ?? width,

            alignment: overrides.alignment ?? alignment,

            label: overrides.label !== undefined ? overrides.label : label,

            label_style: overrides.label_style ?? labelStyle,

            text_color: overrides.text_color ?? textColor,

            gradient_from: overrides.gradient_from ?? gradientFrom,

            gradient_via: overrides.gradient_via ?? gradientVia,

            gradient_to: overrides.gradient_to ?? gradientTo,
        }

        onChange(nextConfig)
    }

    return (
        <div className="space-y-6">
            <section className="rounded-xl border border-neutral-200 bg-neutral-50/60 p-4 dark:border-neutral-800 dark:bg-neutral-900/40">
                <SectionHeading
                    title={templateTitle(section.template)}
                    description={templateDescription(section.template)}
                />

                <div className="mt-4">
                    <EditorPreview
                        template={section.template}
                        mobileHeight={mobileHeight}
                        lineStyle={lineStyle}
                        lineColor={lineColor}
                        lineThickness={lineThickness}
                        width={width}
                        alignment={alignment}
                        label={label}
                        labelStyle={labelStyle}
                        textColor={textColor}
                        gradientFrom={gradientFrom}
                        gradientVia={gradientVia}
                        gradientTo={gradientTo}
                    />
                </div>
            </section>

            <section className="space-y-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-800">
                <SectionHeading
                    title={isSpacer ? 'Responsive spacing' : 'Responsive section height'}
                    description={
                        isSpacer
                            ? 'Set the vertical whitespace independently for mobile, tablet, and desktop.'
                            : 'Control the breathing room around the divider at each responsive breakpoint.'
                    }
                />

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <NumberField
                        id="spacer-divider-mobile-height"
                        label="Mobile"
                        value={mobileHeight}
                        min={0}
                        max={MAX_HEIGHT}
                        suffix="px"
                        onChange={(nextValue) =>
                            updateConfig({
                                mobile_height: nextValue,
                            })
                        }
                    />

                    <NumberField
                        id="spacer-divider-tablet-height"
                        label="Tablet"
                        value={tabletHeight}
                        min={0}
                        max={MAX_HEIGHT}
                        suffix="px"
                        onChange={(nextValue) =>
                            updateConfig({
                                tablet_height: nextValue,
                            })
                        }
                    />

                    <NumberField
                        id="spacer-divider-desktop-height"
                        label="Desktop"
                        value={desktopHeight}
                        min={0}
                        max={MAX_HEIGHT}
                        suffix="px"
                        onChange={(nextValue) =>
                            updateConfig({
                                desktop_height: nextValue,
                            })
                        }
                    />
                </div>

                <p className="text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    Allowed range: 0–320px. A value of 0 removes spacing at that breakpoint.
                </p>
            </section>

            {!isSpacer && (
                <section className="space-y-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-800">
                    <SectionHeading
                        title="Divider layout"
                        description="Control the width and horizontal position of the divider."
                    />

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <SelectField
                            id="spacer-divider-width"
                            label="Width"
                            value={width}
                            options={[
                                {
                                    value: 'full',
                                    label: 'Full width',
                                },
                                {
                                    value: 'three_quarter',
                                    label: '75% width',
                                },
                                {
                                    value: 'half',
                                    label: '50% width',
                                },
                            ]}
                            onChange={(nextValue) =>
                                updateConfig({
                                    width: nextValue as DividerWidth,
                                })
                            }
                        />

                        <SelectField
                            id="spacer-divider-alignment"
                            label="Alignment"
                            value={alignment}
                            options={[
                                {
                                    value: 'left',
                                    label: 'Left',
                                },
                                {
                                    value: 'center',
                                    label: 'Center',
                                },
                                {
                                    value: 'right',
                                    label: 'Right',
                                },
                            ]}
                            onChange={(nextValue) =>
                                updateConfig({
                                    alignment: nextValue as DividerAlignment,
                                })
                            }
                        />
                    </div>
                </section>
            )}

            {(isLine || isLabel) && (
                <section className="space-y-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-800">
                    <SectionHeading
                        title="Line appearance"
                        description="Choose the divider pattern, color, and thickness."
                    />

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <SelectField
                            id="spacer-divider-line-style"
                            label="Line style"
                            value={lineStyle}
                            options={[
                                {
                                    value: 'solid',
                                    label: 'Solid',
                                },
                                {
                                    value: 'dashed',
                                    label: 'Dashed',
                                },
                                {
                                    value: 'dotted',
                                    label: 'Dotted',
                                },
                            ]}
                            onChange={(nextValue) =>
                                updateConfig({
                                    line_style: nextValue as LineStyle,
                                })
                            }
                        />

                        <NumberField
                            id="spacer-divider-line-thickness"
                            label="Thickness"
                            value={lineThickness}
                            min={1}
                            max={4}
                            suffix="px"
                            onChange={(nextValue) =>
                                updateConfig({
                                    line_thickness: nextValue,
                                })
                            }
                        />
                    </div>

                    <ColorField
                        id="spacer-divider-line-color"
                        label="Line color"
                        value={lineColor}
                        onChange={(nextValue) =>
                            updateConfig({
                                line_color: nextValue,
                            })
                        }
                    />
                </section>
            )}

            {isLabel && (
                <section className="space-y-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-800">
                    <SectionHeading
                        title="Divider label"
                        description="Add a short text bridge between the divider lines."
                    />

                    <TextField
                        id="spacer-divider-label"
                        label="Label"
                        value={label ?? ''}
                        maxLength={MAX_LABEL_LENGTH}
                        placeholder="Continue Exploring"
                        optional
                        onChange={(nextValue) =>
                            updateConfig({
                                label: nullableEditableString(nextValue),
                            })
                        }
                    />

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <SelectField
                            id="spacer-divider-label-style"
                            label="Label style"
                            value={labelStyle}
                            options={[
                                {
                                    value: 'plain',
                                    label: 'Plain text',
                                },
                                {
                                    value: 'pill',
                                    label: 'Pill badge',
                                },
                            ]}
                            onChange={(nextValue) =>
                                updateConfig({
                                    label_style: nextValue as LabelStyle,
                                })
                            }
                        />

                        <ColorField
                            id="spacer-divider-text-color"
                            label="Text color"
                            value={textColor}
                            onChange={(nextValue) =>
                                updateConfig({
                                    text_color: nextValue,
                                })
                            }
                        />
                    </div>
                </section>
            )}

            {isGradient && (
                <section className="space-y-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-800">
                    <SectionHeading
                        title="Gradient appearance"
                        description="Build a three-stop gradient divider and control its thickness."
                    />

                    <NumberField
                        id="spacer-divider-gradient-thickness"
                        label="Thickness"
                        value={lineThickness}
                        min={1}
                        max={4}
                        suffix="px"
                        onChange={(nextValue) =>
                            updateConfig({
                                line_thickness: nextValue,
                            })
                        }
                    />

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <ColorField
                            id="spacer-divider-gradient-from"
                            label="Start color"
                            value={gradientFrom}
                            onChange={(nextValue) =>
                                updateConfig({
                                    gradient_from: nextValue,
                                })
                            }
                        />

                        <ColorField
                            id="spacer-divider-gradient-via"
                            label="Middle color"
                            value={gradientVia}
                            onChange={(nextValue) =>
                                updateConfig({
                                    gradient_via: nextValue,
                                })
                            }
                        />

                        <ColorField
                            id="spacer-divider-gradient-to"
                            label="End color"
                            value={gradientTo}
                            onChange={(nextValue) =>
                                updateConfig({
                                    gradient_to: nextValue,
                                })
                            }
                        />
                    </div>
                </section>
            )}
        </div>
    )
}

interface SectionHeadingProps {
    title: string
    description: string
}

function SectionHeading({ title, description }: SectionHeadingProps) {
    return (
        <div>
            <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                {title}
            </h3>

            <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                {description}
            </p>
        </div>
    )
}

interface NumberFieldProps {
    id: string
    label: string
    value: number
    min: number
    max: number
    suffix?: string

    onChange: (value: number) => void
}

function NumberField({ id, label, value, min, max, suffix, onChange }: NumberFieldProps) {
    return (
        <div>
            <label
                htmlFor={id}
                className="text-sm font-medium text-neutral-900 dark:text-neutral-100"
            >
                {label}
            </label>

            <div className="relative mt-2">
                <input
                    id={id}
                    type="number"
                    value={value}
                    min={min}
                    max={max}
                    step={1}
                    onChange={(event) => onChange(clampInteger(event.target.value, min, max))}
                    className={[
                        'w-full rounded-lg border border-neutral-300',
                        'bg-white px-3 py-2 text-sm text-neutral-900',
                        'shadow-sm outline-none transition',
                        'focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200',
                        'dark:border-neutral-700 dark:bg-neutral-950',
                        'dark:text-neutral-100 dark:focus:ring-neutral-800',

                        suffix ? 'pr-10' : '',
                    ].join(' ')}
                />

                {suffix && (
                    <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-neutral-400">
                        {suffix}
                    </span>
                )}
            </div>
        </div>
    )
}

interface SelectOption {
    value: string
    label: string
}

interface SelectFieldProps {
    id: string
    label: string
    value: string
    options: SelectOption[]

    onChange: (value: string) => void
}

function SelectField({ id, label, value, options, onChange }: SelectFieldProps) {
    return (
        <div>
            <label
                htmlFor={id}
                className="text-sm font-medium text-neutral-900 dark:text-neutral-100"
            >
                {label}
            </label>

            <select
                id={id}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className={[
                    'mt-2 w-full rounded-lg border border-neutral-300',
                    'bg-white px-3 py-2 text-sm text-neutral-900',
                    'shadow-sm outline-none transition',
                    'focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200',
                    'dark:border-neutral-700 dark:bg-neutral-950',
                    'dark:text-neutral-100 dark:focus:ring-neutral-800',
                ].join(' ')}
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </div>
    )
}

interface TextFieldProps {
    id: string
    label: string
    value: string
    maxLength: number
    placeholder?: string
    optional?: boolean

    onChange: (value: string) => void
}

function TextField({
    id,
    label,
    value,
    maxLength,
    placeholder,
    optional = false,
    onChange,
}: TextFieldProps) {
    return (
        <div>
            <div className="flex items-center justify-between gap-3">
                <label
                    htmlFor={id}
                    className="text-sm font-medium text-neutral-900 dark:text-neutral-100"
                >
                    {label}

                    {optional && (
                        <span className="ml-1 font-normal text-neutral-400">(optional)</span>
                    )}
                </label>

                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                    {value.length}/{maxLength}
                </span>
            </div>

            <input
                id={id}
                type="text"
                value={value}
                maxLength={maxLength}
                placeholder={placeholder}
                onChange={(event) => onChange(event.target.value)}
                className={[
                    'mt-2 w-full rounded-lg border border-neutral-300',
                    'bg-white px-3 py-2 text-sm text-neutral-900',
                    'shadow-sm outline-none transition',
                    'focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200',
                    'dark:border-neutral-700 dark:bg-neutral-950',
                    'dark:text-neutral-100 dark:focus:ring-neutral-800',
                ].join(' ')}
            />
        </div>
    )
}

interface ColorFieldProps {
    id: string
    label: string
    value: string

    onChange: (value: string) => void
}

function ColorField({ id, label, value, onChange }: ColorFieldProps) {
    return (
        <div>
            <label
                htmlFor={id}
                className="text-sm font-medium text-neutral-900 dark:text-neutral-100"
            >
                {label}
            </label>

            <div className="mt-2 flex items-center gap-2">
                <input
                    id={id}
                    type="color"
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    className={[
                        'h-10 w-12 shrink-0 cursor-pointer rounded-lg',
                        'border border-neutral-300 bg-white p-1',
                        'dark:border-neutral-700 dark:bg-neutral-950',
                    ].join(' ')}
                />

                <input
                    type="text"
                    value={value}
                    maxLength={7}
                    aria-label={`${label} hexadecimal value`}
                    onChange={(event) => {
                        const nextValue = event.target.value

                        if (isHexColor(nextValue)) {
                            onChange(nextValue.toLowerCase())
                        }
                    }}
                    className={[
                        'min-w-0 flex-1 rounded-lg border border-neutral-300',
                        'bg-white px-3 py-2 font-mono text-sm text-neutral-900',
                        'shadow-sm outline-none transition',
                        'focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200',
                        'dark:border-neutral-700 dark:bg-neutral-950',
                        'dark:text-neutral-100 dark:focus:ring-neutral-800',
                    ].join(' ')}
                />
            </div>
        </div>
    )
}

interface EditorPreviewProps {
    template: string

    mobileHeight: number

    lineStyle: LineStyle

    lineColor: string
    lineThickness: number

    width: DividerWidth

    alignment: DividerAlignment

    label: string | null

    labelStyle: LabelStyle

    textColor: string

    gradientFrom: string
    gradientVia: string
    gradientTo: string
}

function EditorPreview({
    template,
    mobileHeight,
    lineStyle,
    lineColor,
    lineThickness,
    width,
    alignment,
    label,
    labelStyle,
    textColor,
    gradientFrom,
    gradientVia,
    gradientTo,
}: EditorPreviewProps) {
    const widthClass = previewWidthClass(width)

    const alignmentClass = previewAlignmentClass(alignment)

    if (template === 'responsive_spacer') {
        return (
            <div className="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-950">
                <div className="bg-neutral-100 px-3 py-2 text-center text-xs font-medium text-neutral-500 dark:bg-neutral-900 dark:text-neutral-400">
                    Previous section
                </div>

                <div
                    className={[
                        'flex items-center justify-center',
                        'border-y border-dashed border-indigo-300',
                        'bg-indigo-50/60',
                        'dark:border-indigo-800 dark:bg-indigo-950/20',
                    ].join(' ')}
                    style={{
                        minHeight: Math.min(Math.max(mobileHeight, 16), 120),
                    }}
                >
                    <span className="rounded-md bg-white px-2 py-1 text-[11px] font-semibold text-indigo-600 shadow-sm dark:bg-neutral-900 dark:text-indigo-300">
                        {mobileHeight}
                        px mobile
                    </span>
                </div>

                <div className="bg-neutral-100 px-3 py-2 text-center text-xs font-medium text-neutral-500 dark:bg-neutral-900 dark:text-neutral-400">
                    Next section
                </div>
            </div>
        )
    }

    if (template === 'gradient_divider') {
        return (
            <div className="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-950">
                <div
                    className={[widthClass, alignmentClass, 'rounded-full'].join(' ')}
                    style={{
                        height: lineThickness,
                        background: `linear-gradient(to right, ${gradientFrom}, ${gradientVia}, ${gradientTo})`,
                    }}
                />
            </div>
        )
    }

    if (template === 'label_divider') {
        return (
            <div className="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-950">
                <div className={[widthClass, alignmentClass, 'flex items-center'].join(' ')}>
                    <span
                        className="min-w-4 flex-1"
                        style={{
                            borderTopColor: lineColor,
                            borderTopStyle: lineStyle,
                            borderTopWidth: lineThickness,
                        }}
                    />

                    <span
                        className={[
                            'mx-3 shrink-0 text-xs font-semibold',

                            labelStyle === 'pill'
                                ? [
                                      'rounded-full border',
                                      'border-neutral-200',
                                      'bg-neutral-50',
                                      'px-3 py-1',
                                      'dark:border-neutral-700',
                                      'dark:bg-neutral-900',
                                  ].join(' ')
                                : 'px-1',
                        ].join(' ')}
                        style={{
                            color: textColor,
                        }}
                    >
                        {label ?? 'Label'}
                    </span>

                    <span
                        className="min-w-4 flex-1"
                        style={{
                            borderTopColor: lineColor,
                            borderTopStyle: lineStyle,
                            borderTopWidth: lineThickness,
                        }}
                    />
                </div>
            </div>
        )
    }

    return (
        <div className="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-950">
            <div
                className={[widthClass, alignmentClass].join(' ')}
                style={{
                    borderTopColor: lineColor,
                    borderTopStyle: lineStyle,
                    borderTopWidth: lineThickness,
                }}
            />
        </div>
    )
}

function templateTitle(template: string): string {
    switch (template) {
        case 'line_divider':
            return 'Line Divider'

        case 'label_divider':
            return 'Label Divider'

        case 'gradient_divider':
            return 'Gradient Divider'

        case 'responsive_spacer':
        default:
            return 'Responsive Spacer'
    }
}

function templateDescription(template: string): string {
    switch (template) {
        case 'line_divider':
            return 'Use a clean solid, dashed, or dotted separator between page sections.'

        case 'label_divider':
            return 'Combine a horizontal divider with a short plain or pill-style label.'

        case 'gradient_divider':
            return 'Create a decorative three-color transition between storefront sections.'

        case 'responsive_spacer':
        default:
            return 'Create adaptive whitespace that changes independently across responsive breakpoints.'
    }
}

function getInteger(value: unknown, fallback: number, min: number, max: number): number {
    if (typeof value !== 'number' || !Number.isInteger(value)) {
        return fallback
    }

    return Math.min(max, Math.max(min, value))
}

function clampInteger(value: string, min: number, max: number): number {
    const parsed = Number.parseInt(value, 10)

    if (!Number.isFinite(parsed)) {
        return min
    }

    return Math.min(max, Math.max(min, parsed))
}

function getLineStyle(value: unknown): LineStyle {
    if (value === 'dashed' || value === 'dotted') {
        return value
    }

    return 'solid'
}

function getWidth(value: unknown): DividerWidth {
    if (value === 'three_quarter' || value === 'half') {
        return value
    }

    return 'full'
}

function getAlignment(value: unknown): DividerAlignment {
    if (value === 'left' || value === 'right') {
        return value
    }

    return 'center'
}

function getLabelStyle(value: unknown): LabelStyle {
    return value === 'pill' ? 'pill' : 'plain'
}

function getNullableString(value: unknown): string | null {
    if (typeof value !== 'string') {
        return null
    }

    const normalized = value.trim()

    return normalized === '' ? null : normalized
}

function nullableEditableString(value: string): string | null {
    return value === '' ? null : value
}

function getHexColor(value: unknown, fallback: string): string {
    if (typeof value !== 'string') {
        return fallback
    }

    const normalized = value.trim().toLowerCase()

    return isHexColor(normalized) ? normalized : fallback
}

function isHexColor(value: string): boolean {
    return /^#[0-9a-fA-F]{6}$/.test(value)
}

function previewWidthClass(width: DividerWidth): string {
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

function previewAlignmentClass(alignment: DividerAlignment): string {
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
