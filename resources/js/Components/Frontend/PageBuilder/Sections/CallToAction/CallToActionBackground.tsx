import { getSafeBackgroundImageUrl, type CallToActionConfig } from './call-to-action-config'

interface Props {
    config: CallToActionConfig
}

export default function CallToActionBackground({ config }: Props) {
    if (config.background_type !== 'image') {
        return null
    }

    const imageUrl = getSafeBackgroundImageUrl(config.background_image)

    if (!imageUrl) {
        return null
    }

    const overlay = Math.min(100, Math.max(0, config.background_overlay)) / 100

    return (
        <>
            <img
                src={imageUrl}
                alt=""
                aria-hidden="true"
                loading="lazy"
                decoding="async"
                className="pointer-events-none absolute inset-0 -z-30 h-full w-full select-none object-cover"
            />

            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 -z-20 bg-black"
                style={{
                    opacity: overlay,
                }}
            />
        </>
    )
}
