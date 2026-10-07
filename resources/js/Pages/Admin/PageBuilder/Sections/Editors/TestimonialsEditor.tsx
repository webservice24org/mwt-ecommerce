import type { JsonValue, SectionConfig } from '@/types/page-builder'

import type { SectionEditorProps } from '../types'
import TestimonialItemsEditor, { type TestimonialItem } from './TestimonialItemsEditor'

const MAX_EYEBROW_LENGTH = 120
const MAX_HEADING_LENGTH = 180
const MAX_DESCRIPTION_LENGTH = 1000

type TestimonialAlignment = 'left' | 'center'

type TestimonialTextTheme = 'light' | 'dark'

type TestimonialSlideEffect =
    'none' | 'fade' | 'slide_left' | 'slide_right' | 'slide_up' | 'slide_down'

interface TestimonialsConfig {
    eyebrow: string | null
    heading: string | null
    description: string

    items: TestimonialItem[]

    alignment: TestimonialAlignment

    background_color: string
    text_theme: TestimonialTextTheme

    show_rating: boolean

    autoplay: boolean
    autoplay_interval: number
    pause_on_hover: boolean
    loop: boolean

    show_arrows: boolean
    show_dots: boolean

    slide_effect: TestimonialSlideEffect
}

export default function TestimonialsEditor({
    pageId,
    section,
    value,
    onChange,
}: SectionEditorProps) {
    const eyebrow = getNullableString(value.eyebrow)

    const heading = getNullableString(value.heading)

    const description = getString(value.description)

    const items = getItems(value.items)

    const alignment = getAlignment(value.alignment)

    const backgroundColor = getBackgroundColor(value.background_color)

    const textTheme = getTextTheme(value.text_theme)

    const showRating = getBoolean(value.show_rating, true)

    const autoplay = getBoolean(value.autoplay, true)

    const autoplayInterval = getInteger(value.autoplay_interval, 5000)

    const pauseOnHover = getBoolean(value.pause_on_hover, true)

    const loop = getBoolean(value.loop, true)

    const showArrows = getBoolean(value.show_arrows, true)

    const showDots = getBoolean(value.show_dots, true)

    const slideEffect = getSlideEffect(value.slide_effect)

    const isGridSlider = section.template === 'grid_slider'

    const isSpotlightSlider = section.template === 'spotlight_slider'

    const isCardSlider = section.template === 'card_slider'

    const updateConfig = (overrides: Partial<TestimonialsConfig>) => {
        const nextConfig: SectionConfig = {
            eyebrow: overrides.eyebrow !== undefined ? overrides.eyebrow : eyebrow,

            heading: overrides.heading !== undefined ? overrides.heading : heading,

            description: overrides.description ?? description,

            items: overrides.items ?? items,

            alignment: overrides.alignment ?? alignment,

            background_color: overrides.background_color ?? backgroundColor,

            text_theme: overrides.text_theme ?? textTheme,

            show_rating: overrides.show_rating ?? showRating,

            autoplay: overrides.autoplay ?? autoplay,

            autoplay_interval: overrides.autoplay_interval ?? autoplayInterval,

            pause_on_hover: overrides.pause_on_hover ?? pauseOnHover,

            loop: overrides.loop ?? loop,

            show_arrows: overrides.show_arrows ?? showArrows,

            show_dots: overrides.show_dots ?? showDots,

            slide_effect: overrides.slide_effect ?? slideEffect,
        }

        onChange(nextConfig)
    }

    return (
        <div className="space-y-6">
            <section className="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800 sm:p-5">
                <SectionHeading
                    title="Testimonials content"
                    description="Configure the heading and introductory content displayed above the testimonial slider."
                />

                <div className="mt-5 space-y-5">
                    <TextField
                        id="testimonials-eyebrow"
                        label="Eyebrow"
                        value={eyebrow ?? ''}
                        maxLength={MAX_EYEBROW_LENGTH}
                        placeholder={
                            isGridSlider
                                ? 'Customer Reviews'
                                : isCardSlider
                                  ? 'Client Stories'
                                  : 'Featured Stories'
                        }
                        optional
                        onChange={(nextValue) =>
                            updateConfig({
                                eyebrow: nullableEditableString(nextValue),
                            })
                        }
                    />

                    <TextField
                        id="testimonials-heading"
                        label="Heading"
                        value={heading ?? ''}
                        maxLength={MAX_HEADING_LENGTH}
                        placeholder={
                            isGridSlider
                                ? 'Loved by Our Customers'
                                : isCardSlider
                                  ? 'Continuous Feedback Stream'
                                  : 'What Our Customers Say'
                        }
                        optional
                        onChange={(nextValue) =>
                            updateConfig({
                                heading: nullableEditableString(nextValue),
                            })
                        }
                    />

                    <TextAreaField
                        id="testimonials-description"
                        label="Description"
                        value={description}
                        maxLength={MAX_DESCRIPTION_LENGTH}
                        rows={4}
                        optional
                        onChange={(nextValue) =>
                            updateConfig({
                                description: nextValue,
                            })
                        }
                    />
                </div>
            </section>

            <section className="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800 sm:p-5">
                <SectionHeading
                    title="Appearance"
                    description="Control the section alignment, background, and text treatment."
                />

                <div className="mt-5 grid gap-5 sm:grid-cols-2">
                    <SelectField
                        id="testimonials-alignment"
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
                        ]}
                        onChange={(nextValue) =>
                            updateConfig({
                                alignment: nextValue as TestimonialAlignment,
                            })
                        }
                    />

                    <SelectField
                        id="testimonials-text-theme"
                        label="Text theme"
                        value={textTheme}
                        options={[
                            {
                                value: 'dark',
                                label: 'Dark text',
                            },
                            {
                                value: 'light',
                                label: 'Light text',
                            },
                        ]}
                        onChange={(nextValue) =>
                            updateConfig({
                                text_theme: nextValue as TestimonialTextTheme,
                            })
                        }
                    />
                </div>

                <div className="mt-5">
                    <ColorField
                        id="testimonials-background-color"
                        label="Background color"
                        value={backgroundColor}
                        onChange={(nextValue) =>
                            updateConfig({
                                background_color: nextValue,
                            })
                        }
                    />
                </div>

                <div className="mt-5">
                    <ToggleField
                        id="testimonials-show-rating"
                        label="Show rating"
                        description="Display testimonial star ratings when a rating is available."
                        checked={showRating}
                        onChange={(checked) =>
                            updateConfig({
                                show_rating: checked,
                            })
                        }
                    />
                </div>
            </section>

            <section className="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800 sm:p-5">
                <SectionHeading
                    title="Slider settings"
                    description="These options apply to all three Testimonials slider designs."
                />

                <div className="mt-5 grid gap-3 sm:grid-cols-2">
                    <ToggleField
                        id="testimonials-autoplay"
                        label="Autoplay"
                        description="Automatically advance testimonials. Enabled by default."
                        checked={autoplay}
                        onChange={(checked) =>
                            updateConfig({
                                autoplay: checked,
                            })
                        }
                    />

                    <ToggleField
                        id="testimonials-pause-on-hover"
                        label="Pause on hover"
                        description="Pause autoplay while the pointer is over the slider."
                        checked={pauseOnHover}
                        onChange={(checked) =>
                            updateConfig({
                                pause_on_hover: checked,
                            })
                        }
                    />

                    <ToggleField
                        id="testimonials-loop"
                        label="Loop slides"
                        description="Continue from the first slide after reaching the final slide."
                        checked={loop}
                        onChange={(checked) =>
                            updateConfig({
                                loop: checked,
                            })
                        }
                    />

                    <ToggleField
                        id="testimonials-show-arrows"
                        label="Show arrows"
                        description="Display previous and next navigation buttons."
                        checked={showArrows}
                        onChange={(checked) =>
                            updateConfig({
                                show_arrows: checked,
                            })
                        }
                    />

                    <ToggleField
                        id="testimonials-show-dots"
                        label="Show dots"
                        description="Display slide position indicators."
                        checked={showDots}
                        onChange={(checked) =>
                            updateConfig({
                                show_dots: checked,
                            })
                        }
                    />
                </div>

                <div className="mt-5 grid gap-5 sm:grid-cols-2">
                    <NumberField
                        id="testimonials-autoplay-interval"
                        label="Autoplay interval"
                        value={autoplayInterval}
                        min={2000}
                        max={20000}
                        step={500}
                        suffix="ms"
                        disabled={!autoplay}
                        helpText="Supported range: 2000–20000 ms. Default: 5000 ms."
                        onChange={(nextValue) =>
                            updateConfig({
                                autoplay_interval: nextValue,
                            })
                        }
                    />

                    <SelectField
                        id="testimonials-slide-effect"
                        label="Slide effect"
                        value={slideEffect}
                        options={[
                            {
                                value: 'none',
                                label: 'None',
                            },
                            {
                                value: 'fade',
                                label: 'Fade',
                            },
                            {
                                value: 'slide_left',
                                label: 'Slide left',
                            },
                            {
                                value: 'slide_right',
                                label: 'Slide right',
                            },
                            {
                                value: 'slide_up',
                                label: 'Slide up',
                            },
                            {
                                value: 'slide_down',
                                label: 'Slide down',
                            },
                        ]}
                        onChange={(nextValue) =>
                            updateConfig({
                                slide_effect: nextValue as TestimonialSlideEffect,
                            })
                        }
                    />
                </div>

                <p className="mt-4 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    Storefront transitions will use the existing shared
                    <code className="mx-1 rounded bg-neutral-100 px-1.5 py-0.5 dark:bg-neutral-900">
                        SlideTransition.tsx
                    </code>
                    component in H.4.
                </p>
            </section>

            <TestimonialItemsEditor
                pageId={pageId}
                items={items}
                showBadge={isSpotlightSlider}
                onChange={(nextItems) =>
                    updateConfig({
                        items: nextItems,
                    })
                }
            />
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

                <span className="text-xs text-neutral-400">
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
                className="mt-2 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
            />
        </div>
    )
}

