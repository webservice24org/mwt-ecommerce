import type { StorefrontVideo } from '@/types/storefront'

interface ProductVideoViewerProps {
    video: StorefrontVideo
    productName: string
}

export default function ProductVideoViewer({ video, productName }: ProductVideoViewerProps) {
    if (!video.url) {
        return <UnavailableVideo />
    }

    if (video.type === 'upload') {
        return (
            <div className="overflow-hidden rounded-2xl bg-black">
                <video
                    key={video.url}
                    controls
                    preload="metadata"
                    className="aspect-square h-full w-full object-contain"
                    aria-label={`${productName} product video`}
                >
                    <source src={video.url} />
                    Your browser does not support video playback.
                </video>
            </div>
        )
    }

    const embedUrl = getEmbedUrl(video.type, video.url)

    if (!embedUrl) {
        return <UnavailableVideo />
    }

    return (
        <div className="overflow-hidden rounded-2xl bg-black">
            <iframe
                src={embedUrl}
                title={`${productName} product video`}
                className="aspect-square h-full w-full"
                loading="lazy"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowFullScreen
            />
        </div>
    )
}

function getEmbedUrl(type: StorefrontVideo['type'], url: string): string | null {
    try {
        const parsed = new URL(url)

        if (type === 'youtube') {
            const videoId = getYouTubeVideoId(parsed)

            return videoId ? `https://www.youtube-nocookie.com/embed/${videoId}` : null
        }

        if (type === 'vimeo') {
            const videoId = getVimeoVideoId(parsed)

            return videoId ? `https://player.vimeo.com/video/${videoId}` : null
        }

        return null
    } catch {
        return null
    }
}

function getYouTubeVideoId(url: URL): string | null {
    const host = url.hostname.toLowerCase().replace(/^www\./, '')

    if (host === 'youtu.be') {
        return sanitizeVideoId(url.pathname.split('/').filter(Boolean)[0])
    }

    if (host === 'youtube.com' || host === 'm.youtube.com') {
        if (url.pathname === '/watch') {
            return sanitizeVideoId(url.searchParams.get('v'))
        }

        const parts = url.pathname.split('/').filter(Boolean)

        if (['embed', 'shorts'].includes(parts[0] ?? '')) {
            return sanitizeVideoId(parts[1])
        }
    }

    return null
}

function getVimeoVideoId(url: URL): string | null {
    const host = url.hostname.toLowerCase().replace(/^www\./, '')

    if (host !== 'vimeo.com' && host !== 'player.vimeo.com') {
        return null
    }

    const parts = url.pathname.split('/').filter(Boolean)

    const candidate = parts[0] === 'video' ? parts[1] : parts[0]

    if (!candidate || !/^\d+$/.test(candidate)) {
        return null
    }

    return candidate
}

function sanitizeVideoId(value: string | null | undefined): string | null {
    if (!value || !/^[A-Za-z0-9_-]+$/.test(value)) {
        return null
    }

    return value
}

function UnavailableVideo() {
    return (
        <div className="flex aspect-square items-center justify-center rounded-2xl bg-neutral-100 p-6 text-center text-sm text-neutral-500">
            Video unavailable
        </div>
    )
}
