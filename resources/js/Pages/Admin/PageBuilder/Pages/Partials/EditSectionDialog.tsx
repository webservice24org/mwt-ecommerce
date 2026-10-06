import { router } from '@inertiajs/react'
import { useState } from 'react'

import Dialog from '@/Components/Admin/Dialog'
import type {
    CatalogCategoryOption,
    CatalogProductOption,
    CatalogSourceDefinition,
    JsonValue,
    PageSection,
    SectionConfig,
    SectionDefinition,
    SectionLayout,
    SectionWidth,
} from '@/types/page-builder'

import SectionEditorRenderer from '../../Sections/Editors/SectionEditorRenderer'
import { hasSectionEditor } from '../../Sections/section-editor-registry'
import { isFeatureBenefitIconName } from '@/PageBuilder/feature-benefit-icon-options'
import {
    getSafeFeatureImageUrl,
    getSafeFeatureLinkUrl,
} from '@/PageBuilder/features-benefits-security'

interface Props {
    open: boolean
    pageId: number
    section: PageSection | null
    sectionDefinitions: SectionDefinition[]
    catalogSources: CatalogSourceDefinition[]
    categoryOptions: CatalogCategoryOption[]
    selectedProductOptions: CatalogProductOption[]
    onClose: () => void
}

export default function EditSectionDialog({
    open,
    pageId,
    section,
    sectionDefinitions,
    catalogSources,
    categoryOptions,
    selectedProductOptions,
    onClose,
}: Props) {
    if (!open || section === null) {
        return null
    }

    return (
        <EditSectionDialogSession
            pageId={pageId}
            section={section}
            sectionDefinitions={sectionDefinitions}
            catalogSources={catalogSources}
            categoryOptions={categoryOptions}
            selectedProductOptions={selectedProductOptions}
            onClose={onClose}
        />
    )
}

interface EditSectionDialogSessionProps {
    pageId: number
    section: PageSection
    sectionDefinitions: SectionDefinition[]
    catalogSources: CatalogSourceDefinition[]
    categoryOptions: CatalogCategoryOption[]
    selectedProductOptions: CatalogProductOption[]
    onClose: () => void
}

function EditSectionDialogSession({
    pageId,
    section,
    sectionDefinitions,
    catalogSources,
    categoryOptions,
    selectedProductOptions,
    onClose,
}: EditSectionDialogSessionProps) {
    const [config, setConfig] = useState<SectionConfig>(() => structuredClone(section.config))

    const [layout, setLayout] = useState<SectionLayout>(() =>
        normalizeSectionLayout(section.layout),
    )

    const [enabled, setEnabled] = useState(section.is_enabled)

    const [processing, setProcessing] = useState(false)

    const [errors, setErrors] = useState<Record<string, string>>({})

    const definition = sectionDefinitions.find((item) => item.type === section.type) ?? null

    const editorAvailable = definition !== null && hasSectionEditor(section.type)

    const configValid = isConfigValid(section.type, config)

    const close = () => {
        if (processing) {
            return
        }

        onClose()
    }

    const save = () => {
        if (definition === null || !editorAvailable || !configValid || processing) {
            return
        }

        setProcessing(true)
        setErrors({})

        router.put(
            route('admin.pages.sections.update', [pageId, section.id]),
            {
                type: section.type,
                template: section.template,
                config,
                layout: {
                    width: layout.width,
                },
                is_enabled: enabled,
            },
            {
                preserveScroll: true,

                onError: (validationErrors) => {
                    setErrors(validationErrors)
                },

                onSuccess: () => {
                    onClose()
                },

                onFinish: () => {
                    setProcessing(false)
                },
            },
        )
    }

    const sectionLabel = definition?.label ?? formatIdentifier(section.type)

    const templateLabel =
        definition?.templates.find((template) => template.key === section.template)?.label ??
        formatIdentifier(section.template)

    return (
        <Dialog
            open
            title={`Edit ${sectionLabel}`}
            description={`${templateLabel} template`}
            maxWidthClass="max-w-3xl"
            onClose={close}
        >
            <div className="space-y-6">
                {definition === null ? (
                    <div
                        role="alert"
                        className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-200"
                    >
                        This section type is no longer registered in the Page Builder.
                    </div>
                ) : (
                    <>
                        <SectionEditorRenderer
                            pageId={pageId}
                            section={section}
                            definition={definition}
                            value={config}
                            catalogSources={catalogSources}
                            categoryOptions={categoryOptions}
                            selectedProductOptions={selectedProductOptions}
                            onChange={setConfig}
                        />

                        <SectionLayoutSelector
                            value={layout.width}
                            disabled={processing}
                            error={errors['layout.width']}
                            onChange={(width) =>
                                setLayout({
                                    width,
                                })
                            }
                        />

                        <div className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                            <label className="flex cursor-pointer items-start gap-3">
                                <input
                                    type="checkbox"
                                    checked={enabled}
                                    disabled={processing}
                                    onChange={(event) => setEnabled(event.target.checked)}
                                    className="mt-0.5 h-4 w-4 rounded border-neutral-300"
                                />

                                <span>
                                    <span className="block text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                        Enable section
                                    </span>

                                    <span className="mt-1 block text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                        Disabled sections remain in the builder but are not rendered
                                        on the storefront.
                                    </span>
                                </span>
                            </label>
                        </div>
                    </>
                )}

                {Object.keys(errors).length > 0 && (
                    <div
                        role="alert"
                        className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-200"
                    >
                        <p className="font-semibold">Please correct the following:</p>

                        <ul className="mt-2 list-disc space-y-1 pl-5">
                            {Object.entries(errors).map(([field, message]) => (
                                <li key={field}>{message}</li>
                            ))}
                        </ul>
                    </div>
                )}

                <div className="flex flex-col-reverse gap-3 border-t border-neutral-200 pt-5 sm:flex-row sm:justify-end dark:border-neutral-800">
                    <button
                        type="button"
                        disabled={processing}
                        onClick={close}
                        className="inline-flex items-center justify-center rounded-lg border border-neutral-200 px-4 py-2.5 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        disabled={
                            processing || definition === null || !editorAvailable || !configValid
                        }
                        onClick={save}
                        className="inline-flex items-center justify-center rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-neutral-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-neutral-300"
                    >
                        {processing ? 'Saving...' : 'Save Changes'}
                    </button>
                </div>
            </div>
        </Dialog>
    )
}

