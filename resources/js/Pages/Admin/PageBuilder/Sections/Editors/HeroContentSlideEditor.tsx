import type { HeroAlignment, HeroButton, HeroSlide } from './hero-editor-types'
import PageBuilderImageField from '../../Media/PageBuilderImageField'

interface Props {
    pageId: number
    slide: HeroSlide
    index: number
    isStatic: boolean
    onChange: (slide: HeroSlide) => void
}

const alignments: Array<{
    value: HeroAlignment
    label: string
}> = [
    {
        value: 'left',
        label: 'Left',
    },
    {
        value: 'center',
        label: 'Center',
    },
    {
        value: 'right',
        label: 'Right',
    },
]

function createEmptyButton(): HeroButton {
    return {
        label: '',
        url: '',
    }
}

export default function HeroContentSlideEditor({
    pageId,
    slide,
    index,
    isStatic,
    onChange,
}: Props) {
    const updateSlide = (patch: Partial<HeroSlide>) => {
        onChange({
            ...slide,
            ...patch,
        })
    }

    const updateButton = (
        key: 'primary_button' | 'secondary_button',
        patch: Partial<HeroButton>,
    ) => {
        const current = slide[key] ?? createEmptyButton()

        updateSlide({
            [key]: {
                ...current,
                ...patch,
            },
        })
    }

    const toggleButton = (key: 'primary_button' | 'secondary_button', enabled: boolean) => {
        updateSlide({
            [key]: enabled ? createEmptyButton() : null,
        })
    }

    return (
        <div className="space-y-6 border-t pt-5">
            <div>
                <h4 className="text-sm font-semibold">
                    {isStatic ? 'Hero Content' : `Slide ${index + 1} Content`}
                </h4>

                <p className="mt-1 text-xs text-muted-foreground">
                    Configure the background, text, alignment, and optional action buttons.
                </p>
            </div>

            <div className="space-y-4">
                <div>
                    <h5 className="text-sm font-medium">Background</h5>

                    <p className="mt-1 text-xs text-muted-foreground">
                        Use a background color, a background image, or both.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                        <label
                            htmlFor={`hero-background-color-${index}`}
                            className="text-sm font-medium"
                        >
                            Background Color
                        </label>

                        <div className="flex gap-2">
                            <input
                                id={`hero-background-color-picker-${index}`}
                                type="color"
                                value={slide.background_color ?? '#111827'}
                                onChange={(event) =>
                                    updateSlide({
                                        background_color: event.target.value,
                                    })
                                }
                                className="h-10 w-12 cursor-pointer rounded-md border bg-background p-1"
                                aria-label="Choose background color"
                            />

                            <input
                                id={`hero-background-color-${index}`}
                                type="text"
                                value={slide.background_color ?? ''}
                                onChange={(event) =>
                                    updateSlide({
                                        background_color: event.target.value,
                                    })
                                }
                                placeholder="#111827"
                                className="h-10 min-w-0 flex-1 rounded-md border bg-background px-3 text-sm"
                            />
                        </div>
                    </div>

                    <PageBuilderImageField
                        pageId={pageId}
                        label="Background Image"
                        value={slide.background_image ?? null}
                        helpText="Upload an optional background image for this Hero."
                        onChange={(value) =>
                            updateSlide({
                                background_image: value,
                            })
                        }
                    />
                </div>
            </div>

            <div className="space-y-4">
                <h5 className="text-sm font-medium">Content</h5>

                <div className="space-y-2">
                    <label htmlFor={`hero-top-title-${index}`} className="text-sm font-medium">
                        Top Title
                    </label>

                    <input
                        id={`hero-top-title-${index}`}
                        type="text"
                        value={slide.top_title ?? ''}
                        onChange={(event) =>
                            updateSlide({
                                top_title: event.target.value,
                            })
                        }
                        placeholder="Optional top title"
                        className="h-10 w-full rounded-md border bg-background px-3 text-sm"
                    />
                </div>

                <div className="space-y-2">
                    <label htmlFor={`hero-title-${index}`} className="text-sm font-medium">
                        Title
                    </label>

                    <input
                        id={`hero-title-${index}`}
                        type="text"
                        value={slide.title ?? ''}
                        onChange={(event) =>
                            updateSlide({
                                title: event.target.value,
                            })
                        }
                        placeholder="Hero title"
                        className="h-10 w-full rounded-md border bg-background px-3 text-sm"
                    />
                </div>

                <div className="space-y-2">
                    <label htmlFor={`hero-description-${index}`} className="text-sm font-medium">
                        Short Text
                    </label>

                    <textarea
                        id={`hero-description-${index}`}
                        value={slide.description ?? ''}
                        onChange={(event) =>
                            updateSlide({
                                description: event.target.value,
                            })
                        }
                        rows={4}
                        placeholder="Short supporting text..."
                        className="w-full resize-y rounded-md border bg-background px-3 py-2 text-sm"
                    />
                </div>

                <div className="space-y-2">
                    <label htmlFor={`hero-alignment-${index}`} className="text-sm font-medium">
                        Text Alignment
                    </label>

                    <select
                        id={`hero-alignment-${index}`}
                        value={slide.alignment}
                        onChange={(event) =>
                            updateSlide({
                                alignment: event.target.value as HeroAlignment,
                            })
                        }
                        className="h-10 w-full rounded-md border bg-background px-3 text-sm sm:max-w-xs"
                    >
                        {alignments.map((alignment) => (
                            <option key={alignment.value} value={alignment.value}>
                                {alignment.label}
                            </option>
                        ))}
                    </select>
                </div>
            </div>

            <ButtonEditor
                title="Primary Button"
                button={slide.primary_button}
                index={index}
                prefix="primary"
                onToggle={(enabled) => toggleButton('primary_button', enabled)}
                onChange={(patch) => updateButton('primary_button', patch)}
            />

            <ButtonEditor
                title="Secondary Button"
                button={slide.secondary_button}
                index={index}
                prefix="secondary"
                onToggle={(enabled) => toggleButton('secondary_button', enabled)}
                onChange={(patch) => updateButton('secondary_button', patch)}
            />
        </div>
    )
}

