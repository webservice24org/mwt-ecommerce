import { ChevronLeft, ChevronRight } from 'lucide-react'

interface Props {
    pageCount: number
    activePage: number
    showArrows: boolean
    showDots: boolean
    controlsId: string
    onPrevious: () => void
    onNext: () => void
    onSelect: (index: number) => void
}

export default function CategoryCarouselControls({
    pageCount,
    activePage,
    showArrows,
    showDots,
    controlsId,
    onPrevious,
    onNext,
    onSelect,
}: Props) {
    if (pageCount <= 1) {
        return null
    }

    return (
        <>
            {showArrows && (
                <>
                    <button
                        type="button"
                        aria-label="Previous category page"
                        aria-controls={controlsId}
                        onClick={onPrevious}
                        className="absolute left-2 top-1/2 z-20 flex size-11 -translate-y-1/2 items-center justify-center rounded-full border border-neutral-200 bg-white/95 text-neutral-900 shadow-sm backdrop-blur-sm transition hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 sm:left-3"
                    >
                        <ChevronLeft className="size-5" aria-hidden="true" />
                    </button>

                    <button
                        type="button"
                        aria-label="Next category page"
                        aria-controls={controlsId}
                        onClick={onNext}
                        className="absolute right-2 top-1/2 z-20 flex size-11 -translate-y-1/2 items-center justify-center rounded-full border border-neutral-200 bg-white/95 text-neutral-900 shadow-sm backdrop-blur-sm transition hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 sm:right-3"
                    >
                        <ChevronRight className="size-5" aria-hidden="true" />
                    </button>
                </>
            )}

            {showDots && (
                <div
                    role="group"
                    aria-label="Choose category page"
                    className="mt-6 flex flex-wrap items-center justify-center gap-2"
                >
                    {Array.from({
                        length: pageCount,
                    }).map((_, index) => {
                        const active = index === activePage

                        return (
                            <button
                                key={index}
                                type="button"
                                aria-label={`Go to category page ${index + 1}`}
                                aria-controls={controlsId}
                                aria-current={active ? 'true' : undefined}
                                onClick={() => onSelect(index)}
                                className={[
                                    'h-2.5 rounded-full transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2',
                                    active
                                        ? 'w-7 bg-neutral-900'
                                        : 'w-2.5 bg-neutral-300 hover:bg-neutral-500',
                                ].join(' ')}
                            />
                        )
                    })}
                </div>
            )}
        </>
    )
}
