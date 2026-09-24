import { router } from '@inertiajs/react'
import { useState } from 'react'

import Dialog from '@/Components/Admin/Dialog'
import type { SectionDefinition } from '@/types/page-builder'

interface Props {
    open: boolean
    pageId: number
    sectionDefinitions: SectionDefinition[]
    onClose: () => void
}

export default function AddSectionDialog({ open, pageId, sectionDefinitions, onClose }: Props) {
    const [processingType, setProcessingType] = useState<string | null>(null)

    const addSection = (definition: SectionDefinition) => {
        if (processingType !== null) {
            return
        }

        setProcessingType(definition.type)

        router.post(
            route('admin.pages.sections.store', pageId),
            {
                type: definition.type,
                template: definition.default_template,
                config: definition.default_config,
                is_enabled: true,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    onClose()
                },
                onFinish: () => {
                    setProcessingType(null)
                },
            },
        )
    }

    const close = () => {
        if (processingType !== null) {
            return
        }

        onClose()
    }

    return (
        <Dialog
            open={open}
            title="Add Section"
            description="Choose a registered section type to add to this page."
            onClose={close}
        >
            {sectionDefinitions.length === 0 ? (
                <div className="rounded-lg border border-dashed border-neutral-300 px-5 py-8 text-center dark:border-neutral-700">
                    <p className="text-sm font-medium text-neutral-700 dark:text-neutral-200">
                        No sections are available.
                    </p>

                    <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        Register a Page Builder section before adding it to a page.
                    </p>
                </div>
            ) : (
                <div className="space-y-3">
                    {sectionDefinitions.map((definition) => {
                        const defaultTemplate = definition.templates.find(
                            (template) => template.key === definition.default_template,
                        )

                        const processing = processingType === definition.type
                        const disabled = processingType !== null

                        return (
                            <button
                                key={definition.type}
                                type="button"
                                disabled={disabled}
                                onClick={() => addSection(definition)}
                                className="flex w-full items-center justify-between gap-4 rounded-xl border border-neutral-200 p-4 text-left transition hover:border-neutral-400 hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-neutral-800 dark:hover:border-neutral-600 dark:hover:bg-neutral-900"
                            >
                                <div className="min-w-0">
                                    <p className="font-medium text-neutral-900 dark:text-neutral-100">
                                        {definition.label}
                                    </p>

                                    <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                        Default template:{' '}
                                        {defaultTemplate?.label ?? definition.default_template}
                                    </p>
                                </div>

                                <span className="shrink-0 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                    {processing ? 'Adding...' : 'Add'}
                                </span>
                            </button>
                        )
                    })}
                </div>
            )}

            <div className="mt-6 flex justify-end">
                <button
                    type="button"
                    onClick={close}
                    disabled={processingType !== null}
                    className="inline-flex items-center justify-center rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800"
                >
                    Cancel
                </button>
            </div>
        </Dialog>
    )
}