interface SectionLayoutSelectorProps {
    value: SectionWidth
    disabled: boolean
    error?: string
    onChange: (value: SectionWidth) => void
}

interface SectionLayoutOption {
    value: SectionWidth
    label: string
    description: string
}

const sectionLayoutOptions: SectionLayoutOption[] = [
    {
        value: 'container',
        label: 'Container',
        description: 'Keep this section inside the storefront content container.',
    },
    {
        value: 'full',
        label: 'Full Width',
        description: 'Allow this section to span the full available page width.',
    },
]

function SectionLayoutSelector({ value, disabled, error, onChange }: SectionLayoutSelectorProps) {
    return (
        <section className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
            <div className="mb-4">
                <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                    Section Layout
                </h3>

                <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    Choose how wide this section should be displayed on the storefront.
                </p>
            </div>

            {error && (
                <p role="alert" className="mb-3 text-sm text-red-600 dark:text-red-400">
                    {error}
                </p>
            )}

            <div
                role="radiogroup"
                aria-label="Section layout"
                className="grid gap-3 sm:grid-cols-2"
            >
                {sectionLayoutOptions.map((option) => {
                    const selected = value === option.value

                    return (
                        <label
                            key={option.value}
                            className={[
                                'rounded-lg border p-4 transition',
                                disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer',
                                selected
                                    ? 'border-neutral-900 bg-neutral-50 ring-1 ring-neutral-900 dark:border-neutral-100 dark:bg-neutral-900 dark:ring-neutral-100'
                                    : 'border-neutral-200 bg-white hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-600',
                            ].join(' ')}
                        >
                            <input
                                type="radio"
                                name="section_layout_width"
                                value={option.value}
                                checked={selected}
                                disabled={disabled}
                                onChange={() => onChange(option.value)}
                                className="sr-only"
                            />

                            <SectionLayoutDiagram width={option.value} selected={selected} />

                            <span className="mt-3 block text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                {option.label}
                            </span>

                            <span className="mt-1 block text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                {option.description}
                            </span>
                        </label>
                    )
                })}
            </div>
        </section>
    )
}

