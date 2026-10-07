import { Star } from 'lucide-react'
import { useId } from 'react'

import {
    PageBuilderSliderArrows,
    PageBuilderSliderDots,
} from '@/Components/Frontend/PageBuilder/Shared/PageBuilderSliderControls'
import PageBuilderSliderViewport from '@/Components/Frontend/PageBuilder/Shared/PageBuilderSliderViewport'
import usePageBuilderSlider from '@/Components/Frontend/PageBuilder/Shared/usePageBuilderSlider'
import usePrefersReducedMotion from '@/Components/Frontend/PageBuilder/Shared/usePrefersReducedMotion'
import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'

import {
    readTestimonialsSectionConfig,
    type StorefrontTestimonial,
} from './testimonials-storefront'
import useResponsiveTestimonialCount from './useResponsiveTestimonialCount'

interface Props {
    section: StorefrontSectionProps['section']
}

export default function TestimonialCardSliderSection({ section }: Props) {
    const headingId = useId()

    const config = readTestimonialsSectionConfig(section.config)

    const testimonials = config.items

    const reducedMotion = usePrefersReducedMotion()

    const visibleCardCount = useResponsiveTestimonialCount()

    const slider = usePageBuilderSlider({
        slideCount: testimonials.length,

        autoplay: config.autoplay,

        autoplayInterval: config.autoplay_interval,

        pauseOnHover: config.pause_on_hover,

        loop: config.loop,

        reducedMotion,
    })

    if (testimonials.length === 0) {
        return null
    }

    const visibleTestimonials = getVisibleTestimonials(
        testimonials,
        slider.activeIndex,
        config.loop,
        visibleCardCount,
    )

    const lightTheme = config.text_theme === 'light'

    const controlTheme = lightTheme ? 'light' : 'dark'

    const hasHeading = config.heading !== null

    return (
        <section
            role="region"
            aria-roledescription="carousel"
            aria-labelledby={hasHeading ? headingId : undefined}
            aria-label={hasHeading ? undefined : 'Customer testimonials'}
            className="overflow-x-clip py-6 sm:py-8"
        >
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    {...slider.interactionProps}
                    className={[
                        'relative overflow-hidden rounded-3xl border',
                        'p-5 shadow-sm sm:p-8 lg:p-12',

                        lightTheme ? 'border-white/10' : 'border-slate-200/80',
                    ].join(' ')}
                    style={{
                        backgroundColor: config.background_color,
                    }}
                >
                    <header
                        className={[
                            'mb-8 flex flex-col gap-5',
                            'sm:flex-row sm:items-end sm:justify-between',
                        ].join(' ')}
                    >
                        <div
                            className={[
                                'min-w-0',

                                config.alignment === 'center'
                                    ? 'text-center sm:text-left'
                                    : 'text-left',
                            ].join(' ')}
                        >
                            {config.eyebrow && (
                                <p
                                    className={[
                                        'text-xs font-bold uppercase tracking-[0.18em]',

                                        lightTheme ? 'text-white/70' : 'text-indigo-600',
                                    ].join(' ')}
                                >
                                    {config.eyebrow}
                                </p>
                            )}

                            {config.heading && (
                                <h2
                                    id={headingId}
                                    className={[
                                        'text-2xl font-extrabold tracking-tight',
                                        'sm:text-3xl',

                                        config.eyebrow ? 'mt-1' : '',

                                        lightTheme ? 'text-white' : 'text-slate-900',
                                    ].join(' ')}
                                >
                                    {config.heading}
                                </h2>
                            )}

                            {config.description && (
                                <p
                                    className={[
                                        'mt-3 max-w-2xl text-sm leading-6',

                                        lightTheme ? 'text-white/70' : 'text-slate-600',
                                    ].join(' ')}
                                >
                                    {config.description}
                                </p>
                            )}
                        </div>

                        {config.show_arrows && testimonials.length > 1 && (
                            <PageBuilderSliderArrows
                                canGoPrevious={slider.canGoPrevious}
                                canGoNext={slider.canGoNext}
                                onPrevious={slider.previous}
                                onNext={slider.next}
                                theme={controlTheme}
                                previousLabel="Previous testimonial"
                                nextLabel="Next testimonial"
                            />
                        )}
                    </header>

                    <PageBuilderSliderViewport
                        activeIndex={slider.activeIndex}
                        direction={slider.direction}
                        effect={reducedMotion ? 'none' : config.slide_effect}
                    >
                        <div
                            role="list"
                            aria-label="Customer testimonial cards"
                            className={[
                                'grid min-w-0 grid-cols-1 gap-6',
                                'md:grid-cols-2 xl:grid-cols-3',
                                'pb-2',
                            ].join(' ')}
                        >
                            {visibleTestimonials.map((testimonial, index) => (
                                <TestimonialCard
                                    key={`${slider.activeIndex}-${index}-${testimonial.name}`}
                                    testimonial={testimonial}
                                    showRating={config.show_rating}
                                    lightTheme={lightTheme}
                                />
                            ))}
                        </div>
                    </PageBuilderSliderViewport>

                    {config.show_dots && testimonials.length > 1 && (
                        <div className="mt-7 flex justify-center">
                            <PageBuilderSliderDots
                                activeIndex={slider.activeIndex}
                                slideCount={testimonials.length}
                                onSelect={slider.goTo}
                                theme={controlTheme}
                                label="Choose testimonial"
                                itemLabel="testimonial"
                            />
                        </div>
                    )}
                </div>
            </div>
        </section>
    )
}