interface TextAreaFieldProps {
    id: string
    label: string
    value: string
    maxLength: number
    rows: number
    optional?: boolean

    onChange: (value: string) => void
}

function TextAreaField({
    id,
    label,
    value,
    maxLength,
    rows,
    optional = false,
    onChange,
}: TextAreaFieldProps) {
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

                <span className="text-xs text-neutral-400">
                    {value.length}/{maxLength}
                </span>
            </div>

            <textarea
                id={id}
                value={value}
                rows={rows}
                maxLength={maxLength}
                onChange={(event) => onChange(event.target.value)}
                className="mt-2 w-full resize-y rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm leading-6 text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
            />
        </div>
    )
}

interface SelectFieldProps {
    id: string
    label: string
    value: string

    options: Array<{
        value: string
        label: string
    }>

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
                className="mt-2 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
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

interface ToggleFieldProps {
    id: string
    label: string
    description: string
    checked: boolean

    onChange: (checked: boolean) => void
}

function ToggleField({ id, label, description, checked, onChange }: ToggleFieldProps) {
    return (
        <label
            htmlFor={id}
            className="flex cursor-pointer items-start gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-800"
        >
            <input
                id={id}
                type="checkbox"
                checked={checked}
                onChange={(event) => onChange(event.target.checked)}
                className="mt-0.5 h-4 w-4 shrink-0"
            />

            <span>
                <span className="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                    {label}
                </span>

                <span className="mt-1 block text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    {description}
                </span>
            </span>
        </label>
    )
}

interface NumberFieldProps {
    id: string
    label: string
    value: number