interface ButtonEditorProps {
    title: string
    button: HeroButton | null
    index: number
    prefix: string
    onToggle: (enabled: boolean) => void
    onChange: (patch: Partial<HeroButton>) => void
}

function ButtonEditor({ title, button, index, prefix, onToggle, onChange }: ButtonEditorProps) {
    const enabled = button !== null

    return (
        <div className="space-y-4 rounded-lg border p-4">
            <label className="flex items-start gap-3">
                <input
                    type="checkbox"
                    checked={enabled}
                    onChange={(event) => onToggle(event.target.checked)}
                    className="mt-1 h-4 w-4"
                />

                <span>
                    <span className="block text-sm font-medium">{title}</span>

                    <span className="mt-1 block text-xs text-muted-foreground">
                        Display this button when both button text and URL are provided.
                    </span>
                </span>
            </label>

            {enabled && button && (
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                        <label
                            htmlFor={`hero-${prefix}-button-label-${index}`}
                            className="text-sm font-medium"
                        >
                            Button Text
                        </label>

                        <input
                            id={`hero-${prefix}-button-label-${index}`}
                            type="text"
                            value={button.label}
                            onChange={(event) =>
                                onChange({
                                    label: event.target.value,
                                })
                            }
                            placeholder="Shop Now"
                            className="h-10 w-full rounded-md border bg-background px-3 text-sm"
                        />
                    </div>

                    <div className="space-y-2">
                        <label
                            htmlFor={`hero-${prefix}-button-url-${index}`}
                            className="text-sm font-medium"
                        >
                            Button URL
                        </label>

                        <input
                            id={`hero-${prefix}-button-url-${index}`}
                            type="text"
                            value={button.url}
                            onChange={(event) =>
                                onChange({
                                    url: event.target.value,
                                })
                            }
                            placeholder="/products"
                            className="h-10 w-full rounded-md border bg-background px-3 text-sm"
                        />
                    </div>
                </div>
            )}
        </div>
    )
}
