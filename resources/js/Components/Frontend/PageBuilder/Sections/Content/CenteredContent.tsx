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

export default function CenteredContent({ config }: Props) {
    const headingId = useId()

    if (!config.body) {
        return null
    }

    return (
        <section
            aria-labelledby={config.heading ? headingId : undefined}
            className="min-w-0 py-8 sm:py-10 lg:py-12"
        >
            <div className="mx-auto w-full min-w-0 max-w-5xl">
                {config.image && (
                    <div className="relative mx-auto mb-6 aspect-[16/9] min-h-52 min-w-0 overflow-hidden rounded-xl bg-neutral-100 sm:mb-8 sm:min-h-72 lg:mb-10 dark:bg-neutral-900">
                        <img
                            src={config.image}
                            alt={config.image_alt ?? ''}
                            loading="lazy"
                            decoding="async"
                            className="absolute inset-0 h-full w-full object-cover"
                        />
                    </div>
                )}

                <div
                    className={[
                        'mx-auto flex min-w-0 max-w-4xl flex-col',
                        contentAlignmentClasses[config.alignment],
                    ].join(' ')}
                >
                    {config.heading && (
                        <h2 id={headingId} dir="auto" className={contentHeadingClasses}>
                            {config.heading}
                        </h2>
                    )}

                    <p
                        dir="auto"
                        className={[contentBodyClasses, config.heading ? 'mt-4 sm:mt-5' : ''].join(
                            ' ',
                        )}
                    >
                        {config.body}
                    </p>
                </div>
            </div>
        </section>
    )
}