    min: number
    max: number
    step?: number

    suffix?: string
    helpText?: string
    disabled?: boolean

    onChange: (value: number) => void
}

function NumberField({
    id,
    label,
    value,
    min,
    max,
    step = 1,
    suffix,
    helpText,
    disabled = false,
    onChange,
}: NumberFieldProps) {
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
                    step={step}
                    disabled={disabled}
                    onChange={(event) => {
                        const nextValue = Number(event.target.value)

                        if (!Number.isFinite(nextValue)) {
                            return
                        }

                        onChange(nextValue)
                    }}
                    className={[
                        'w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm',
                        'text-neutral-900 shadow-sm outline-none transition',
                        'focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200',
                        'disabled:cursor-not-allowed disabled:opacity-50',
                        'dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100',
                        'dark:focus:ring-neutral-800',

                        suffix ? 'pr-14' : '',
                    ].join(' ')}
                />

                {suffix && (
                    <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-neutral-400">
                        {suffix}
                    </span>
                )}
            </div>

            {helpText && (
                <p className="mt-1.5 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    {helpText}
                </p>
            )}
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

            <div className="mt-2 flex items-center gap-3">
                <input
                    id={id}
                    type="color"
                    value={getBackgroundColor(value)}
                    onChange={(event) => onChange(event.target.value)}
                    className="h-10 w-14 cursor-pointer rounded-lg border border-neutral-300 bg-white p-1 dark:border-neutral-700 dark:bg-neutral-950"
                />

                <input
                    type="text"
                    value={value}
                    maxLength={7}
                    onChange={(event) => onChange(event.target.value)}
                    className="min-w-0 flex-1 rounded-lg border border-neutral-300 bg-white px-3 py-2 font-mono text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
                />
            </div>
        </div>
    )
}

