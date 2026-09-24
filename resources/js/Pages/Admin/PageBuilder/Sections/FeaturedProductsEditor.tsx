import type { SectionEditorProps } from './types'

export default function FeaturedProductsEditor({ definition }: SectionEditorProps) {
    return (
        <div className="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/50">
            <p className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                {definition.label} configuration
            </p>

            <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                Configuration fields will be connected in the next Page Builder substeps.
            </p>
        </div>
    )
}
