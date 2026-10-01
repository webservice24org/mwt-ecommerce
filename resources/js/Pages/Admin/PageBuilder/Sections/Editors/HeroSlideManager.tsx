import type { HeroSlide } from './hero-editor-types'
import HeroContentSlideEditor from './HeroContentSlideEditor'
import HeroImageSlideEditor from './HeroImageSlideEditor'

interface Props {
    pageId: number
    template: string
    slides: HeroSlide[]

    onAdd: () => void

    onDuplicate: (index: number) => void

    onDelete: (index: number) => void

    onMoveUp: (index: number) => void

    onMoveDown: (index: number) => void

    onSlideChange: (index: number, slide: HeroSlide) => void
}

function getSlideTitle(slide: HeroSlide, index: number, template: string): string {
    if (template !== 'image_slider' && slide.title?.trim()) {
        return slide.title
    }

    if (template === 'image_slider' && slide.alt?.trim()) {
        return slide.alt
    }

    return `Slide ${index + 1}`
}

export default function HeroSlideManager({
    pageId,
    template,
    slides,
    onAdd,
    onDuplicate,
    onDelete,
    onMoveUp,
    onMoveDown,
    onSlideChange,
}: Props) {
    const isStatic = template === 'static'

    return (
        <div className="space-y-4">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 className="text-sm font-semibold">
                        {isStatic ? 'Hero Content' : 'Slides'}
                    </h3>

                    <p className="mt-1 text-sm text-muted-foreground">
                        {isStatic
                            ? 'Static Hero uses one content item.'
                            : `${slides.length} ${
                                  slides.length === 1 ? 'slide' : 'slides'
                              } configured.`}
                    </p>
                </div>

                {!isStatic && (
                    <button
                        type="button"
                        onClick={onAdd}
                        className="inline-flex h-9 items-center justify-center rounded-md border bg-background px-3 text-sm font-medium shadow-sm hover:bg-muted"
                    >
                        Add Slide
                    </button>
                )}
            </div>

            <div className="space-y-3">
                {slides.map((slide, index) => (
                    <div key={index} className="rounded-lg border bg-background p-4">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="min-w-0">
                                <div className="flex items-center gap-2">
                                    <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-muted text-xs font-semibold">
                                        {index + 1}
                                    </span>

                                    <h4 className="truncate text-sm font-medium">
                                        {getSlideTitle(slide, index, template)}
                                    </h4>
                                </div>

                                <p className="mt-2 text-xs text-muted-foreground">
                                    {template === 'image_slider'
                                        ? 'Image slide'
                                        : isStatic
                                          ? 'Static Hero content'
                                          : 'Content slide'}
                                </p>
                            </div>

                            {!isStatic && (
                                <div className="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        disabled={index === 0}
                                        onClick={() => onMoveUp(index)}
                                        className="h-8 rounded-md border px-2.5 text-xs font-medium disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        Up
                                    </button>

                                    <button
                                        type="button"
                                        disabled={index === slides.length - 1}
                                        onClick={() => onMoveDown(index)}
                                        className="h-8 rounded-md border px-2.5 text-xs font-medium disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        Down
                                    </button>

                                    <button
                                        type="button"
                                        onClick={() => onDuplicate(index)}
                                        className="h-8 rounded-md border px-2.5 text-xs font-medium"
                                    >
                                        Duplicate
                                    </button>

                                    <button
                                        type="button"
                                        disabled={slides.length <= 1}
                                        onClick={() => onDelete(index)}
                                        className="h-8 rounded-md border px-2.5 text-xs font-medium disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        Delete
                                    </button>
                                </div>
                            )}
                        </div>

                        {template === 'image_slider' ? (
                            <HeroImageSlideEditor
                                pageId={pageId}
                                slide={slide}
                                index={index}
                                onChange={(nextSlide) => onSlideChange(index, nextSlide)}
                            />
                        ) : (
                            <HeroContentSlideEditor
                                pageId={pageId}
                                slide={slide}
                                index={index}
                                isStatic={isStatic}
                                onChange={(nextSlide) => onSlideChange(index, nextSlide)}
                            />
                        )}
                    </div>
                ))}
            </div>
        </div>
    )
}
