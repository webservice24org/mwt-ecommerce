import type { ReactNode } from 'react'

import HeroSliderControls from './HeroSliderControls'
import HeroSlideTransition from './HeroSlideTransition'
import type { HeroConfig, HeroSlide } from './types'
import { useHeroSlider } from './useHeroSlider'

import { usePrefersReducedMotion } from './usePrefersReducedMotion'
import { handleHeroKeyboard } from './hero-keyboard'

interface Props {
    config: HeroConfig
}

type ImageHeroSlide = HeroSlide & {
    image: string
}

export default function ImageSliderHero({ config }: Props) {
    const slides = config.slides.filter(
        (slide): slide is ImageHeroSlide =>
            typeof slide.image === 'string' && slide.image.trim() !== '',
    )

    const prefersReducedMotion = usePrefersReducedMotion()

    const slideCount = slides.length

    const slider = useHeroSlider({
        slideCount,
        autoplay: config.autoplay && !prefersReducedMotion,
        autoplayDelay: config.autoplay_delay,
    })

    if (slideCount === 0) {
        return null
    }

    const activeSlide = slides[Math.min(slider.activeIndex, slideCount - 1)]

    if (!activeSlide) {
        return null
    }

    return (
        <section
            data-storefront-section="hero"
            data-hero-template="image_slider"
            data-hero-effect={config.effect}
            className="relative isolate min-w-0 overflow-hidden bg-neutral-950"
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
            <HeroSlideTransition
                activeIndex={slider.activeIndex}
                direction={slider.direction}
                effect={config.effect}
            >
                <ImageSlide
                    slide={activeSlide}
                    activeIndex={slider.activeIndex}
                    slideCount={slideCount}
                />
            </HeroSlideTransition>

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

function ImageSlide({
    slide,
    activeIndex,
    slideCount,
}: {
    slide: ImageHeroSlide
    activeIndex: number
    slideCount: number
}) {
    const image = (
        <img src={slide.image} alt={slide.alt ?? ''} className="block h-full w-full object-cover" />
    )

    return (
        <div
            role="group"
            aria-roledescription="slide"
            aria-label={`${activeIndex + 1} of ${slideCount}`}
            className="relative h-[320px] overflow-hidden sm:h-[420px] md:h-[500px] lg:h-[560px]"
        >
            {slide.url ? <SlideLink url={slide.url}>{image}</SlideLink> : image}
        </div>
    )
}

function SlideLink({ url, children }: { url: string; children: ReactNode }) {
    return (
        <a
            href={url}
            className="block h-full w-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-white"
        >
            {children}
        </a>
    )
}
