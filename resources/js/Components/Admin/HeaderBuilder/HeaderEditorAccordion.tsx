import { ChevronDown, ChevronRight } from 'lucide-react'
import { useId, useState } from 'react'

interface Props {
    title: string
    description: string
    icon: React.ReactNode
    children: React.ReactNode
    defaultOpen?: boolean
    badge?: React.ReactNode
}

export default function HeaderEditorAccordion({
    title,
    description,
    icon,
    children,
    defaultOpen = false,
    badge,
}: Props) {
    const [open, setOpen] = useState(defaultOpen)

    const contentId = useId()

    return (
        <section className="min-w-0 overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
            <button
                type="button"
                aria-expanded={open}
                aria-controls={contentId}
                onClick={() => setOpen((current) => !current)}
                className="flex w-full min-w-0 items-start justify-between gap-4 p-5 text-left transition hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-neutral-950 sm:p-6"
            >
                <div className="flex min-w-0 items-start gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700">
                        {icon}
                    </div>

                    <div className="min-w-0">
                        <div className="flex min-w-0 flex-wrap items-center gap-2">
                            <h2 className="text-sm font-semibold text-neutral-950">{title}</h2>

                            {badge}
                        </div>

                        <p className="mt-1 max-w-3xl text-sm leading-6 text-neutral-500">
                            {description}
                        </p>
                    </div>
                </div>

                <span className="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-white text-neutral-500">
                    {open ? (
                        <ChevronDown className="h-4 w-4" strokeWidth={1.9} aria-hidden="true" />
                    ) : (
                        <ChevronRight className="h-4 w-4" strokeWidth={1.9} aria-hidden="true" />
                    )}
                </span>
            </button>

            {open && (
                <div id={contentId} className="border-t border-neutral-100">
                    {children}
                </div>
            )}
        </section>
    )
}
