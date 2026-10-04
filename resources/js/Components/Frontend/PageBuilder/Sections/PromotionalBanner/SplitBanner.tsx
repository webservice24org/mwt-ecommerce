import { useId } from 'react'

import {
    promotionalBannerAlignmentClasses,
    type PromotionalBannerConfig,
} from './promotional-banner-config'

interface Props {
    config: PromotionalBannerConfig
}

export default function SplitBanner({ config }: Props) {
    const headingId = useId()

    return (
        <section
            aria-labelledby={config.heading ? headingId : undefined}
            className="py-5 sm:py-7 lg:py-8"
        >
            <div
                className={[
                    'overflow-hidden rounded-xl bg-neutral-100 text-neutral-950 sm:rounded-2xl',
                    config.image ? 'grid md:grid-cols-2' : '',
                ].join(' ')}
            >
                {config.image && (
                    <div className="relative aspect-[16/10] min-h-52 sm:min-h-64 md:aspect-auto md:min-h-72">
                        <img
                            src={config.image}
                            alt=""
                            loading="lazy"
                            decoding="async"
                            className="absolute inset-0 h-full w-full object-cover"
                        />
                    </div>
                )}

                <div
                    className={[
                        'flex min-w-0 flex-col justify-center px-5 py-8 sm:px-8 sm:py-12 lg:px-10 lg:py-14',
                        promotionalBannerAlignmentClasses[config.alignment],
                    ].join(' ')}
                >
                    {config.heading && (
                        <h2
                            id={headingId}
                            className="max-w-3xl break-words text-2xl font-semibold tracking-tight [overflow-wrap:anywhere] sm:text-3xl lg:text-4xl"
                        >
                            {config.heading}
                        </h2>
                    )}

                    {config.description && (
                        <p className="mt-3 max-w-2xl whitespace-pre-line break-words text-sm leading-relaxed text-neutral-600 [overflow-wrap:anywhere] sm:mt-4 sm:text-base lg:text-lg">
                            {config.description}
                        </p>
                    )}

                    {config.cta_label && config.cta_url && (
                        <a
                            href={config.cta_url}
                            className="mt-5 inline-flex min-h-11 max-w-full items-center justify-center rounded-lg bg-neutral-900 px-5 py-2.5 text-center text-sm font-semibold text-white break-words transition hover:bg-neutral-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-100 motion-reduce:transition-none sm:mt-6 sm:px-6 sm:py-3 [overflow-wrap:anywhere]"
                        >
                            {config.cta_label}
                        </a>
                    )}
                </div>
            </div>
        </section>
    )
}