function getItems(value: JsonValue | undefined): TestimonialItem[] {
    if (!Array.isArray(value)) {
        return []
    }

    const items: TestimonialItem[] = []

    for (const item of value) {
        if (!isRecord(item)) {
            continue
        }

        items.push({
            quote: getString(item.quote),

            name: getString(item.name),

            role: getNullableString(item.role),

            rating: getRating(item.rating),

            image: getNullableString(item.image),

            image_alt: getNullableString(item.image_alt),

            badge: getNullableString(item.badge),
        })
    }

    return items
}

function getString(value: JsonValue | undefined): string {
    return typeof value === 'string' ? value : ''
}

function getNullableString(value: JsonValue | undefined): string | null {
    if (typeof value !== 'string') {
        return null
    }

    return value
}

function nullableEditableString(value: string): string | null {
    return value === '' ? null : value
}

function getInteger(value: JsonValue | undefined, fallback: number): number {
    return typeof value === 'number' && Number.isInteger(value) ? value : fallback
}

function getBoolean(value: JsonValue | undefined, fallback: boolean): boolean {
    return typeof value === 'boolean' ? value : fallback
}

function getRating(value: JsonValue | undefined): number | null {
    if (typeof value !== 'number' || !Number.isInteger(value) || value < 1 || value > 5) {
        return null
    }

    return value
}

function getAlignment(value: JsonValue | undefined): TestimonialAlignment {
    return value === 'center' ? 'center' : 'left'
}

function getTextTheme(value: JsonValue | undefined): TestimonialTextTheme {
    return value === 'light' ? 'light' : 'dark'
}

function getBackgroundColor(value: JsonValue | undefined): string {
    if (typeof value === 'string' && /^#[0-9a-fA-F]{6}$/.test(value)) {
        return value
    }

    return '#ffffff'
}

function getSlideEffect(value: JsonValue | undefined): TestimonialSlideEffect {
    switch (value) {
        case 'none':
        case 'fade':
        case 'slide_left':
        case 'slide_right':
        case 'slide_up':
        case 'slide_down':
            return value

        default:
            return 'slide_left'
    }
}

function isRecord(value: JsonValue): value is {
    [key: string]: JsonValue
} {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
}
