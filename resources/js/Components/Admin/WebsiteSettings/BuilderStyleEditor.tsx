import { Palette, RotateCcw } from 'lucide-react'
import { useId } from 'react'
import HeaderEditorAccordion from '@/Components/Admin/HeaderBuilder/HeaderEditorAccordion'
import { readBuilderStyle } from '@/lib/builderStyle'
import type { BuilderSectionStyle, BuilderStyle, BuilderStyleSection } from '@/lib/builderStyle'

interface Section {
    key: BuilderStyleSection
    label: string
    description: string
}

const headerSections: Section[] = [
    { key: 'announcement', label: 'Announcement bar', description: 'Top notice and promotion text.' },
    { key: 'main', label: 'Main header', description: 'Brand text and header background.' },
    { key: 'navigation', label: 'Navigation', description: 'Navigation bar and open menu surfaces.' },
]

const footerSections: Section[] = [
    { key: 'top', label: 'Top section', description: 'Store benefits or promotion, when supported by the selected template.' },
    { key: 'main', label: 'Main footer', description: 'Brand, headings, descriptions and ordinary links.' },
    { key: 'bottom', label: 'Bottom bar', description: 'Copyright and developer credit.' },
]

function luminance(hex: string): number {
    const channels = [1, 3, 5].map((offset) => {
        const value = parseInt(hex.slice(offset, offset + 2), 16) / 255
        return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4
    })
    return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2]
}

function contrastRatio(text: string, background: string): number {
    const first = luminance(text)
    const second = luminance(background)
    return (Math.max(first, second) + 0.05) / (Math.min(first, second) + 0.05)
}

interface Props {
    kind: 'header' | 'footer'
    value: unknown
    disabled: boolean
    onChange: (value: BuilderStyle | undefined) => void
}

export default function BuilderStyleEditor({ kind, value, disabled, onChange }: Props) {
    const sections = kind === 'header' ? headerSections : footerSections
    const styles = readBuilderStyle(value, sections.map((section) => section.key))
    const id = useId()

    const update = (section: BuilderStyleSection, next: BuilderSectionStyle) => {
        if (disabled) return
        const result = readBuilderStyle({ ...styles, [section]: next }, sections.map((item) => item.key))
        onChange(Object.keys(result).length > 0 ? result : undefined)
    }

    return (
        <HeaderEditorAccordion
            title="Style / Advanced"
            description="Customize the storefront colors and typography. Changes appear in the live preview before saving."
            icon={<Palette className="h-5 w-5" aria-hidden="true" />}
        >
            <div className="space-y-5 p-5 sm:p-6">
                <p className="text-sm leading-6 text-neutral-600">
                    Default keeps the template styling. Buttons, badges, form controls and account/cart popups keep their own colors. Choose readable text and background contrast.
                </p>
                {sections.map((section) => {
                    const current = styles[section.key] ?? {}
                    const prefix = `${id}-${section.key}`
                    return (
                        <fieldset key={section.key} disabled={disabled} className="min-w-0 space-y-4 rounded-xl border border-neutral-200 p-4">
                            <legend className="px-1 text-sm font-semibold text-neutral-900">{section.label}</legend>
                            <p className="text-xs text-neutral-600">{section.description}</p>
                            <div className="grid min-w-0 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <ColorControl id={`${prefix}-text`} label="Text color" value={current.text_color} onChange={(text_color) => update(section.key, { ...current, text_color })} />
                                <ColorControl id={`${prefix}-background`} label="Background color" value={current.background_color} onChange={(background_color) => update(section.key, { ...current, background_color })} />
                                <div className="min-w-0 space-y-2">
                                    <label htmlFor={`${prefix}-size`} className="block text-sm font-medium text-neutral-700">Text size (px)</label>
                                    <select id={`${prefix}-size`} value={current.text_size ?? ''} onChange={(event) => update(section.key, { ...current, text_size: event.target.value === '' ? undefined : Number(event.target.value) })} className="min-h-10 w-full rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 disabled:opacity-50">
                                        <option value="">Template default</option>
                                        {Array.from({ length: 15 }, (_, index) => index + 10).map((size) => <option key={size} value={size}>{size} px</option>)}
                                    </select>
                                </div>
                            </div>
                            {current.text_color && current.background_color && contrastRatio(current.text_color, current.background_color) < 4.5 && (
                                <p role="status" className="rounded-md bg-amber-50 p-2 text-xs text-amber-800">Low contrast. Aim for at least 4.5:1 for normal text.</p>
                            )}
                            <button type="button" disabled={Object.keys(current).length === 0} onClick={() => update(section.key, {})} className="inline-flex items-center gap-2 rounded-md px-2 py-1 text-sm font-medium text-neutral-600 hover:text-neutral-950 disabled:opacity-40">
                                <RotateCcw className="h-4 w-4" aria-hidden="true" /> Reset section
                            </button>
                        </fieldset>
                    )
                })}
                <button type="button" disabled={disabled || Object.keys(styles).length === 0} onClick={() => onChange(undefined)} className="rounded-lg border border-neutral-300 px-4 py-2 text-sm font-semibold text-neutral-700 hover:bg-neutral-100 disabled:opacity-40">Reset all styles</button>
            </div>
        </HeaderEditorAccordion>
    )
}

function ColorControl({ id, label, value, onChange }: { id: string; label: string; value?: string; onChange: (value: string | undefined) => void }) {
    return (
        <div className="min-w-0 space-y-2">
            <label htmlFor={id} className="block text-sm font-medium text-neutral-700">{label}</label>
            <div className="flex min-w-0 flex-wrap items-center gap-2">
                <input id={id} type="color" value={value ?? '#808080'} onChange={(event) => onChange(event.target.value)} className="h-10 w-12 shrink-0 cursor-pointer rounded border border-neutral-300 bg-white p-1 disabled:opacity-50" />
                <span className="text-xs text-neutral-600">{value ?? 'Default'}</span>
                {value ? (
                    <button type="button" onClick={() => onChange(undefined)} className="text-xs font-medium text-indigo-700 underline">Reset</button>
                ) : (
                    <button type="button" onClick={() => onChange('#808080')} className="text-xs font-medium text-indigo-700 underline">Enable</button>
                )}
            </div>
        </div>
    )
}
