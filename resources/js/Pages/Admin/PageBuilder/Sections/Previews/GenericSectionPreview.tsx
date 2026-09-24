import type { PageSection } from '@/types/page-builder'

interface Props {
    section: PageSection
    sectionLabel: string
    templateLabel: string
}

export default function GenericSectionPreview({ section, sectionLabel, templateLabel }: Props) {
    return (
        <div className="overflow-hidden rounded-b-xl bg-neutral-50 p-5 sm:p-6 dark:bg-neutral-900/40">
            <div className="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-950">
                <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div className="max-w-md">
                        <div className="h-2 w-20 rounded-full bg-neutral-200 dark:bg-neutral-800" />

                        <div className="mt-4 h-5 w-48 max-w-full rounded bg-neutral-300 dark:bg-neutral-700" />

                        <div className="mt-3 h-2 w-full max-w-sm rounded-full bg-neutral-200 dark:bg-neutral-800" />

                        <div className="mt-2 h-2 w-3/4 max-w-xs rounded-full bg-neutral-200 dark:bg-neutral-800" />
                    </div>

                    <div className="grid w-full gap-3 sm:grid-cols-3 lg:max-w-md">
                        <PreviewCard />
                        <PreviewCard />
                        <PreviewCard />
                    </div>
                </div>

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

function PreviewCard() {
    return (
        <div className="rounded-lg border border-neutral-200 p-2 dark:border-neutral-800">
            <div className="aspect-[4/3] rounded-md bg-neutral-100 dark:bg-neutral-900" />

            <div className="mt-2 h-2 w-3/4 rounded-full bg-neutral-200 dark:bg-neutral-800" />

            <div className="mt-1.5 h-2 w-1/2 rounded-full bg-neutral-100 dark:bg-neutral-900" />
        </div>
    )
}
