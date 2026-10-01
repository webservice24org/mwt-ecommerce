import HeroSlideManager from './HeroSlideManager'
import {
    createHeroSlideForTemplate,
    heroConfigToSectionConfig,
    normalizeHeroConfig,
    type HeroConfig,
    type HeroEffect,
} from './hero-editor-types'

import type { SectionEditorProps } from '../types'

const effects: Array<{
    value: HeroEffect
    label: string
    description: string
}> = [
    {
        value: 'slide_left',
        label: 'Slide Left',
        description: 'Slides move from right to left.',
    },
    {
        value: 'slide_right',
        label: 'Slide Right',
        description: 'Slides move from left to right.',
    },
    {
        value: 'slide_up',
        label: 'Slide Up',
        description: 'Slides move upward.',
    },
    {
        value: 'slide_down',
        label: 'Slide Down',
        description: 'Slides move downward.',
    },
    {
        value: 'fade',
        label: 'Fade',
        description: 'Slides softly fade between each other.',
    },
    {
        value: 'fade_scale',
        label: 'Fade + Scale',
        description: 'Slides fade while gently scaling.',
    },
    {
        value: 'zoom',
        label: 'Zoom',
        description: 'Slides transition with a zoom effect.',
    },
]

function getTemplateLabel(template: string): string {
    switch (template) {
        case 'content_slider':
            return 'Content Slider'

        case 'image_slider':
            return 'Image Slider'

        case 'static':
            return 'Static Hero'

        default:
            return template
    }
}

