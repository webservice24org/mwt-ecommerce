import type { SectionConfig } from '@/types/page-builder'

import PageBuilderImageField from '../../Media/PageBuilderImageField'
import type { SectionEditorProps } from '../types'

const MAX_EYEBROW_LENGTH = 120
const MAX_HEADING_LENGTH = 180
const MAX_DESCRIPTION_LENGTH = 1000
const MAX_LABEL_LENGTH = 80
const MAX_CONTACT_LENGTH = 80
const MAX_EMAIL_LENGTH = 254
const MAX_WHATSAPP_MESSAGE_LENGTH = 500
const MAX_NEWSLETTER_PLACEHOLDER_LENGTH = 160
const MAX_NEWSLETTER_NOTE_LENGTH = 255

type BackgroundType = 'color' | 'image'
type TextTheme = 'light' | 'dark'

interface CallToActionConfig {
    eyebrow: string | null
    heading: string
    description: string

    background_type: BackgroundType
    background_color: string
    background_image: string | null
    background_overlay: number

    text_theme: TextTheme

    phone_label: string | null
    phone_number: string | null

    email_label: string | null
    email: string | null

    whatsapp_label: string | null
    whatsapp_number: string | null
    whatsapp_message: string | null

    newsletter_placeholder: string | null
    newsletter_button_label: string | null
    newsletter_note: string | null
}

