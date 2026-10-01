interface Props {
    slideCount: number
    activeIndex: number

    showArrows: boolean
    showDots: boolean

    onPrevious: () => void
    onNext: () => void

    onSelect: (index: number) => void
}

export default function HeroSliderControls({
    slideCount,
    activeIndex,
    showArrows,
    showDots,
    onPrevious,
    onNext,
    onSelect,
}: Props) {
    if (slideCount <= 1) {
        return null
    }

    return (
        <>
            {showArrows && (
                <>
                    <button
                        type="button"
                        aria-label="Previous slide"
                        onClick={onPrevious}
                        className="absolute left-2 top-1/2 z-30 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/30 bg-black/25 text-white backdrop-blur-sm transition hover:bg-black/45 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white sm:left-5"
                    >
                        <ChevronLeft />
                    </button>

                    <button
                        type="button"
                        aria-label="Next slide"
                        onClick={onNext}
                        className="absolute right-2 top-1/2 z-30 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/30 bg-black/25 text-white backdrop-blur-sm transition hover:bg-black/45 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white sm:right-5"
                    >
                        <ChevronRight />
                    </button>
                </>
            )}

            {showDots && (
                <div
                    className="absolute bottom-5 left-1/2 z-30 flex -translate-x-1/2 items-center gap-2"
                    role="group"
                    aria-label="Choose slide"
                >
                    {Array.from({
                        length: slideCount,
                    }).map((_, index) => {
                        const active = index === activeIndex

                        return (
                            <button
                                key={index}
                                type="button"
                                aria-label={`Go to slide ${index + 1}`}
                                aria-current={active ? 'true' : undefined}
                                onClick={() => onSelect(index)}
                                className={`h-2.5 rounded-full transition-all ${
                                    active ? 'w-7 bg-white' : 'w-2.5 bg-white/50 hover:bg-white/75'
                                }`}
                            />
                        )
                    })}
                </div>
            )}
        </>
    )
}

function ChevronLeft() {
    return (
        <svg
            viewBox="0 0 24 24"
            aria-hidden="true"
            className="h-5 w-5"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            <path d="m15 18-6-6 6-6" />
        </svg>
    )
}

function ChevronRight() {
    return (
        <svg
            viewBox="0 0 24 24"
            aria-hidden="true"
            className="h-5 w-5"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            <path d="m9 18 6-6-6-6" />
        </svg>
    )
}
