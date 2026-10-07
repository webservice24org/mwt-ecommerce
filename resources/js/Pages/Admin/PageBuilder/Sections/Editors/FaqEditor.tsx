import type { SectionConfig } from '@/types/page-builder'
import type { ReactNode } from 'react'
import FaqItemsEditor, { type FaqItem } from './FaqItemsEditor'

import type { SectionEditorProps } from '../types'

const MAX_EYEBROW_LENGTH = 120
const MAX_HEADING_LENGTH = 180
const MAX_DESCRIPTION_LENGTH = 1000

type FaqAlignment = 'left' | 'center'

type FaqTextTheme = 'light' | 'dark'

interface FaqConfig {
    eyebrow: string | null
    heading: string | null
    description: string

    items: FaqItem[]

    alignment: FaqAlignment
    background_color: string
    text_theme: FaqTextTheme

    open_first: boolean
    allow_multiple_open: boolean
}

export default function FaqEditor({ section, value, onChange }: SectionEditorProps) {
    const eyebrow = getNullableString(value.eyebrow)

    const heading = getNullableString(value.heading)

    const description = getString(value.description)

    const items = getFaqItems(value.items)

    const alignment = getAlignment(value.alignment)

    const backgroundColor = getBackgroundColor(value.background_color)

    const textTheme = getTextTheme(value.text_theme)

    const openFirst = getBoolean(value.open_first, true)

    const allowMultipleOpen = getBoolean(value.allow_multiple_open, false)

    const updateConfig = (overrides: Partial<FaqConfig>) => {
        const nextConfig: SectionConfig = {
            eyebrow: overrides.eyebrow !== undefined ? overrides.eyebrow : eyebrow,

            heading: overrides.heading !== undefined ? overrides.heading : heading,

            description: overrides.description ?? description,

            items: overrides.items ?? items,

            alignment: overrides.alignment ?? alignment,

            background_color: overrides.background_color ?? backgroundColor,

            text_theme: overrides.text_theme ?? textTheme,

            open_first: overrides.open_first ?? openFirst,

            allow_multiple_open: overrides.allow_multiple_open ?? allowMultipleOpen,
        }

        onChange(nextConfig)
    }

    const templateLabel = getTemplateLabel(section.template)

    return (
        <div className="space-y-6">
            <div className="rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/50">
                <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                    {templateLabel}
                </p>

                <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    Edit the FAQ section heading, appearance, interaction settings, questions, and
                    answers.
                </p>
            </div>

            <fieldset className="space-y-4">
                <legend className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                    Section Content
                </legend>

                <Field>
                    <FieldLabel htmlFor="faq-eyebrow">Eyebrow</FieldLabel>

                    <input
                        id="faq-eyebrow"
                        type="text"
                        value={eyebrow ?? ''}
                        maxLength={MAX_EYEBROW_LENGTH}
                        onChange={(event) =>
                            updateConfig({
                                eyebrow: nullableValue(event.target.value),
                            })
                        }
                        className={inputClass}
                        placeholder="Help Center"
                    />
                </Field>

                <Field>
                    <FieldLabel htmlFor="faq-heading">Heading</FieldLabel>

                    <input
                        id="faq-heading"
                        type="text"
                        value={heading ?? ''}
                        maxLength={MAX_HEADING_LENGTH}
                        onChange={(event) =>
                            updateConfig({
                                heading: nullableValue(event.target.value),
                            })
                        }
                        className={inputClass}
                        placeholder="Frequently Asked Questions"
                    />
                </Field>

                <Field>
                    <FieldLabel htmlFor="faq-description">Description</FieldLabel>

                    <textarea
                        id="faq-description"
                        value={description}
                        maxLength={MAX_DESCRIPTION_LENGTH}
                        rows={4}
                        onChange={(event) =>
                            updateConfig({
                                description: event.target.value,
                            })
                        }
                        className={textareaClass}
                        placeholder="Add a short introduction for this FAQ section."
                    />
                </Field>
            </fieldset>

            <fieldset className="space-y-4">
                <legend className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                    Appearance
                </legend>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Field>
                        <FieldLabel htmlFor="faq-alignment">Alignment</FieldLabel>

                        <select
                            id="faq-alignment"
                            value={alignment}
                            onChange={(event) =>
                                updateConfig({
                                    alignment: getAlignment(event.target.value),
                                })
                            }
                            className={inputClass}
                        >
                            <option value="left">Left</option>

                            <option value="center">Center</option>
                        </select>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="faq-text-theme">Text Theme</FieldLabel>

                        <select
                            id="faq-text-theme"
                            value={textTheme}
                            onChange={(event) =>
                                updateConfig({
                                    text_theme: getTextTheme(event.target.value),
                                })
                            }
                            className={inputClass}
                        >
                            <option value="dark">Dark</option>

                            <option value="light">Light</option>
                        </select>
                    </Field>
                </div>

                <Field>
                    <FieldLabel htmlFor="faq-background-color">Background Color</FieldLabel>

                    <div className="flex items-center gap-3">
                        <input
                            id="faq-background-color"
                            type="color"
                            value={backgroundColor}
                            onChange={(event) =>
                                updateConfig({
                                    background_color: event.target.value,
                                })
                            }
                            className="h-10 w-14 cursor-pointer rounded-md border border-neutral-300 bg-white p-1 dark:border-neutral-700 dark:bg-neutral-950"
                        />

                        <input
                            type="text"
                            value={backgroundColor}
                            maxLength={7}
                            onChange={(event) =>
                                updateConfig({
                                    background_color: normalizeHexColor(
                                        event.target.value,
                                        backgroundColor,
                                    ),
                                })
                            }
                            className={inputClass}
                            aria-label="FAQ background color hexadecimal value"
                        />
                    </div>
                </Field>
            </fieldset>

            <fieldset className="space-y-4">
                <legend className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                    Accordion Behavior
                </legend>

                <ToggleField
                    checked={openFirst}
                    onChange={(checked) =>
                        updateConfig({
                            open_first: checked,
                        })
                    }
                    label="Open first FAQ automatically"
                    description="The first FAQ item starts expanded when the section loads."
                />

                <ToggleField
                    checked={allowMultipleOpen}
                    onChange={(checked) =>
                        updateConfig({
                            allow_multiple_open: checked,
                        })
                    }
                    label="Allow multiple questions open"
                    description="Visitors can keep more than one FAQ answer expanded at the same time."
                />
            </fieldset>

            <FaqItemsEditor
                items={items}
                onChange={(nextItems) =>
                    updateConfig({
                        items: nextItems,
                    })
                }
            />
        </div>
    )
}