function SectionLayoutDiagram({ width, selected }: { width: SectionWidth; selected: boolean }) {
    const contentClass = selected
        ? 'bg-neutral-700 dark:bg-neutral-300'
        : 'bg-neutral-300 dark:bg-neutral-700'

    return (
        <span
            aria-hidden="true"
            className={[
                'flex h-16 items-center rounded-lg border px-2 transition',
                selected
                    ? 'border-neutral-400 bg-white dark:border-neutral-600 dark:bg-neutral-950'
                    : 'border-neutral-200 bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900',
            ].join(' ')}
        >
            <span
                className={[
                    'block h-9 rounded',
                    contentClass,
                    width === 'container' ? 'mx-auto w-3/4' : 'w-full',
                ].join(' ')}
            />
        </span>
    )
}

function normalizeSectionLayout(layout: SectionLayout | null | undefined): SectionLayout {
    if (layout?.width === 'full') {
        return {
            width: 'full',
        }
    }

    return {
        width: 'container',
    }
}

function formatIdentifier(value: string): string {
    return value
        .split(/[_-]/)
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ')
}

function isConfigValid(type: string, config: SectionConfig): boolean {
    if (type === 'features_benefits') {
        return isFeaturesBenefitsConfigValid(config)
    }
    if (type === 'call_to_action') {
        return isCallToActionConfigValid(config)
    }

    if (type === 'content') {
        return isContentConfigValid(config)
    }

    if (type === 'promotional_banner') {
        return isPromotionalBannerConfigValid(config)
    }

    if (type !== 'featured_products' && type !== 'product_collection') {
        return true
    }

    const title = config.title
    const limit = config.limit

    const source = isJsonObject(config.source)
        ? config.source
        : {
              type: type === 'product_collection' ? 'latest' : 'featured',
          }

    if (
        typeof title !== 'string' ||
        title.trim().length === 0 ||
        title.length > 120 ||
        typeof limit !== 'number' ||
        !Number.isInteger(limit) ||
        limit < 1 ||
        limit > 24
    ) {
        return false
    }

    const sourceType = source.type

    if (sourceType === 'featured' || (type === 'product_collection' && sourceType === 'latest')) {
        return true
    }

    if (sourceType === 'category') {
        return (
            typeof source.category_id === 'number' &&
            Number.isInteger(source.category_id) &&
            source.category_id > 0
        )
    }

    if (sourceType === 'manual') {
        const productIds = source.product_ids

        return (
            Array.isArray(productIds) &&
            productIds.length > 0 &&
            productIds.length <= 24 &&
            productIds.every(
                (productId) =>
                    typeof productId === 'number' && Number.isInteger(productId) && productId > 0,
            ) &&
            new Set(productIds).size === productIds.length
        )
    }

    return false
}

function isFeaturesBenefitsConfigValid(config: SectionConfig): boolean {
    const allowedKeys = new Set([
        'eyebrow',
        'heading',
        'description',
        'items',
        'columns',
        'alignment',
        'background_color',
        'text_theme',
    ])

    if (Object.keys(config).some((key) => !allowedKeys.has(key))) {
        return false
    }

    const eyebrow = config.eyebrow

    const heading = config.heading

    const description = config.description

    const items = config.items

    const columns = config.columns

    const alignment = config.alignment

    const backgroundColor = config.background_color

    const textTheme = config.text_theme

    if (
        !isNullableFeatureStringWithin(eyebrow, 120) ||
        typeof heading !== 'string' ||
        heading.trim().length === 0 ||
        characterCount(heading.trim()) > 180 ||
        typeof description !== 'string' ||
        characterCount(description.trim()) > 1000
    ) {
        return false
    }

    if (!Array.isArray(items) || items.length < 1 || items.length > 12) {
        return false
    }

    if (!items.every((item) => isFeatureBenefitItemValid(item))) {
        return false
    }

    if (typeof columns !== 'number' || !Number.isInteger(columns) || ![2, 3, 4].includes(columns)) {
        return false
    }

    if (alignment !== 'left' && alignment !== 'center') {
        return false
    }

    if (typeof backgroundColor !== 'string' || !/^#[0-9a-fA-F]{6}$/.test(backgroundColor.trim())) {
        return false
    }

    return textTheme === 'light' || textTheme === 'dark'
}

