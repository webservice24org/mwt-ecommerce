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
} from '@/types/page-builder'

import SectionEditorRenderer from '../../Sections/Editors/SectionEditorRenderer'
import { hasSectionEditor } from '../../Sections/section-editor-registry'

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
                            type={section.type}
                            pageId={pageId}
                            section={section}
                            definition={definition}
                            value={config}
                            catalogSources={catalogSources}
                            categoryOptions={categoryOptions}
                            selectedProductOptions={selectedProductOptions}
                            onChange={setConfig}
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

function formatIdentifier(value: string): string {
    return value
        .split(/[_-]/)
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ')
}

function isConfigValid(type: string, config: SectionConfig): boolean {
    if (type !== 'featured_products') {
        return true
    }

    const title = config.title
    const limit = config.limit
    const source = isJsonObject(config.source)
        ? config.source
        : {
              type: 'featured',
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

    if (!isJsonObject(source)) {
        return false
    }

    const sourceType = source.type

    if (sourceType === 'featured') {
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

function isJsonObject(value: SectionConfig[string]): value is Record<string, JsonValue> {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
}
