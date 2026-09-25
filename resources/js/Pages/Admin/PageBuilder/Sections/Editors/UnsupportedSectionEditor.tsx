import type { SectionEditorProps } from '../types'

export default function UnsupportedSectionEditor({ section, definition }: SectionEditorProps) {
    return (
        <div
            role="status"
            className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200"
        >
            <p className="font-semibold">Editor unavailable</p>

            <p className="mt-1 leading-6">
                The configuration editor for <span className="font-medium">{definition.label}</span>{' '}
                is not available yet.
            </p>

            <p className="mt-2 text-xs opacity-75">
                Section type: <code>{section.type}</code>
            </p>
        </div>
    )
}
