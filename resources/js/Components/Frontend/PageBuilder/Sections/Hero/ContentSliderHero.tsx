import { Link } from '@inertiajs/react'

import HeroSliderControls from './HeroSliderControls'
import SlideTransition from '@/Components/Frontend/PageBuilder/Shared/SlideTransition'
import type { HeroAlignment, HeroButton, HeroConfig, HeroSlide } from './types'
import { useHeroSlider } from './useHeroSlider'
import { usePrefersReducedMotion } from './usePrefersReducedMotion'

import { handleHeroKeyboard } from './hero-keyboard'

interface Props {
    config: HeroConfig
}

const alignmentClasses: Record<
    HeroAlignment,
    {
        content: string
        description: string
        actions: string
    }
> = {
    left: {
        content: 'items-start text-left',
        description: '',
        actions: 'justify-start',
    },

    center: {
        content: 'items-center text-center',
        description: 'mx-auto',
        actions: 'justify-center',
    },

    right: {
        content: 'items-end text-right',
        description: 'ml-auto',
        actions: 'justify-end',
    },
}

export default function ContentSliderHero({ config }: Props) {
    const slideCount = config.slides.length

    const prefersReducedMotion = usePrefersReducedMotion()

    const slider = useHeroSlider({
        slideCount,
        autoplay: config.autoplay && !prefersReducedMotion,
        autoplayDelay: config.autoplay_delay,
    })

    if (slideCount === 0) {
        return null
    }

    const activeSlide = config.slides[Math.min(slider.activeIndex, slideCount - 1)]

    if (!activeSlide) {
        return null
    }

    return (
        <section
            role="region"
            aria-roledescription="carousel"
            aria-label="Featured content"
            data-storefront-section="hero"
            data-hero-template="content_slider"
            data-hero-effect={config.effect}
            className="relative isolate min-w-0 overflow-hidden rounded-2xl"
            onMouseEnter={slider.pause}
            onMouseLeave={slider.resume}
            onFocusCapture={slider.pause}
            onBlurCapture={(event) => {
                if (!event.currentTarget.contains(event.relatedTarget)) {
                    slider.resume()
                }
            }}

            onKeyDown={(event) => {
                handleHeroKeyboard({
                    event,
                    onPrevious: slider.previousSlide,
                    onNext: slider.nextSlide,
                })
            }}
        >
            <SlideTransition
                activeIndex={slider.activeIndex}
                direction={slider.direction}
                effect={config.effect}
            >
                <ContentSlide
                    slide={activeSlide}
                    activeIndex={slider.activeIndex}
                    slideCount={slideCount}
                />
            </SlideTransition>

            <HeroSliderControls
                slideCount={slideCount}
                activeIndex={slider.activeIndex}
                showArrows={config.show_arrows}
                showDots={config.show_dots}
                onPrevious={slider.previousSlide}
                onNext={slider.nextSlide}
                onSelect={slider.goToSlide}
            />
        </section>
    )
}

function ContentSlide({
    slide,
    activeIndex,
    slideCount,
}: {
    slide: HeroSlide
    activeIndex: number
    slideCount: number
}) {
    const alignment = alignmentClasses[slide.alignment]

    return (
        <div
            role="group"
            aria-roledescription="slide"
            aria-label={`${activeIndex + 1} of ${slideCount}`}
            className="relative isolate min-h-[420px] overflow-hidden sm:min-h-[500px] lg:min-h-[560px]"
            style={{
                backgroundColor: slide.background_color ?? '#171717',
            }}
        >
            <SlideBackground slide={slide} />

            <div className="w-full min-w-0 max-w-3xl">
                <div className="relative z-10 flex min-h-[420px] items-center px-6 py-16 sm:min-h-[500px] sm:px-10 sm:py-20 lg:min-h-[560px] lg:px-16">
                    <div className={`flex w-full flex-col ${alignment.content}`}>
                        <div className="w-full max-w-3xl">
                            {slide.top_title && (
                                <p className="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-white/80 sm:text-sm">
                                    {slide.top_title}
                                </p>
                            )}

                            {slide.title && (
                                <h2 className="break-words text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl xl:text-6xl">
                                    {slide.title}
                                </h2>
                            )}

                            {slide.description && (
                                <p
                                    className={`mt-5 max-w-2xl break-words text-base leading-7 text-white/85 sm:text-lg sm:leading-8 ${alignment.description}`}
                                >
                                    {slide.description}
                                </p>
                            )}

                            <HeroActions slide={slide} />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}

function SlideBackground({ slide }: { slide: HeroSlide }) {
    if (!slide.background_image) {
        return null
    }

    return (
        <>
            <img
                src={slide.background_image}
                alt=""
                aria-hidden="true"
                className="absolute inset-0 h-full w-full object-cover"
            />

            <div aria-hidden="true" className="absolute inset-0 bg-black/50" />
        </>
    )
}

function HeroActions({ slide }: { slide: HeroSlide }) {
    if (!slide.primary_button && !slide.secondary_button) {
        return null
    }

    const alignment = alignmentClasses[slide.alignment]

    return (
        <div className={`mt-8 flex flex-wrap gap-3 ${alignment.actions}`}>
            {slide.primary_button && <HeroLink button={slide.primary_button} variant="primary" />}

            {slide.secondary_button && (
                <HeroLink button={slide.secondary_button} variant="secondary" />
            )}
        </div>
    )
}

interface HeroLinkProps {
    button: HeroButton
    variant: 'primary' | 'secondary'
}

function HeroLink({ button, variant }: HeroLinkProps) {
    const className =
        variant === 'primary'
            ? 'inline-flex min-h-11 items-center justify-center rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-neutral-950 shadow-sm transition hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-950'
            : 'inline-flex min-h-11 items-center justify-center rounded-lg border border-white/60 bg-white/10 px-5 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white'

    if (isExternalUrl(button.url)) {
        return (
            <a href={button.url} className={className}>
                {button.label}
            </a>
        )
    }

    return (
        <Link href={button.url} className={className}>
            {button.label}
        </Link>
    )
}

function isExternalUrl(url: string): boolean {
    return url.startsWith('http://') || url.startsWith('https://') || url.startsWith('//')
}