interface FieldProps {
    children: ReactNode
}

function Field({ children }: FieldProps) {
    return <div className="space-y-1.5">{children}</div>
}

interface FieldLabelProps {
    htmlFor: string
    children: ReactNode
}

function FieldLabel({ htmlFor, children }: FieldLabelProps) {
    return (
        <label
            htmlFor={htmlFor}
            className="block text-sm font-medium text-neutral-700 dark:text-neutral-300"
        >
            {children}
        </label>
    )
}

interface ToggleFieldProps {
    checked: boolean

    label: string
    description: string

    onChange: (checked: boolean) => void
}

function ToggleField({ checked, label, description, onChange }: ToggleFieldProps) {
    return (
        <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-950">
            <input
                type="checkbox"
                checked={checked}
                onChange={(event) => onChange(event.target.checked)}
                className="mt-1 h-4 w-4 rounded border-neutral-300 text-neutral-950 focus:ring-neutral-500 dark:border-neutral-700"
            />

            <span className="min-w-0">
                <span className="block text-sm font-medium text-neutral-900 dark:text-neutral-100">
                    {label}
                </span>

                <span className="mt-1 block text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    {description}
                </span>
            </span>
        </label>
    )
}

const inputClass =
    'w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800'

const textareaClass =
    'w-full resize-y rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm leading-6 text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800'

function getFaqItems(value: unknown): FaqItem[] {
    if (!Array.isArray(value)) {
        return []
    }

    const items: FaqItem[] = []

    for (const item of value) {
        if (!isRecord(item)) {
            continue
        }

        items.push({
            question: getString(item.question),

            answer: getString(item.answer),
        })
    }

    return items
}

function getString(value: unknown): string {
    return typeof value === 'string' ? value : ''
}

function getNullableString(value: unknown): string | null {
    if (typeof value !== 'string') {
        return null
    }

    const normalized = value.trim()

    return normalized === '' ? null : normalized
}

function nullableValue(value: string): string | null {
    return value.trim() === '' ? null : value
}

function getBoolean(value: unknown, fallback: boolean): boolean {
    return typeof value === 'boolean' ? value : fallback
}

function getAlignment(value: unknown): FaqAlignment {
    return value === 'center' ? 'center' : 'left'
}

function getTextTheme(value: unknown): FaqTextTheme {
    return value === 'light' ? 'light' : 'dark'
}

function getBackgroundColor(value: unknown): string {
    if (typeof value === 'string' && /^#[0-9a-fA-F]{6}$/.test(value)) {
        return value.toLowerCase()
    }

    return '#ffffff'
}

function normalizeHexColor(value: string, fallback: string): string {
    const normalized = value.trim()

    if (/^#[0-9a-fA-F]{6}$/.test(normalized)) {
        return normalized.toLowerCase()
    }

    return fallback
}

function getTemplateLabel(template: string): string {
    switch (template) {
        case 'accordion':
            return 'Classic Accordion'

        case 'two_column':
            return 'Two-Column FAQ'

        case 'side_panel':
            return 'Side Panel FAQ'

        default:
            return 'FAQ'
    }
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
}