function isFeatureBenefitItemValid(value: unknown): boolean {
    if (!isPlainObject(value)) {
        return false
    }

    const allowedKeys = new Set([
        'title',
        'description',
        'icon',
        'image',
        'image_alt',
        'link_label',
        'link_url',
    ])

    if (Object.keys(value).some((key) => !allowedKeys.has(key))) {
        return false
    }

    const title = value.title

    const description = value.description

    const icon = value.icon

    const image = value.image

    const imageAlt = value.image_alt

    const linkLabel = value.link_label

    const linkUrl = value.link_url

    if (
        typeof title !== 'string' ||
        title.trim().length === 0 ||
        characterCount(title.trim()) > 160
    ) {
        return false
    }

    if (typeof description !== 'string' || characterCount(description.trim()) > 1000) {
        return false
    }

    if (
        !isNullableFeatureStringWithin(icon, 80) ||
        !isNullableFeatureStringWithin(image, 2048) ||
        !isNullableFeatureStringWithin(imageAlt, 255) ||
        !isNullableFeatureStringWithin(linkLabel, 80) ||
        !isNullableFeatureStringWithin(linkUrl, 2048)
    ) {
        return false
    }

    const normalizedIcon = normalizeNullableFeatureString(icon)

    const normalizedImage = normalizeNullableFeatureString(image)

    const normalizedLinkLabel = normalizeNullableFeatureString(linkLabel)

    const normalizedLinkUrl = normalizeNullableFeatureString(linkUrl)

    if (normalizedIcon !== null && !isFeatureBenefitIconName(normalizedIcon)) {
        return false
    }

    if (normalizedImage !== null && getSafeFeatureImageUrl(normalizedImage) === null) {
        return false
    }

    if ((normalizedLinkLabel === null) !== (normalizedLinkUrl === null)) {
        return false
    }

    if (normalizedLinkUrl !== null && getSafeFeatureLinkUrl(normalizedLinkUrl) === null) {
        return false
    }

    return true
}

function isNullableFeatureStringWithin(value: unknown, maxLength: number): boolean {
    if (value === null || value === undefined) {
        return true
    }

    if (typeof value !== 'string') {
        return false
    }

    return characterCount(value.trim()) <= maxLength
}

function normalizeNullableFeatureString(value: unknown): string | null {
    if (typeof value !== 'string') {
        return null
    }

    const normalized = value.trim()

    return normalized === '' ? null : normalized
}

function isPlainObject(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
}

function isCallToActionConfigValid(config: SectionConfig): boolean {
    const allowedKeys = new Set([
        'eyebrow',
        'heading',
        'description',
        'background_type',
        'background_color',
        'background_image',
        'background_overlay',
        'text_theme',
        'phone_label',
        'phone_number',
        'email_label',
        'email',
        'whatsapp_label',
        'whatsapp_number',
        'whatsapp_message',
        'newsletter_placeholder',
        'newsletter_button_label',
        'newsletter_note',
    ])

    if (Object.keys(config).some((key) => !allowedKeys.has(key))) {
        return false
    }

    const eyebrow = config.eyebrow
    const heading = config.heading
    const description = config.description
    const backgroundType = config.background_type
    const backgroundColor = config.background_color
    const backgroundImage = config.background_image
    const backgroundOverlay = config.background_overlay
    const textTheme = config.text_theme
    const phoneLabel = config.phone_label
    const phoneNumber = config.phone_number
    const emailLabel = config.email_label
    const email = config.email
    const whatsappLabel = config.whatsapp_label
    const whatsappNumber = config.whatsapp_number
    const whatsappMessage = config.whatsapp_message
    const newsletterPlaceholder = config.newsletter_placeholder
    const newsletterButtonLabel = config.newsletter_button_label
    const newsletterNote = config.newsletter_note

    if (
        !isNullableStringWithin(eyebrow, 120) ||
        typeof heading !== 'string' ||
        heading.trim().length === 0 ||
        characterCount(heading.trim()) > 180 ||
        typeof description !== 'string' ||
        characterCount(description.trim()) > 1000
    ) {
        return false
    }

    if (backgroundType !== 'color' && backgroundType !== 'image') {
        return false
    }

    if (typeof backgroundColor !== 'string' || !/^#[0-9a-fA-F]{6}$/.test(backgroundColor.trim())) {
        return false
    }

    if (!isNullableStringWithin(backgroundImage, 2048)) {
        return false
    }

    if (
        backgroundType === 'image' &&
        (typeof backgroundImage !== 'string' || backgroundImage.trim().length === 0)
    ) {
        return false
    }

    if (
        typeof backgroundOverlay !== 'number' ||
        !Number.isInteger(backgroundOverlay) ||
        backgroundOverlay < 0 ||
        backgroundOverlay > 100
    ) {
        return false
    }

    if (textTheme !== 'light' && textTheme !== 'dark') {
        return false
    }

    if (
        !isNullableStringWithin(phoneLabel, 80) ||
        !isContactNumberValid(phoneNumber) ||
        !isNullableStringWithin(emailLabel, 80) ||
        !isEmailValueValid(email) ||
        !isNullableStringWithin(whatsappLabel, 80) ||
        !isContactNumberValid(whatsappNumber) ||
        !isNullableStringWithin(whatsappMessage, 500) ||
        !isNullableStringWithin(newsletterPlaceholder, 160) ||
        !isNullableStringWithin(newsletterButtonLabel, 80) ||
        !isNullableStringWithin(newsletterNote, 255)
    ) {
        return false
    }

    return true
}

