import type { HeroSlide } from './hero-editor-types'
import PageBuilderImageField from '../../Media/PageBuilderImageField'

interface Props {
    pageId: number
    slide: HeroSlide
    index: number
    onChange: (slide: HeroSlide) => void
}

export default function HeroImageSlideEditor({ pageId, slide, index, onChange }: Props) {
    const updateSlide = (patch: Partial<HeroSlide>) => {
        onChange({
            ...slide,
            ...patch,
        })
    }

    return (
        <div className="space-y-5 border-t pt-5">
            <div>
                <h4 className="text-sm font-semibold">Slide {index + 1} Image</h4>

                <p className="mt-1 text-xs text-muted-foreground">
                    Configure the image, alternative text, and optional destination URL for this
                    slide.
                </p>
            </div>

            <PageBuilderImageField
                pageId={pageId}
                label="Slide Image"
                value={slide.image ?? null}
                helpText="Upload the image displayed by this slide."
                onChange={(value) =>
                    updateSlide({
                        image: value,
                    })
                }
            />

            <div className="space-y-2">
                <label htmlFor={`hero-image-alt-${index}`} className="text-sm font-medium">
                    Alt Text
                </label>

                <input
                    id={`hero-image-alt-${index}`}
                    type="text"
                    value={slide.alt ?? ''}
                    onChange={(event) =>
                        updateSlide({
                            alt: event.target.value,
                        })
                    }
                    placeholder="Describe this image"
                    className="h-10 w-full rounded-md border bg-background px-3 text-sm"
                />

                <p className="text-xs text-muted-foreground">
                    Add meaningful alternative text for accessibility when appropriate.
                </p>
            </div>

            <div className="space-y-2">
                <label htmlFor={`hero-image-url-${index}`} className="text-sm font-medium">
                    Link URL
                    <span className="ml-1 font-normal text-muted-foreground">(optional)</span>
                </label>

                <input
                    id={`hero-image-url-${index}`}
                    type="text"
                    value={slide.url ?? ''}
                    onChange={(event) =>
                        updateSlide({
                            url: event.target.value,
                        })
                    }
                    placeholder="/products"
                    className="h-10 w-full rounded-md border bg-background px-3 text-sm"
                />

                <p className="text-xs text-muted-foreground">
                    When provided, the storefront can make the complete slide clickable.
                </p>
            </div>
        </div>
    )
}
