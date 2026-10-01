import { Link } from '@inertiajs/react'

import type { HeroAlignment, HeroButton, HeroConfig, HeroSlide } from './types'

interface Props {
    config: HeroConfig
}

const alignmentClasses: Record<
    HeroAlignment,
    {
        container: string
        actions: string
    }
> = {
    left: {
        container: 'items-start text-left',
        actions: 'justify-start',
    },

    center: {
        container: 'items-center text-center',
        actions: 'justify-center',
    },

    right: {
        container: 'items-end text-right',
        actions: 'justify-end',
    },
}

export default function StaticHero({ config }: Props) {
    const slide = config.slides[0]

    if (!slide) {
        return null
    }

    return (
        <section
            data-storefront-section="hero"
            data-hero-template="static"
            className="relative isolate min-w-0 overflow-hidden rounded-2xl"
            style={{
                backgroundColor: slide.background_color ?? '#171717',
            }}
        >
            <HeroBackground slide={slide} />

            <div className="relative z-10 flex min-h-[420px] items-center px-6 py-16 sm:min-h-[500px] sm:px-10 sm:py-20 lg:min-h-[560px] lg:px-16">
                <div
                    className={`flex w-full flex-col ${
                        alignmentClasses[slide.alignment].container
                    }`}
                >
                    <div className="w-full min-w-0 max-w-3xl">
                        {slide.top_title && (
                            <p className="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-white/80 sm:text-sm">
                                {slide.top_title}
                            </p>
                        )}

                        {slide.title && (
                            <h2 className="text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl xl:text-6xl">
                                {slide.title}
                            </h2>
                        )}

                        {slide.description && (
                            <p
                                className={`mt-5 max-w-2xl text-base leading-7 text-white/85 sm:text-lg sm:leading-8 ${
                                    slide.alignment === 'center'
                                        ? 'mx-auto'
                                        : slide.alignment === 'right'
                                          ? 'ml-auto'
                                          : ''
                                }`}
                            >
                                {slide.description}
                            </p>
                        )}

                        <HeroActions slide={slide} />
                    </div>
                </div>
            </div>
        </section>
    )
}

function HeroBackground({ slide }: { slide: HeroSlide }) {
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

    return (
        <div className={`mt-8 flex flex-wrap gap-3 ${alignmentClasses[slide.alignment].actions}`}>
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