function isNullableStringWithin(value: SectionConfig[string], maxLength: number): boolean {
    if (value === null || value === undefined) {
        return true
    }

    if (typeof value !== 'string') {
        return false
    }

    return characterCount(value.trim()) <= maxLength
}

function isContactNumberValid(value: SectionConfig[string]): boolean {
    if (value === null || value === undefined) {
        return true
    }

    if (typeof value !== 'string') {
        return false
    }

    const normalized = value.trim()

    if (normalized.length === 0) {
        return true
    }

    if (characterCount(normalized) > 80) {
        return false
    }

    if (!/^[0-9+()\-\s.]+$/.test(normalized)) {
        return false
    }

    const digits = normalized.replace(/\D+/g, '')

    return digits.length >= 6
}

function isEmailValueValid(value: SectionConfig[string]): boolean {
    if (value === null || value === undefined) {
        return true
    }

    if (typeof value !== 'string') {
        return false
    }

    const normalized = value.trim()

    if (normalized.length === 0) {
        return true
    }

    if (characterCount(normalized) > 254) {
        return false
    }

    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalized)
}

function isContentConfigValid(config: SectionConfig): boolean {
    const allowedKeys = new Set(['heading', 'body', 'image', 'image_alt', 'alignment'])

    if (Object.keys(config).some((key) => !allowedKeys.has(key))) {
        return false
    }

    const heading = config.heading
    const body = config.body
    const image = config.image
    const imageAlt = config.image_alt
    const alignment = config.alignment

    if (
        typeof heading !== 'string' ||
        characterCount(heading.trim()) > 160 ||
        typeof body !== 'string' ||
        body.trim().length === 0 ||
        characterCount(body.trim()) > 5000
    ) {
        return false
    }

    if (image !== null && typeof image !== 'string') {
        return false
    }

    if (
        imageAlt !== null &&
        (typeof imageAlt !== 'string' || characterCount(imageAlt.trim()) > 255)
    ) {
        return false
    }

    return alignment === 'left' || alignment === 'center' || alignment === 'right'
}

function isPromotionalBannerConfigValid(config: SectionConfig): boolean {
    const allowedKeys = new Set([
        'heading',
        'description',
        'image',
        'cta_label',
        'cta_url',
        'alignment',
    ])

    if (Object.keys(config).some((key) => !allowedKeys.has(key))) {
        return false
    }

    const heading = config.heading
    const description = config.description
    const image = config.image
    const ctaLabel = config.cta_label
    const ctaUrl = config.cta_url
    const alignment = config.alignment

    if (
        typeof heading !== 'string' ||
        heading.trim().length === 0 ||
        characterCount(heading.trim()) > 160 ||
        typeof description !== 'string' ||
        characterCount(description.trim()) > 500
    ) {
        return false
    }

    if (image !== null && typeof image !== 'string') {
        return false
    }

    if (ctaLabel !== null && typeof ctaLabel !== 'string') {
        return false
    }

    if (ctaUrl !== null && typeof ctaUrl !== 'string') {
        return false
    }

    const normalizedCtaLabel =
        typeof ctaLabel === 'string' && ctaLabel.trim().length > 0 ? ctaLabel.trim() : null

    const normalizedCtaUrl =
        typeof ctaUrl === 'string' && ctaUrl.trim().length > 0 ? ctaUrl.trim() : null

    if (
        (normalizedCtaLabel === null) !== (normalizedCtaUrl === null) ||
        (normalizedCtaLabel !== null && characterCount(normalizedCtaLabel) > 80) ||
        (normalizedCtaUrl !== null && characterCount(normalizedCtaUrl) > 2048)
    ) {
        return false
    }

    return alignment === 'left' || alignment === 'center' || alignment === 'right'
}

function characterCount(value: string): number {
    return Array.from(value).length
}

function isJsonObject(value: SectionConfig[string]): value is Record<string, JsonValue> {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
}