export default function CallToActionEditor({
    pageId,
    section,
    value,
    onChange,
}: SectionEditorProps) {
    const eyebrow = getNullableString(value.eyebrow)
    const heading = getString(value.heading)
    const description = getString(value.description)

    const backgroundType = getBackgroundType(value.background_type)
    const backgroundColor = getBackgroundColor(value.background_color)
    const backgroundImage = getNullableString(value.background_image)
    const backgroundOverlay = getOverlay(value.background_overlay)

    const textTheme = getTextTheme(value.text_theme)

    const phoneLabel = getNullableString(value.phone_label)
    const phoneNumber = getNullableString(value.phone_number)

    const emailLabel = getNullableString(value.email_label)
    const email = getNullableString(value.email)

    const whatsappLabel = getNullableString(value.whatsapp_label)
    const whatsappNumber = getNullableString(value.whatsapp_number)
    const whatsappMessage = getString(value.whatsapp_message)

    const newsletterPlaceholder = getNullableString(value.newsletter_placeholder)
    const newsletterButtonLabel = getNullableString(value.newsletter_button_label)
    const newsletterNote = getNullableString(value.newsletter_note)

    const showNewsletter = section.template === 'split_lead_capture'

    const updateConfig = (overrides: Partial<CallToActionConfig>) => {
        const nextConfig: SectionConfig = {
            eyebrow: overrides.eyebrow !== undefined ? overrides.eyebrow : eyebrow,

            heading: overrides.heading ?? heading,

            description: overrides.description ?? description,

            background_type: overrides.background_type ?? backgroundType,

            background_color: overrides.background_color ?? backgroundColor,

            background_image:
                overrides.background_image !== undefined
                    ? overrides.background_image
                    : backgroundImage,

            background_overlay: overrides.background_overlay ?? backgroundOverlay,

            text_theme: overrides.text_theme ?? textTheme,

            phone_label: overrides.phone_label !== undefined ? overrides.phone_label : phoneLabel,

            phone_number:
                overrides.phone_number !== undefined ? overrides.phone_number : phoneNumber,

            email_label: overrides.email_label !== undefined ? overrides.email_label : emailLabel,

            email: overrides.email !== undefined ? overrides.email : email,

            whatsapp_label:
                overrides.whatsapp_label !== undefined ? overrides.whatsapp_label : whatsappLabel,

            whatsapp_number:
                overrides.whatsapp_number !== undefined
                    ? overrides.whatsapp_number
                    : whatsappNumber,

            whatsapp_message:
                overrides.whatsapp_message !== undefined
                    ? overrides.whatsapp_message
                    : whatsappMessage,

            newsletter_placeholder:
                overrides.newsletter_placeholder !== undefined
                    ? overrides.newsletter_placeholder
                    : newsletterPlaceholder,

            newsletter_button_label:
                overrides.newsletter_button_label !== undefined
                    ? overrides.newsletter_button_label
                    : newsletterButtonLabel,

            newsletter_note:
                overrides.newsletter_note !== undefined
                    ? overrides.newsletter_note
                    : newsletterNote,
        }

        onChange(nextConfig)
    }

    return (
        <div className="space-y-6">
            {/* CTA CONTENT */}
            <section className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                <SectionHeading
                    title="CTA Content"
                    description="Configure the primary message displayed by this call-to-action section."
                />

                <div className="space-y-5">
                    <TextField
                        id="call-to-action-eyebrow"
                        label="Eyebrow"
                        value={eyebrow ?? ''}
                        maxLength={MAX_EYEBROW_LENGTH}
                        placeholder="Ready to get started?"
                        helpText="Optional short text displayed above the main heading."
                        onChange={(value) =>
                            updateConfig({
                                eyebrow: nullableString(value),
                            })
                        }
                    />

                    <TextField
                        id="call-to-action-heading"
                        label="Heading"
                        value={heading}
                        maxLength={MAX_HEADING_LENGTH}
                        required
                        placeholder="Let's build something great together"
                        helpText="Required. Enter the main call-to-action heading."
                        onChange={(value) =>
                            updateConfig({
                                heading: value,
                            })
                        }
                    />

                    <TextAreaField
                        id="call-to-action-description"
                        label="Description"
                        value={description}
                        maxLength={MAX_DESCRIPTION_LENGTH}
                        rows={5}
                        placeholder="Add supporting text for this call to action..."
                        helpText="Optional supporting plain-text description."
                        onChange={(value) =>
                            updateConfig({
                                description: value,
                            })
                        }
                    />
                </div>
            </section>

            {/* CONTACT METHODS */}
            <section className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                <SectionHeading
                    title="Contact Methods"
                    description="Configure the phone, email, and WhatsApp details displayed by this CTA."
                />

                <div className="space-y-6">
                    <div>
                        <h4 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            Phone
                        </h4>

                        <div className="mt-4 grid gap-5 sm:grid-cols-2">
                            <TextField
                                id="call-to-action-phone-label"
                                label="Phone label"
                                value={phoneLabel ?? ''}
                                maxLength={MAX_LABEL_LENGTH}
                                placeholder="Call Us"
                                onChange={(value) =>
                                    updateConfig({
                                        phone_label: nullableString(value),
                                    })
                                }
                            />

                            <TextField
                                id="call-to-action-phone-number"
                                label="Phone number"
                                value={phoneNumber ?? ''}
                                maxLength={MAX_CONTACT_LENGTH}
                                placeholder="+880 1700-000000"
                                helpText="Use a valid international contact number."
                                onChange={(value) =>
                                    updateConfig({
                                        phone_number: nullableString(value),
                                    })
                                }
                            />
                        </div>
                    </div>

                    <div className="border-t border-neutral-200 pt-6 dark:border-neutral-800">
                        <h4 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            Email
                        </h4>

                        <div className="mt-4 grid gap-5 sm:grid-cols-2">
                            <TextField
                                id="call-to-action-email-label"
                                label="Email label"
                                value={emailLabel ?? ''}
                                maxLength={MAX_LABEL_LENGTH}
                                placeholder="Email Us"
                                onChange={(value) =>
                                    updateConfig({
                                        email_label: nullableString(value),
                                    })
                                }
                            />

                            <TextField
                                id="call-to-action-email"
                                label="Email address"
                                type="email"
                                value={email ?? ''}
                                maxLength={MAX_EMAIL_LENGTH}
                                placeholder="hello@example.com"
                                onChange={(value) =>
                                    updateConfig({
                                        email: nullableString(value),
                                    })
                                }
                            />
                        </div>
                    </div>

                    <div className="border-t border-neutral-200 pt-6 dark:border-neutral-800">
                        <h4 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            WhatsApp
                        </h4>

                        <div className="mt-4 grid gap-5 sm:grid-cols-2">
                            <TextField
                                id="call-to-action-whatsapp-label"
                                label="WhatsApp label"
                                value={whatsappLabel ?? ''}
                                maxLength={MAX_LABEL_LENGTH}
                                placeholder="Chat on WhatsApp"
                                onChange={(value) =>
                                    updateConfig({
                                        whatsapp_label: nullableString(value),
                                    })
                                }
                            />

                            <TextField
                                id="call-to-action-whatsapp-number"
                                label="WhatsApp number"
                                value={whatsappNumber ?? ''}
                                maxLength={MAX_CONTACT_LENGTH}
                                placeholder="+880 1700-000000"
                                helpText="Use the WhatsApp-enabled international contact number."
                                onChange={(value) =>
                                    updateConfig({
                                        whatsapp_number: nullableString(value),
                                    })
                                }
                            />
                        </div>

                        <div className="mt-5">
                            <TextAreaField
                                id="call-to-action-whatsapp-message"
                                label="Prefilled WhatsApp message"
                                value={whatsappMessage ?? ''}
                                maxLength={MAX_WHATSAPP_MESSAGE_LENGTH}
                                rows={4}
                                placeholder="Hello, I would like to discuss a project."
                                helpText="Optional message that can be prefilled when the visitor opens WhatsApp."
                                onChange={(value) =>
                                    updateConfig({
                                        whatsapp_message: value,
                                    })
                                }
                            />
                        </div>
                    </div>
                </div>
            </section>

            {/* BACKGROUND */}
            <section className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                <SectionHeading
                    title="Background & Appearance"
                    description="Choose a solid color or upload an image for the CTA background."
                />

                <div className="space-y-6">
                    <div>
                        <p className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                            Background type
                        </p>

                        <div
                            role="radiogroup"
                            aria-label="CTA background type"
                            className="mt-3 grid gap-3 sm:grid-cols-2"
                        >
                            <RadioCard
                                name="call-to-action-background-type"
                                value="color"
                                checked={backgroundType === 'color'}
                                title="Solid Color"
                                description="Use the configured background color."
                                onChange={() =>
                                    updateConfig({
                                        background_type: 'color',
                                    })
                                }
                            />

                            <RadioCard
                                name="call-to-action-background-type"
                                value="image"
                                checked={backgroundType === 'image'}
                                title="Background Image"
                                description="Upload an image and place an overlay above it."
                                onChange={() =>
                                    updateConfig({
                                        background_type: 'image',
                                    })
                                }
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            htmlFor="call-to-action-background-color"
                            className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                        >
                            Background color
                        </label>

                        <div className="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <input
                                id="call-to-action-background-color"
                                type="color"
                                value={backgroundColor}
                                onChange={(event) =>
                                    updateConfig({
                                        background_color: event.target.value,
                                    })
                                }
                                className="h-11 w-16 cursor-pointer rounded-lg border border-neutral-300 bg-white p-1 dark:border-neutral-700 dark:bg-neutral-950"
                            />

                            <div className="flex-1">
                                <input
                                    type="text"
                                    value={backgroundColor}
                                    maxLength={7}
                                    onChange={(event) =>
                                        updateConfig({
                                            background_color: event.target.value,
                                        })
                                    }
                                    placeholder="#0f172a"
                                    className="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
                                />
                            </div>
                        </div>

                        <p className="mt-1.5 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                            This color is used directly for solid backgrounds and remains available
                            as the fallback color when an image background is selected.
                        </p>
                    </div>

                    {backgroundType === 'image' && (
                        <div className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                            <PageBuilderImageField
                                pageId={pageId}
                                label="Background Image"
                                value={backgroundImage}
                                helpText="Upload the image used behind this call-to-action section. JPG, PNG, and WebP images are supported."
                                onChange={(value) =>
                                    updateConfig({
                                        background_image: value,
                                    })
                                }
                            />

                            {backgroundImage === null && (
                                <p
                                    role="alert"
                                    className="mt-3 text-xs font-medium text-amber-700 dark:text-amber-400"
                                >
                                    An image is required while Background Image is selected.
                                </p>
                            )}
                        </div>
                    )}

                    <div>
                        <div className="flex items-center justify-between gap-4">
                            <label
                                htmlFor="call-to-action-background-overlay"
                                className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                            >
                                Background overlay
                            </label>

                            <span className="text-sm font-semibold tabular-nums text-neutral-700 dark:text-neutral-300">
                                {backgroundOverlay}%
                            </span>
                        </div>

                        <input
                            id="call-to-action-background-overlay"
                            type="range"
                            min={0}
                            max={100}
                            step={1}
                            value={backgroundOverlay}
                            onChange={(event) =>
                                updateConfig({
                                    background_overlay: Number(event.target.value),
                                })
                            }
                            className="mt-3 w-full cursor-pointer"
                        />

                        <div className="mt-1 flex justify-between text-xs text-neutral-500 dark:text-neutral-400">
                            <span>0%</span>
                            <span>100%</span>
                        </div>

                        <p className="mt-2 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                            Controls the dark overlay strength used with image backgrounds. The
                            storefront template will apply this value when rendering the background.
                        </p>
                    </div>

                    <div>
                        <label
                            htmlFor="call-to-action-text-theme"
                            className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                        >
                            Text theme
                        </label>

                        <select
                            id="call-to-action-text-theme"
                            value={textTheme}
                            onChange={(event) =>
                                updateConfig({
                                    text_theme: event.target.value as TextTheme,
                                })
                            }
                            className="mt-2 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
                        >
                            <option value="light">Light text</option>
                            <option value="dark">Dark text</option>
                        </select>

                        <p className="mt-1.5 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                            Select the text style that provides sufficient contrast against the
                            configured background.
                        </p>
                    </div>
                </div>
            </section>

            {/* NEWSLETTER / LEAD CAPTURE */}
            {showNewsletter && (
                <section className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                    <SectionHeading
                        title="Lead Capture"
                        description="Configure the labels that will be used by the Split Lead Capture subscription form."
                    />

                    <div className="space-y-5">
                        <TextField
                            id="call-to-action-newsletter-placeholder"
                            label="Email field placeholder"
                            value={newsletterPlaceholder ?? ''}
                            maxLength={MAX_NEWSLETTER_PLACEHOLDER_LENGTH}
                            placeholder="Enter your email address"
                            onChange={(value) =>
                                updateConfig({
                                    newsletter_placeholder: nullableString(value),
                                })
                            }
                        />

                        <TextField
                            id="call-to-action-newsletter-button-label"
                            label="Subscribe button label"
                            value={newsletterButtonLabel ?? ''}
                            maxLength={MAX_LABEL_LENGTH}
                            placeholder="Subscribe"
                            onChange={(value) =>
                                updateConfig({
                                    newsletter_button_label: nullableString(value),
                                })
                            }
                        />

                        <TextField
                            id="call-to-action-newsletter-note"
                            label="Newsletter note"
                            value={newsletterNote ?? ''}
                            maxLength={MAX_NEWSLETTER_NOTE_LENGTH}
                            placeholder="No spam, unsubscribe anytime."
                            onChange={(value) =>
                                updateConfig({
                                    newsletter_note: nullableString(value),
                                })
                            }
                        />

                        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                            These fields configure the subscription form presentation only. The real
                            newsletter submission workflow will be implemented in{' '}
                            <strong>4.15E.5</strong>.
                        </div>
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
        <div className="mb-4">
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
    helpText?: string
    required?: boolean
    type?: 'text' | 'email'
    onChange: (value: string) => void
}

function TextField({
    id,
    label,
    value,
    maxLength,
    placeholder,
    helpText,
    required = false,
    type = 'text',
    onChange,
}: TextFieldProps) {
    return (
        <div>
            <div className="flex items-center justify-between gap-3">
                <label
                    htmlFor={id}
                    className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                >
                    {label}
                    {required && <span className="ml-1 text-red-500">*</span>}
                </label>

                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                    {characterCount(value)}/{maxLength}
                </span>
            </div>

            <input
                id={id}
                type={type}
                value={value}
                maxLength={maxLength}
                required={required}
                onChange={(event) => onChange(event.target.value)}
                placeholder={placeholder}
                className="mt-2 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
            />

            {helpText && (
                <p className="mt-1.5 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    {helpText}
                </p>
            )}
        </div>
    )
}

interface TextAreaFieldProps {
    id: string
    label: string
    value: string
    maxLength: number
    rows?: number
    placeholder?: string
    helpText?: string
    onChange: (value: string) => void
}

function TextAreaField({
    id,
    label,
    value,
    maxLength,
    rows = 4,
    placeholder,
    helpText,
    onChange,
}: TextAreaFieldProps) {
    return (
        <div>
            <div className="flex items-center justify-between gap-3">
                <label
                    htmlFor={id}
                    className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                >
                    {label}
                </label>

                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                    {characterCount(value)}/{maxLength}
                </span>
            </div>

            <textarea
                id={id}
                value={value}
                maxLength={maxLength}
                rows={rows}
                onChange={(event) => onChange(event.target.value)}
                placeholder={placeholder}
                className="mt-2 w-full resize-y rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm leading-6 text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
            />

            {helpText && (
                <p className="mt-1.5 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    {helpText}
                </p>
            )}
        </div>
    )
}

interface RadioCardProps {
    name: string
    value: string
    checked: boolean
    title: string
    description: string
    onChange: () => void
}

function RadioCard({ name, value, checked, title, description, onChange }: RadioCardProps) {
    return (
        <label
            className={[
                'cursor-pointer rounded-lg border p-4 transition',
                checked
                    ? 'border-neutral-900 bg-neutral-50 ring-1 ring-neutral-900 dark:border-neutral-100 dark:bg-neutral-900 dark:ring-neutral-100'
                    : 'border-neutral-200 bg-white hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-600',
            ].join(' ')}
        >
            <input
                type="radio"
                name={name}
                value={value}
                checked={checked}
                onChange={onChange}
                className="sr-only"
            />

            <span className="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                {title}
            </span>

            <span className="mt-1 block text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                {description}
            </span>
        </label>
    )
}

function getString(value: SectionConfig[string]): string {
    return typeof value === 'string' ? value : ''
}

function getNullableString(value: SectionConfig[string]): string | null {
    if (typeof value !== 'string') {
        return null
    }

    const normalized = value.trim()

    return normalized.length > 0 ? normalized : null
}

function nullableString(value: string): string | null {
    const normalized = value.trim()

    return normalized.length > 0 ? normalized : null
}

function getBackgroundType(value: SectionConfig[string]): BackgroundType {
    return value === 'image' ? 'image' : 'color'
}

function getBackgroundColor(value: SectionConfig[string]): string {
    if (typeof value !== 'string') {
        return '#0f172a'
    }

    const normalized = value.trim()

    return /^#[0-9a-fA-F]{6}$/.test(normalized) ? normalized : '#0f172a'
}

function getOverlay(value: SectionConfig[string]): number {
    if (typeof value !== 'number' || !Number.isInteger(value) || value < 0 || value > 100) {
        return 70
    }

    return value
}

function getTextTheme(value: SectionConfig[string]): TextTheme {
    return value === 'dark' ? 'dark' : 'light'
}

function characterCount(value: string): number {
    return Array.from(value).length
}
