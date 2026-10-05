export type CallToActionBackgroundType = 'color' | 'image'

export type CallToActionTextTheme = 'light' | 'dark'

export interface CallToActionConfig {
    eyebrow: string | null
    heading: string
    description: string

    background_type: CallToActionBackgroundType
    background_color: string
    background_image: string | null
    background_overlay: number

    text_theme: CallToActionTextTheme

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

const DEFAULT_BACKGROUND_COLOR = '#0f172a'

const DEFAULT_BACKGROUND_OVERLAY = 70

const HEX_COLOR_PATTERN = /^#[0-9a-f]{6}$/i

const CONTACT_PATTERN = /^[0-9+()\-\s.]+$/

function containsControlCharacter(value: string): boolean {
    for (let index = 0; index < value.length; index += 1) {
        const code = value.charCodeAt(index)

        if (code <= 31 || code === 127) {
            return true
        }
    }

    return false
}

function readNullableString(value: unknown): string | null {
    if (typeof value !== 'string') {
        return null
    }

    const trimmed = value.trim()

    return trimmed === '' ? null : trimmed
}

function readString(value: unknown, fallback = ''): string {
    return readNullableString(value) ?? fallback
}

function readBackgroundColor(value: unknown): string {
    const color = readNullableString(value)

    if (!color || !HEX_COLOR_PATTERN.test(color)) {
        return DEFAULT_BACKGROUND_COLOR
    }

    return color.toLowerCase()
}

function readBackgroundOverlay(value: unknown): number {
    if (typeof value !== 'number' || !Number.isInteger(value)) {
        return DEFAULT_BACKGROUND_OVERLAY
    }

    return Math.min(100, Math.max(0, value))
}

function isSafeContactValue(value: string): boolean {
    if (containsControlCharacter(value)) {
        return false
    }

    if (!CONTACT_PATTERN.test(value)) {
        return false
    }

    const digits = value.replace(/\D+/g, '')

    return digits.length >= 6
}

function normalizePhoneForHref(value: string): string {
    const trimmed = value.trim()

    const digits = trimmed.replace(/\D+/g, '')

    if (digits.length < 6) {
        return ''
    }

    return trimmed.startsWith('+') ? `+${digits}` : digits
}

function isSafeEmailValue(value: string): boolean {
    if (value.length > 254 || containsControlCharacter(value) || /\s/.test(value)) {
        return false
    }

    /*
     * Laravel remains the authoritative
     * email validator.
     *
     * This defensive frontend check prevents
     * malformed persisted data from creating
     * unexpected mailto targets.
     */
    const firstAt = value.indexOf('@')

    const lastAt = value.lastIndexOf('@')

    if (firstAt <= 0 || firstAt !== lastAt || firstAt === value.length - 1) {
        return false
    }

    return !/[?#]/.test(value)
}

export function getSafeBackgroundImageUrl(value: string | null): string | null {
    if (!value) {
        return null
    }

    const trimmed = value.trim()

    if (trimmed === '' || containsControlCharacter(trimmed)) {
        return null
    }

    /*
     * Page Builder uploaded media normally
     * uses an application-relative path:
     *
     * /storage/page-builder/...
     */
    if (trimmed.startsWith('/') && !trimmed.startsWith('//')) {
        return trimmed
    }

    try {
        const url = new URL(trimmed)

        if (url.protocol !== 'http:' && url.protocol !== 'https:') {
            return null
        }

        return trimmed
    } catch {
        return null
    }
}

export function buildPhoneHref(phoneNumber: string | null): string {
    if (!phoneNumber) {
        return ''
    }

    const value = phoneNumber.trim()

    if (!isSafeContactValue(value)) {
        return ''
    }

    const normalized = normalizePhoneForHref(value)

    if (!normalized) {
        return ''
    }

    return `tel:${normalized}`
}

export function buildEmailHref(email: string | null): string {
    if (!email) {
        return ''
    }

    const value = email.trim()

    if (!isSafeEmailValue(value)) {
        return ''
    }

    return `mailto:${value}`
}

export function buildWhatsAppHref(whatsappNumber: string | null, message: string | null): string {
    if (!whatsappNumber) {
        return ''
    }

    const value = whatsappNumber.trim()

    if (!isSafeContactValue(value)) {
        return ''
    }

    const digits = value.replace(/\D+/g, '')

    if (digits.length < 6) {
        return ''
    }

    const baseUrl = `https://wa.me/${digits}`

    const safeMessage = readNullableString(message)

    if (!safeMessage) {
        return baseUrl
    }

    return `${baseUrl}?text=` + encodeURIComponent(safeMessage)
}

export function readCallToActionConfig(config: Record<string, unknown>): CallToActionConfig {
    const backgroundType: CallToActionBackgroundType =
        config.background_type === 'image' ? 'image' : 'color'

    const textTheme: CallToActionTextTheme = config.text_theme === 'dark' ? 'dark' : 'light'

    return {
        eyebrow: readNullableString(config.eyebrow),

        heading: readString(config.heading, 'Ready to get started?'),

        description: readString(config.description),

        background_type: backgroundType,

        background_color: readBackgroundColor(config.background_color),

        background_image: getSafeBackgroundImageUrl(readNullableString(config.background_image)),

        background_overlay: readBackgroundOverlay(config.background_overlay),

        text_theme: textTheme,

        phone_label: readNullableString(config.phone_label),

        phone_number: readNullableString(config.phone_number),

        email_label: readNullableString(config.email_label),

        email: readNullableString(config.email),

        whatsapp_label: readNullableString(config.whatsapp_label),

        whatsapp_number: readNullableString(config.whatsapp_number),

        whatsapp_message: readNullableString(config.whatsapp_message),

        newsletter_placeholder: readNullableString(config.newsletter_placeholder),

        newsletter_button_label: readNullableString(config.newsletter_button_label),

        newsletter_note: readNullableString(config.newsletter_note),
    }
}
