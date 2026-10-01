import type { ReactNode } from 'react'
import { useEffect, useId, useRef } from 'react'

interface Props {
    open: boolean
    title: string
    description?: string
    children: ReactNode
    maxWidthClass?: string
    onClose: () => void
}

export default function Dialog({
    open,
    title,
    description,
    children,
    maxWidthClass = 'max-w-lg',
    onClose,
}: Props) {
    const titleId = useId()
    const descriptionId = useId()

    const dialogRef = useRef<HTMLDivElement>(null)
    const onCloseRef = useRef(onClose)

    /*
     * Keep the latest onClose callback available without making the
     * focus-management effect depend on the callback identity.
     */
    useEffect(() => {
        onCloseRef.current = onClose
    }, [onClose])

    /*
     * Dialog lifecycle.
     *
     * This effect intentionally depends only on `open`.
     *
     * Previously, `onClose` was also a dependency. If a parent supplied
     * a new callback during an editor update, this effect ran again and
     * focused the dialog container, stealing focus from the active input.
     */
    useEffect(() => {
        if (!open) {
            return
        }

        const previousActiveElement =
            document.activeElement instanceof HTMLElement ? document.activeElement : null

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onCloseRef.current()
            }
        }

        document.addEventListener('keydown', handleKeyDown)

        const animationFrame = window.requestAnimationFrame(() => {
            dialogRef.current?.focus()
        })

        return () => {
            window.cancelAnimationFrame(animationFrame)

            document.removeEventListener('keydown', handleKeyDown)

            previousActiveElement?.focus()
        }
    }, [open])

    if (!open) {
        return null
    }

    return (
        <div className="fixed inset-0 z-[100] flex items-center justify-center px-4 py-6">
            <button
                type="button"
                aria-label="Close dialog"
                onClick={onClose}
                className="absolute inset-0 bg-black/40"
            />

            <div
                ref={dialogRef}
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
                aria-describedby={description ? descriptionId : undefined}
                tabIndex={-1}
                className={`relative z-10 max-h-[90vh] w-full overflow-y-auto rounded-xl border border-neutral-200 bg-white p-6 shadow-xl outline-none dark:border-neutral-800 dark:bg-neutral-950 ${maxWidthClass}`}
            >
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h2
                            id={titleId}
                            className="text-lg font-semibold text-neutral-900 dark:text-neutral-100"
                        >
                            {title}
                        </h2>

                        {description && (
                            <p
                                id={descriptionId}
                                className="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400"
                            >
                                {description}
                            </p>
                        )}
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Close dialog"
                        className="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-lg text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900 dark:hover:bg-neutral-800 dark:hover:text-neutral-100"
                    >
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div className="mt-6">{children}</div>
            </div>
        </div>
    )
}
