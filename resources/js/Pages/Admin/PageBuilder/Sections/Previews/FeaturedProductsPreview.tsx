import type { PageSection } from '@/types/page-builder'

interface Props {
    section: PageSection
    sectionLabel: string
    templateLabel: string
}

const MAX_PREVIEW_PRODUCTS = 4

export default function FeaturedProductsPreview({ section, sectionLabel, templateLabel }: Props) {
    const title =
        typeof section.config.title === 'string' && section.config.title.trim() !== ''
            ? section.config.title
            : 'Featured Products'

    const configuredLimit =
        typeof section.config.limit === 'number' && Number.isInteger(section.config.limit)
            ? section.config.limit
            : 8

    const previewCount = Math.max(1, Math.min(configuredLimit, MAX_PREVIEW_PRODUCTS))

    return (
        <div className="overflow-hidden rounded-b-xl bg-neutral-50 p-5 sm:p-6 dark:bg-neutral-900/40">
            <div className="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-950">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0">
                        <p className="text-xs font-medium uppercase tracking-wide text-neutral-400">
                            Storefront preview
                        </p>

                        <h3 className="mt-2 text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                            {title}
                        </h3>
                    </div>

                    <span className="rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-medium text-neutral-500 dark:bg-neutral-900 dark:text-neutral-400">
                        Limit: {configuredLimit}
                    </span>
                </div>

                <div className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    {Array.from(
                        {
                            length: previewCount,
                        },
                        (_, index) => (
                            <PreviewProductCard key={index} />
                        ),
                    )}
                </div>

                {configuredLimit > MAX_PREVIEW_PRODUCTS && (
                    <p className="mt-3 text-xs text-neutral-400">
                        Previewing {MAX_PREVIEW_PRODUCTS} of {configuredLimit} configured product
                        slots.
                    </p>
                )}

                <div className="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-neutral-100 pt-4 text-xs text-neutral-400 dark:border-neutral-900">
                    <span>
                        {sectionLabel} · {templateLabel}
                    </span>

                    <span>Section #{section.id}</span>
                </div>
            </div>
        </div>
    )
}

function PreviewProductCard() {
    return (
        <div className="rounded-lg border border-neutral-200 bg-white p-2 dark:border-neutral-800 dark:bg-neutral-950">
            <div className="aspect-[4/3] rounded-md bg-neutral-100 dark:bg-neutral-900" />

            <div className="mt-3 h-2.5 w-3/4 rounded-full bg-neutral-200 dark:bg-neutral-800" />

            <div className="mt-2 h-2 w-1/2 rounded-full bg-neutral-100 dark:bg-neutral-900" />

            <div className="mt-3 h-3 w-16 rounded bg-neutral-200 dark:bg-neutral-800" />
        </div>
    )
}