interface TestimonialCardProps {
    testimonial: StorefrontTestimonial

    showRating: boolean
    lightTheme: boolean
}

function TestimonialCard({ testimonial, showRating, lightTheme }: TestimonialCardProps) {
    return (
        <article
            role="listitem"
            className={[
                'flex min-w-0 flex-col justify-between',
                'rounded-2xl border p-6',

                lightTheme
                    ? ['border-white/10', 'bg-white/10'].join(' ')
                    : ['border-slate-200/80', 'bg-slate-50'].join(' '),
            ].join(' ')}
        >
            <div className="space-y-3">
                {showRating && testimonial.rating !== null && (
                    <Rating rating={testimonial.rating} />
                )}

                <blockquote>
                    <p
                        className={[
                            'break-words text-sm font-medium leading-relaxed',
                            '[overflow-wrap:anywhere]',

                            lightTheme ? 'text-white/90' : 'text-slate-700',
                        ].join(' ')}
                    >
                        “{testimonial.quote}”
                    </p>
                </blockquote>
            </div>

            <footer
                className={[
                    'mt-6 flex min-w-0 items-center gap-3 border-t pt-4',

                    lightTheme ? 'border-white/15' : 'border-slate-200/60',
                ].join(' ')}
            >
                <TestimonialAvatar testimonial={testimonial} lightTheme={lightTheme} />

                <div className="min-w-0">
                    <p
                        className={[
                            'break-words text-sm font-bold',
                            '[overflow-wrap:anywhere]',

                            lightTheme ? 'text-white' : 'text-slate-900',
                        ].join(' ')}
                    >
                        {testimonial.name}
                    </p>

                    {testimonial.role && (
                        <p
                            className={[
                                'mt-0.5 break-words text-xs',
                                '[overflow-wrap:anywhere]',

                                lightTheme ? 'text-white/65' : 'text-slate-500',
                            ].join(' ')}
                        >
                            {testimonial.role}
                        </p>
                    )}
                </div>
            </footer>
        </article>
    )
}

interface TestimonialAvatarProps {
    testimonial: StorefrontTestimonial

    lightTheme: boolean
}

function TestimonialAvatar({ testimonial, lightTheme }: TestimonialAvatarProps) {
    if (testimonial.image) {
        return (
            <img
                src={testimonial.image}
                alt={testimonial.image_alt ?? ''}
                loading="lazy"
                decoding="async"
                draggable={false}
                className="h-10 w-10 shrink-0 rounded-full object-cover"
            />
        )
    }

    return (
        <span
            aria-hidden="true"
            className={[
                'flex h-10 w-10 shrink-0 items-center justify-center',
                'rounded-full text-sm font-bold uppercase',

                lightTheme ? 'bg-white/15 text-white' : 'bg-indigo-100 text-indigo-700',
            ].join(' ')}
        >
            {getInitials(testimonial.name)}
        </span>
    )
}

interface RatingProps {
    rating: number
}

function Rating({ rating }: RatingProps) {
    return (
        <div
            className="flex items-center gap-1 text-amber-400"
            aria-label={`${rating} out of 5 stars`}
        >
            {Array.from(
                {
                    length: 5,
                },
                (_item, index) => (
                    <Star
                        key={index}
                        aria-hidden="true"
                        className={['h-4 w-4', index < rating ? 'fill-current' : 'opacity-30'].join(
                            ' ',
                        )}
                    />
                ),
            )}
        </div>
    )
}

function getVisibleTestimonials(
    testimonials: StorefrontTestimonial[],
    activeIndex: number,
    loop: boolean,
    limit: number,
): StorefrontTestimonial[] {
    if (testimonials.length === 0) {
        return []
    }

    const safeLimit = Math.min(limit, testimonials.length)

    if (!loop) {
        return testimonials.slice(activeIndex, activeIndex + safeLimit)
    }

    const result: StorefrontTestimonial[] = []

    for (let offset = 0; offset < safeLimit; offset += 1) {
        const index = (activeIndex + offset) % testimonials.length

        const testimonial = testimonials[index]

        if (testimonial) {
            result.push(testimonial)
        }
    }

    return result
}

function getInitials(name: string): string {
    const words = name.trim().split(/\s+/).filter(Boolean)

    if (words.length === 0) {
        return '?'
    }

    return words
        .slice(0, 2)
        .map((word) => word.charAt(0).toUpperCase())
        .join('')
}
