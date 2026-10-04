import { useId } from 'react'

import {
    contentAlignmentClasses,
    contentBodyClasses,
    contentHeadingClasses,
    type ContentSectionConfig,
} from './content-config'

interface Props {
    config: ContentSectionConfig
}

export default function TextImageContent({ config }: Props) {
    const headingId = useId()

    if (!config.body) {
        return null
    }

    const copy = (
        <div
            className={[
                'flex min-w-0 flex-col justify-center',
                contentAlignmentClasses[config.alignment],
            ].join(' ')}
        >
            {config.heading && (
                <h2 id={headingId} dir="auto" className={`max-w-3xl ${contentHeadingClasses}`}>
                    {config.heading}
                </h2>
            )}

            <p
                dir="auto"
                className={[
                    `max-w-3xl ${contentBodyClasses}`,
                    config.heading ? 'mt-4 sm:mt-5' : '',
                ].join(' ')}
            >
                {config.body}
            </p>
        </div>
    )

    return (
        <section
            aria-labelledby={config.heading ? headingId : undefined}
            className="min-w-0 py-8 sm:py-10 lg:py-12"
        >
            {config.image ? (
                <div className="grid min-w-0 gap-6 sm:gap-8 md:grid-cols-2 md:items-stretch lg:gap-10 xl:gap-12">
                    {copy}

                    <div className="relative aspect-[16/10] min-h-52 min-w-0 overflow-hidden rounded-xl bg-neutral-100 sm:min-h-64 md:aspect-auto md:min-h-80 dark:bg-neutral-900">
                        <img
                            src={config.image}
                            alt={config.image_alt ?? ''}
                            loading="lazy"
                            decoding="async"
                            className="absolute inset-0 h-full w-full object-cover"
                        />
                    </div>
                </div>
            ) : (
                copy
            )}
        </section>
    )
}
