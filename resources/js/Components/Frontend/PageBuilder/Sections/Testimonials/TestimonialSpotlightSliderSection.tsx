import { ChevronLeft, ChevronRight, Star } from 'lucide-react'

import { useId } from 'react'

import { PageBuilderSliderDots } from '@/Components/Frontend/PageBuilder/Shared/PageBuilderSliderControls'

import PageBuilderSliderViewport from '@/Components/Frontend/PageBuilder/Shared/PageBuilderSliderViewport'

import usePageBuilderSlider from '@/Components/Frontend/PageBuilder/Shared/usePageBuilderSlider'

import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'
import usePrefersReducedMotion from '@/Components/Frontend/PageBuilder/Shared/usePrefersReducedMotion'

import {
    readTestimonialsSectionConfig,
    type StorefrontTestimonial,
} from './testimonials-storefront'

interface Props {
    section: StorefrontSectionProps['section']
}

export default function TestimonialSpotlightSliderSection({ section }: Props) {
    const headingId = useId()

    const config = readTestimonialsSectionConfig(section.config)
    const reducedMotion = usePrefersReducedMotion()

    const testimonials = config.items

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

    const testimonial = testimonials[slider.activeIndex] ?? testimonials[0]

    if (!testimonial) {
        return null
    }

    const lightTheme = config.text_theme === 'light'

    const hasHeading = config.heading !== null

    const hasHeader = config.eyebrow !== null || hasHeading || config.description !== ''

    return (
        <section
            role="region"
            aria-roledescription="carousel"
            aria-labelledby={hasHeading ? headingId : undefined}
            aria-label={hasHeading ? undefined : 'Featured customer testimonials'}
            className="overflow-x-clip py-6 sm:py-8"
        >
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    {...slider.interactionProps}
                    className={[
                        'relative overflow-hidden rounded-3xl',
                        'p-5 shadow-xl',
                        'sm:p-8 lg:p-14',
                    ].join(' ')}
                    style={{
                        backgroundColor: config.background_color,

                        backgroundImage: `linear-gradient(135deg, ${config.background_color} 0%, ${config.background_color} 48%, #0f172a 100%)`,
                    }}
                >
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-indigo-400/10 blur-3xl"
                    />

                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute -bottom-32 -left-24 h-80 w-80 rounded-full bg-purple-400/10 blur-3xl"
                    />

                    <div className="relative">
                        {hasHeader && (
                            <header
                                className={[
                                    'mx-auto mb-10 max-w-3xl',

                                    config.alignment === 'center' ? 'text-center' : 'text-left',
                                ].join(' ')}
                            >
                                {config.eyebrow && (
                                    <p
                                        className={[
                                            'text-xs font-bold uppercase tracking-[0.2em]',

                                            lightTheme ? 'text-indigo-200' : 'text-indigo-700',
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

                                            config.eyebrow ? 'mt-2' : '',

                                            lightTheme ? 'text-white' : 'text-slate-900',
                                        ].join(' ')}
                                    >
                                        {config.heading}
                                    </h2>
                                )}

                                {config.description && (
                                    <p
                                        className={[
                                            'mt-3 text-sm leading-6',

                                            lightTheme ? 'text-indigo-100/80' : 'text-slate-700',
                                        ].join(' ')}
                                    >
                                        {config.description}
                                    </p>
                                )}
                            </header>
                        )}

                        <PageBuilderSliderViewport
                            activeIndex={slider.activeIndex}
                            direction={slider.direction}
                            effect={reducedMotion ? 'none' : config.slide_effect}
                        >
                            <div
                                role="group"
                                aria-roledescription="slide"
                                aria-label={`Testimonial ${slider.activeIndex + 1} of ${testimonials.length}`}
                            >
                                <SpotlightTestimonial
                                    testimonial={testimonial}
                                    showRating={config.show_rating}
                                    lightTheme={lightTheme}
                                />
                            </div>
                        </PageBuilderSliderViewport>

                        {testimonials.length > 1 && (config.show_arrows || config.show_dots) && (
                            <SpotlightNavigation
                                activeIndex={slider.activeIndex}
                                slideCount={testimonials.length}
                                showArrows={config.show_arrows}
                                showDots={config.show_dots}
                                canGoPrevious={slider.canGoPrevious}
                                canGoNext={slider.canGoNext}
                                lightTheme={lightTheme}
                                onPrevious={slider.previous}
                                onNext={slider.next}
                                onSelect={slider.goTo}
                            />
                        )}
                    </div>
                </div>
            </div>
        </section>
    )
}

interface SpotlightTestimonialProps {
    testimonial: StorefrontTestimonial

    showRating: boolean
    lightTheme: boolean
}

