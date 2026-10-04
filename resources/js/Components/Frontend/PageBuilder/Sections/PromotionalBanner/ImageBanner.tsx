import { useId } from 'react'

import {
    promotionalBannerAlignmentClasses,
    type PromotionalBannerConfig,
} from './promotional-banner-config'

interface Props {
    config: PromotionalBannerConfig
}

export default function ImageBanner({ config }: Props) {
    const headingId = useId()

    return (
        <section
            aria-labelledby={config.heading ? headingId : undefined}
            className="py-5 sm:py-7 lg:py-8"
        >
            <div className="relative isolate overflow-hidden rounded-xl bg-neutral-900 text-white sm:rounded-2xl">
                {config.image && (
                    <>
                        <img
                            src={config.image}
                            alt=""
                            loading="lazy"
                            decoding="async"
                            className="absolute inset-0 -z-20 h-full w-full object-cover"
                        />

                        <div aria-hidden="true" className="absolute inset-0 -z-10 bg-black/60" />
                    </>
                )}

                <div
                    className={[
                        'flex min-h-56 min-w-0 flex-col justify-center px-5 py-10 sm:min-h-72 sm:px-8 sm:py-14 lg:min-h-80 lg:px-10 lg:py-16',
                        promotionalBannerAlignmentClasses[config.alignment],
                    ].join(' ')}
                >
                    {config.heading && (
                        <h2
                            id={headingId}
                            className="max-w-4xl break-words text-2xl font-semibold tracking-tight [overflow-wrap:anywhere] sm:text-3xl lg:text-4xl"
                        >
                            {config.heading}
                        </h2>
                    )}

                    {config.description && (
                        <p className="mt-3 max-w-2xl whitespace-pre-line break-words text-sm leading-relaxed text-neutral-200 [overflow-wrap:anywhere] sm:mt-4 sm:text-base lg:text-lg">
                            {config.description}
                        </p>
                    )}

                    {config.cta_label && config.cta_url && (
                        <a
                            href={config.cta_url}
                            className="mt-5 inline-flex min-h-11 max-w-full items-center justify-center rounded-lg bg-white px-5 py-2.5 text-center text-sm font-semibold text-neutral-950 break-words transition hover:bg-neutral-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-900 motion-reduce:transition-none sm:mt-6 sm:px-6 sm:py-3 [overflow-wrap:anywhere]"
                        >
                            {config.cta_label}
                        </a>
                    )}
                </div>
            </div>
        </section>
    )
}