export default function HeroEditor({ pageId, section, value, onChange }: SectionEditorProps) {
    const config = normalizeHeroConfig(value)

    const templateLabel = getTemplateLabel(section.template)

    const isSlider = section.template === 'content_slider' || section.template === 'image_slider'

    const updateConfig = (patch: Partial<HeroConfig>) => {
        const nextConfig: HeroConfig = {
            autoplay: patch.autoplay ?? config.autoplay,

            autoplay_delay: patch.autoplay_delay ?? config.autoplay_delay,

            effect: patch.effect ?? config.effect,

            show_arrows: patch.show_arrows ?? config.show_arrows,

            show_dots: patch.show_dots ?? config.show_dots,

            slides: patch.slides ?? config.slides,
        }

        onChange(heroConfigToSectionConfig(nextConfig))
    }

    const addSlide = () => {
        if (!isSlider) {
            return
        }

        updateConfig({
            slides: [...config.slides, createHeroSlideForTemplate(section.template)],
        })
    }

    const duplicateSlide = (index: number) => {
        if (!isSlider) {
            return
        }

        const source = config.slides[index]

        if (!source) {
            return
        }

        const duplicate = structuredClone(source)

        const slides = [...config.slides]

        slides.splice(index + 1, 0, duplicate)

        updateConfig({
            slides,
        })
    }

    const deleteSlide = (index: number) => {
        if (!isSlider || config.slides.length <= 1) {
            return
        }

        updateConfig({
            slides: config.slides.filter((_, slideIndex) => slideIndex !== index),
        })
    }

    const moveSlideUp = (index: number) => {
        if (!isSlider || index <= 0) {
            return
        }

        const slides = [...config.slides]

        const current = slides[index]

        const previous = slides[index - 1]

        if (current === undefined || previous === undefined) {
            return
        }

        slides[index - 1] = current
        slides[index] = previous

        updateConfig({
            slides,
        })
    }

    const moveSlideDown = (index: number) => {
        if (!isSlider || index >= config.slides.length - 1) {
            return
        }

        const slides = [...config.slides]

        const current = slides[index]

        const next = slides[index + 1]

        if (current === undefined || next === undefined) {
            return
        }

        slides[index + 1] = current
        slides[index] = next

        updateConfig({
            slides,
        })
    }

    const updateSlide = (index: number, slide: HeroConfig['slides'][number]) => {
        const slides = [...config.slides]

        if (!slides[index]) {
            return
        }

        slides[index] = slide

        updateConfig({
            slides,
        })
    }

    const selectedEffect = effects.find((effect) => effect.value === config.effect) ?? effects[0]

    return (
        <div className="space-y-6">
            <div className="rounded-lg border bg-muted/30 p-4">
                <div className="space-y-1">
                    <h3 className="font-medium">Hero Editor</h3>

                    <p className="text-sm text-muted-foreground">
                        Editing the{' '}
                        <span className="font-medium text-foreground">{templateLabel}</span>{' '}
                        template.
                    </p>
                </div>
            </div>

            {isSlider ? (
                <div className="space-y-5">
                    <div>
                        <h3 className="text-sm font-semibold">Slider Settings</h3>

                        <p className="mt-1 text-sm text-muted-foreground">
                            Configure how this Hero slider behaves and transitions between slides.
                        </p>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <label className="flex items-start gap-3 rounded-lg border p-4">
                            <input
                                type="checkbox"
                                checked={config.autoplay}
                                onChange={(event) =>
                                    updateConfig({
                                        autoplay: event.target.checked,
                                    })
                                }
                                className="mt-1 h-4 w-4"
                            />

                            <span className="space-y-1">
                                <span className="block text-sm font-medium">Autoplay</span>

                                <span className="block text-xs text-muted-foreground">
                                    Automatically advance through Hero slides.
                                </span>
                            </span>
                        </label>

                        <label className="flex items-start gap-3 rounded-lg border p-4">
                            <input
                                type="checkbox"
                                checked={config.show_arrows}
                                onChange={(event) =>
                                    updateConfig({
                                        show_arrows: event.target.checked,
                                    })
                                }
                                className="mt-1 h-4 w-4"
                            />

                            <span className="space-y-1">
                                <span className="block text-sm font-medium">Navigation Arrows</span>

                                <span className="block text-xs text-muted-foreground">
                                    Show previous and next slide controls.
                                </span>
                            </span>
                        </label>

                        <label className="flex items-start gap-3 rounded-lg border p-4">
                            <input
                                type="checkbox"
                                checked={config.show_dots}
                                onChange={(event) =>
                                    updateConfig({
                                        show_dots: event.target.checked,
                                    })
                                }
                                className="mt-1 h-4 w-4"
                            />

                            <span className="space-y-1">
                                <span className="block text-sm font-medium">Pagination Dots</span>

                                <span className="block text-xs text-muted-foreground">
                                    Show slide position indicators.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div className="space-y-2">
                        <label htmlFor="hero-autoplay-delay" className="text-sm font-medium">
                            Autoplay Delay
                        </label>

                        <div className="flex items-center gap-3">
                            <input
                                id="hero-autoplay-delay"
                                type="number"
                                min={1000}
                                max={30000}
                                step={500}
                                disabled={!config.autoplay}
                                value={config.autoplay_delay}
                                onChange={(event) => {
                                    const delay = Number(event.target.value)

                                    if (!Number.isInteger(delay)) {
                                        return
                                    }

                                    updateConfig({
                                        autoplay_delay: delay,
                                    })
                                }}
                                className="h-10 w-full rounded-md border bg-background px-3 text-sm disabled:cursor-not-allowed disabled:opacity-50 sm:max-w-xs"
                            />

                            <span className="shrink-0 text-sm text-muted-foreground">
                                milliseconds
                            </span>
                        </div>

                        <p className="text-xs text-muted-foreground">
                            Allowed range: 1,000–30,000 milliseconds.
                        </p>
                    </div>

                    <div className="space-y-2">
                        <label htmlFor="hero-effect" className="text-sm font-medium">
                            Transition Effect
                        </label>

                        <select
                            id="hero-effect"
                            value={config.effect}
                            onChange={(event) =>
                                updateConfig({
                                    effect: event.target.value as HeroEffect,
                                })
                            }
                            className="h-10 w-full rounded-md border bg-background px-3 text-sm"
                        >
                            {effects.map((effect) => (
                                <option key={effect.value} value={effect.value}>
                                    {effect.label}
                                </option>
                            ))}
                        </select>

                        <p className="text-xs text-muted-foreground">
                            {selectedEffect.description}
                        </p>
                    </div>
                </div>
            ) : (
                <div className="rounded-lg border p-4">
                    <div className="space-y-1">
                        <h3 className="text-sm font-semibold">Static Hero</h3>

                        <p className="text-sm text-muted-foreground">
                            This template displays one non-sliding Hero, so autoplay, transition,
                            arrows, and pagination controls are not configurable.
                        </p>
                    </div>
                </div>
            )}

            <HeroSlideManager
                pageId={pageId}
                template={section.template}
                slides={config.slides}
                onAdd={addSlide}
                onDuplicate={duplicateSlide}
                onDelete={deleteSlide}
                onMoveUp={moveSlideUp}
                onMoveDown={moveSlideDown}
                onSlideChange={updateSlide}
            />
        </div>
    )
}