function SpotlightTestimonial({ testimonial, showRating, lightTheme }: SpotlightTestimonialProps) {
    return (
        <article className="mx-auto max-w-4xl px-1 text-center sm:px-4">
            <div className="space-y-7 sm:space-y-8">
                {testimonial.badge && (
                    <p>
                        <span
                            className={[
                                'inline-flex rounded-full border',
                                'px-3.5 py-1',
                                'text-xs font-bold uppercase tracking-[0.18em]',

                                lightTheme
                                    ? [
                                          'border-indigo-400/30',
                                          'bg-indigo-800/50',
                                          'text-indigo-200',
                                      ].join(' ')
                                    : ['border-indigo-200', 'bg-indigo-50', 'text-indigo-700'].join(
                                          ' ',
                                      ),
                            ].join(' ')}
                        >
                            {testimonial.badge}
                        </span>
                    </p>
                )}

                {showRating && testimonial.rating !== null && (
                    <SpotlightRating rating={testimonial.rating} />
                )}

                <blockquote>
                    <p
                        className={[
                            'break-words text-xl font-extrabold leading-snug tracking-tight',
                            '[overflow-wrap:anywhere]',
                            'sm:text-3xl',

                            lightTheme ? 'text-slate-100' : 'text-slate-900',
                        ].join(' ')}
                    >
                        “{testimonial.quote}”
                    </p>
                </blockquote>

                <div className="flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <SpotlightAvatar testimonial={testimonial} lightTheme={lightTheme} />

                    <div className="min-w-0 text-center sm:text-left">
                        <p
                            className={[
                                'break-words text-base font-bold',
                                '[overflow-wrap:anywhere]',

                                lightTheme ? 'text-white' : 'text-slate-900',
                            ].join(' ')}
                        >
                            {testimonial.name}
                        </p>

                        {testimonial.role && (
                            <p
                                className={[
                                    'mt-1 break-words text-xs',
                                    '[overflow-wrap:anywhere]',

                                    lightTheme ? 'text-indigo-200' : 'text-slate-600',
                                ].join(' ')}
                            >
                                {testimonial.role}
                            </p>
                        )}
                    </div>
                </div>
            </div>
        </article>
    )
}

interface SpotlightAvatarProps {
    testimonial: StorefrontTestimonial

    lightTheme: boolean
}

function SpotlightAvatar({ testimonial, lightTheme }: SpotlightAvatarProps) {
    if (testimonial.image) {
        return (
            <img
                src={testimonial.image}
                alt={testimonial.image_alt ?? ''}
                loading="lazy"
                decoding="async"
                draggable={false}
                className={[
                    'h-14 w-14 shrink-0 rounded-full object-cover shadow-md',
                    'border-2',

                    lightTheme ? 'border-indigo-400' : 'border-indigo-200',
                ].join(' ')}
            />
        )
    }

    return (
        <span
            aria-hidden="true"
            className={[
                'flex h-14 w-14 shrink-0 items-center justify-center',
                'rounded-full border-2 text-base font-bold uppercase shadow-md',

                lightTheme
                    ? ['border-indigo-400', 'bg-indigo-800', 'text-white'].join(' ')
                    : ['border-indigo-200', 'bg-indigo-100', 'text-indigo-700'].join(' '),
            ].join(' ')}
        >
            {getInitials(testimonial.name)}
        </span>
    )
}

interface SpotlightRatingProps {
    rating: number
}

function SpotlightRating({ rating }: SpotlightRatingProps) {
    return (
        <div
            className="flex items-center justify-center gap-1 text-amber-400"
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
                        className={['h-5 w-5', index < rating ? 'fill-current' : 'opacity-30'].join(
                            ' ',
                        )}
                    />
                ),
            )}
        </div>
    )
}

interface SpotlightNavigationProps {
    activeIndex: number
    slideCount: number

    showArrows: boolean
    showDots: boolean

    canGoPrevious: boolean
    canGoNext: boolean

    lightTheme: boolean

    onPrevious: () => void
    onNext: () => void

    onSelect: (index: number) => void
}

function SpotlightNavigation({
    activeIndex,
    slideCount,
    showArrows,
    showDots,
    canGoPrevious,
    canGoNext,
    lightTheme,
    onPrevious,
    onNext,
    onSelect,
}: SpotlightNavigationProps) {
    return (
        <div
            className={[
                'mx-auto mt-8 flex max-w-4xl items-center border-t pt-8',

                showArrows ? 'justify-between' : 'justify-center',

                lightTheme ? 'border-indigo-800/60' : 'border-slate-300/60',
            ].join(' ')}
        >
            {showArrows && (
                <SpotlightArrowButton
                    direction="previous"
                    label="Previous testimonial"
                    disabled={!canGoPrevious}
                    lightTheme={lightTheme}
                    onClick={onPrevious}
                />
            )}

            {showDots && (
                <PageBuilderSliderDots
                    activeIndex={activeIndex}
                    slideCount={slideCount}
                    onSelect={onSelect}
                    theme={lightTheme ? 'light' : 'dark'}
                    label="Choose testimonial"
                    itemLabel="testimonial"
                />
            )}

            {showArrows && (
                <SpotlightArrowButton
                    direction="next"
                    label="Next testimonial"
                    disabled={!canGoNext}
                    lightTheme={lightTheme}
                    onClick={onNext}
                />
            )}
        </div>
    )
}

interface SpotlightArrowButtonProps {
    direction: 'previous' | 'next'

    label: string
    disabled: boolean

    lightTheme: boolean

    onClick: () => void
}

function SpotlightArrowButton({
    direction,
    label,
    disabled,
    lightTheme,
    onClick,
}: SpotlightArrowButtonProps) {
    const Icon = direction === 'previous' ? ChevronLeft : ChevronRight

    return (
        <button
            type="button"
            aria-label={label}
            title={label}
            disabled={disabled}
            onClick={onClick}
            className={[
                'inline-flex h-11 w-11 shrink-0 items-center justify-center',
                'rounded-full border transition',
                'focus-visible:outline-none focus-visible:ring-2',
                'focus-visible:ring-offset-2',
                'disabled:cursor-not-allowed disabled:opacity-40',
                'motion-reduce:transition-none',

                lightTheme
                    ? [
                          'border-indigo-700/80',
                          'text-indigo-200',
                          'hover:bg-indigo-800/50',
                          'focus-visible:ring-white',
                          'focus-visible:ring-offset-indigo-950',
                      ].join(' ')
                    : [
                          'border-slate-300',
                          'text-slate-700',
                          'hover:bg-slate-100',
                          'focus-visible:ring-indigo-600',
                          'focus-visible:ring-offset-white',
                      ].join(' '),
            ].join(' ')}
        >
            <Icon aria-hidden="true" className="h-5 w-5" />
        </button>
    )
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
