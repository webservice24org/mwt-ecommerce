import { router, useForm } from '@inertiajs/react'
import { ChevronDown, ChevronUp, Film, ImagePlus, Star, Trash2, Upload } from 'lucide-react'
import { ChangeEvent, FormEvent, useEffect, useMemo, useRef, useState } from 'react'

import type { ProductImage, ProductVideo, ProductVideoType } from '@/types/catalog'

type Props = {
    productId: number
    images: ProductImage[]
    video: ProductVideo | null
}

type VideoFormData = {
    type: ProductVideoType
    url: string
    title: string
    video: File | null
}

function formatBytes(bytes: number | null): string {
    if (bytes === null) {
        return ''
    }

    if (bytes < 1024) {
        return `${bytes} B`
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function getYoutubeEmbedUrl(url: string): string | null {
    try {
        const parsed = new URL(url)
        const host = parsed.hostname.replace(/^www\./, '').toLowerCase()

        let videoId: string | null = null

        if (host === 'youtu.be') {
            videoId = parsed.pathname.split('/').filter(Boolean)[0] ?? null
        }

        if (host === 'youtube.com' || host === 'm.youtube.com') {
            videoId = parsed.searchParams.get('v')

            if (!videoId) {
                const parts = parsed.pathname.split('/').filter(Boolean)

                if (parts[0] === 'embed' || parts[0] === 'shorts') {
                    videoId = parts[1] ?? null
                }
            }
        }

        if (!videoId) {
            return null
        }

        return `https://www.youtube.com/embed/${encodeURIComponent(videoId)}`
    } catch {
        return null
    }
}

function getVimeoEmbedUrl(url: string): string | null {
    try {
        const parsed = new URL(url)
        const host = parsed.hostname.replace(/^www\./, '').toLowerCase()

        if (host !== 'vimeo.com' && host !== 'player.vimeo.com') {
            return null
        }

        const parts = parsed.pathname.split('/').filter(Boolean)

        const id = parts.find((part) => /^\d+$/.test(part))

        if (!id) {
            return null
        }

        return `https://player.vimeo.com/video/${id}`
    } catch {
        return null
    }
}

export default function ProductMediaPanel({ productId, images, video }: Props) {
    const fileInputRef = useRef<HTMLInputElement>(null)

    const [selectedImages, setSelectedImages] = useState<File[]>([])

    const [uploadingImages, setUploadingImages] = useState(false)

    const [altTexts, setAltTexts] = useState<Record<number, string>>({})

    const featuredImage = useMemo(() => images.find((image) => image.is_primary) ?? null, [images])

    const videoForm = useForm<VideoFormData>({
        type: video?.type ?? 'youtube',
        url: video?.url ?? '',
        title: video?.title ?? '',
        video: null,
    })

    const selectedVideoPreview = useMemo(() => {
        if (!videoForm.data.video) {
            return null
        }

        return URL.createObjectURL(videoForm.data.video)
    }, [videoForm.data.video])

    useEffect(() => {
        if (!selectedVideoPreview) {
            return
        }

        return () => {
            URL.revokeObjectURL(selectedVideoPreview)
        }
    }, [selectedVideoPreview])

    const hostedVideoPreview = useMemo(() => {
        if (videoForm.data.type === 'youtube' && videoForm.data.url) {
            return getYoutubeEmbedUrl(videoForm.data.url)
        }

        if (videoForm.data.type === 'vimeo' && videoForm.data.url) {
            return getVimeoEmbedUrl(videoForm.data.url)
        }

        return null
    }, [videoForm.data.type, videoForm.data.url])

    const getAltText = (image: ProductImage): string => altTexts[image.id] ?? image.alt_text ?? ''

    const handleImageSelection = (event: ChangeEvent<HTMLInputElement>) => {
        const files = Array.from(event.target.files ?? [])

        setSelectedImages(files)
    }

    const uploadImages = () => {
        if (selectedImages.length === 0 || uploadingImages) {
            return
        }

        setUploadingImages(true)

        const uploadNext = (index: number): void => {
            if (index >= selectedImages.length) {
                setUploadingImages(false)
                setSelectedImages([])

                if (fileInputRef.current) {
                    fileInputRef.current.value = ''
                }

                return
            }

            const formData = new FormData()

            formData.append('image', selectedImages[index])

            router.post(route('admin.products.images.store', productId), formData, {
                forceFormData: true,
                preserveScroll: true,

                onSuccess: () => {
                    uploadNext(index + 1)
                },

                onError: () => {
                    setUploadingImages(false)
                },
            })
        }

        uploadNext(0)
    }

    const setFeatured = (image: ProductImage) => {
        if (image.is_primary) {
            return
        }

        router.put(
            route('admin.products.images.featured', [productId, image.id]),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const updateAltText = (image: ProductImage) => {
        router.put(
            route('admin.products.images.update', [productId, image.id]),
            {
                alt_text: getAltText(image),
            },
            {
                preserveScroll: true,
            },
        )
    }

    const reorderImages = (fromIndex: number, toIndex: number) => {
        if (toIndex < 0 || toIndex >= images.length) {
            return
        }

        const ordered = [...images]

        const [moved] = ordered.splice(fromIndex, 1)

        ordered.splice(toIndex, 0, moved)

        router.put(
            route('admin.products.images.reorder', productId),
            {
                image_ids: ordered.map((image) => image.id),
            },
            {
                preserveScroll: true,
            },
        )
    }

    const deleteImage = (image: ProductImage) => {
        const confirmed = window.confirm(
            image.is_primary
                ? 'Delete this featured image? Another gallery image will automatically become featured.'
                : 'Delete this product image?',
        )

        if (!confirmed) {
            return
        }

        router.delete(route('admin.products.images.destroy', [productId, image.id]), {
            preserveScroll: true,
        })
    }

    const changeVideoType = (type: ProductVideoType) => {
        videoForm.setData((current) => ({
            ...current,
            type,

            url: type === 'upload' ? '' : current.url,

            video: type === 'upload' ? current.video : null,
        }))
    }

    const saveVideo = (event: FormEvent) => {
        event.preventDefault()

        videoForm.post(route('admin.products.video.store', productId), {
            forceFormData: true,
            preserveScroll: true,

            onSuccess: () => {
                videoForm.setData('video', null)
            },
        })
    }

    const removeVideo = () => {
        if (!window.confirm('Remove this product video?')) {
            return
        }

        router.delete(route('admin.products.video.destroy', productId), {
            preserveScroll: true,
        })
    }

    return (
        <div className="space-y-8">
            {/* Featured Image */}
            <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                <div className="mb-4">
                    <h2 className="text-lg font-semibold text-neutral-950 dark:text-neutral-50">
                        Featured Image
                    </h2>

                    <p className="mt-1 text-sm text-neutral-500">
                        This image is used as the main product image throughout the storefront.
                    </p>
                </div>

                {featuredImage ? (
                    <div className="grid gap-5 md:grid-cols-[240px_1fr]">
                        <div className="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-800">
                            <img
                                src={featuredImage.url}
                                alt={featuredImage.alt_text ?? ''}
                                className="aspect-square h-full w-full object-cover"
                            />
                        </div>

                        <div className="flex flex-col justify-center">
                            <div className="mb-3 flex items-center gap-2">
                                <Star className="h-5 w-5 fill-current" />

                                <span className="font-medium">Featured product image</span>
                            </div>

                            {featuredImage.original_name && (
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                    {featuredImage.original_name}
                                </p>
                            )}

                            <div className="mt-2 flex flex-wrap gap-3 text-xs text-neutral-500">
                                {featuredImage.width && featuredImage.height && (
                                    <span>
                                        {featuredImage.width}×{featuredImage.height}
                                    </span>
                                )}

                                {featuredImage.file_size && (
                                    <span>{formatBytes(featuredImage.file_size)}</span>
                                )}
                            </div>
                        </div>
                    </div>
                ) : (
                    <div className="flex min-h-48 flex-col items-center justify-center rounded-xl border border-dashed border-neutral-300 bg-neutral-50 p-8 text-center dark:border-neutral-700 dark:bg-neutral-900">
                        <ImagePlus className="mb-3 h-9 w-9 text-neutral-400" />

                        <p className="font-medium">No featured image</p>

                        <p className="mt-1 text-sm text-neutral-500">
                            The first uploaded image automatically becomes featured.
                        </p>
                    </div>
                )}
            </section>

            {/* Upload Images */}
            <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                <div className="mb-4">
                    <h2 className="text-lg font-semibold">Upload Images</h2>

                    <p className="mt-1 text-sm text-neutral-500">
                        JPG, PNG or WebP. Maximum 5 MB per image.
                    </p>
                </div>

                <div className="rounded-xl border border-dashed border-neutral-300 p-5 dark:border-neutral-700">
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        onChange={handleImageSelection}
                        className="block w-full text-sm"
                    />

                    {selectedImages.length > 0 && (
                        <div className="mt-4">
                            <p className="text-sm font-medium">
                                {selectedImages.length} image
                                {selectedImages.length !== 1 ? 's' : ''} selected
                            </p>

                            <div className="mt-2 space-y-1">
                                {selectedImages.map((file, index) => (
                                    <p
                                        key={`${file.name}-${index}`}
                                        className="truncate text-xs text-neutral-500"
                                    >
                                        {file.name} — {formatBytes(file.size)}
                                    </p>
                                ))}
                            </div>
                        </div>
                    )}

                    <button
                        type="button"
                        disabled={selectedImages.length === 0 || uploadingImages}
                        onClick={uploadImages}
                        className="mt-4 inline-flex h-10 items-center gap-2 rounded-md bg-neutral-950 px-4 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-neutral-950"
                    >
                        <Upload className="h-4 w-4" />

                        {uploadingImages ? 'Uploading...' : 'Upload Images'}
                    </button>
                </div>
            </section>

            {/* Gallery */}
            <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                <div className="mb-5">
                    <h2 className="text-lg font-semibold">Product Gallery</h2>

                    <p className="mt-1 text-sm text-neutral-500">
                        Manage featured image, order, alternative text and gallery images.
                    </p>
                </div>

                {images.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-neutral-300 p-8 text-center text-sm text-neutral-500 dark:border-neutral-700">
                        No product images uploaded yet.
                    </div>
                ) : (
                    <div className="space-y-4">
                        {images.map((image, index) => (
                            <div
                                key={image.id}
                                className="grid gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-800 lg:grid-cols-[140px_1fr_auto]"
                            >
                                <div className="relative overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-800">
                                    <img
                                        src={image.url}
                                        alt={image.alt_text ?? ''}
                                        className="aspect-square h-full w-full object-cover"
                                    />

                                    {image.is_primary && (
                                        <div className="absolute left-2 top-2 inline-flex items-center gap-1 rounded-md bg-black/80 px-2 py-1 text-xs font-medium text-white">
                                            <Star className="h-3 w-3 fill-current" />
                                            Featured
                                        </div>
                                    )}
                                </div>

                                <div className="min-w-0 space-y-3">
                                    <div>
                                        <p className="truncate text-sm font-medium">
                                            {image.original_name ?? `Image #${image.id}`}
                                        </p>

                                        <p className="mt-1 text-xs text-neutral-500">
                                            {image.width && image.height
                                                ? `${image.width} × ${image.height}`
                                                : ''}

                                            {image.file_size
                                                ? ` · ${formatBytes(image.file_size)}`
                                                : ''}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            htmlFor={`product-image-alt-${image.id}`}
                                            className="mb-1.5 block text-sm font-medium"
                                        >
                                            Alt Text
                                        </label>

                                        <div className="flex max-w-xl gap-2">
                                            <input
                                                id={`product-image-alt-${image.id}`}
                                                type="text"
                                                maxLength={255}
                                                value={getAltText(image)}
                                                onChange={(event) =>
                                                    setAltTexts((current) => ({
                                                        ...current,
                                                        [image.id]: event.target.value,
                                                    }))
                                                }
                                                className="h-10 min-w-0 flex-1 rounded-md border border-neutral-300 bg-white px-3 text-sm outline-none focus:ring-2 focus:ring-neutral-900 dark:border-neutral-700 dark:bg-neutral-950"
                                            />

                                            <button
                                                type="button"
                                                onClick={() => updateAltText(image)}
                                                className="rounded-md border border-neutral-300 px-3 text-sm font-medium dark:border-neutral-700"
                                            >
                                                Save
                                            </button>
                                        </div>
                                    </div>

                                    {!image.is_primary && (
                                        <button
                                            type="button"
                                            onClick={() => setFeatured(image)}
                                            className="inline-flex items-center gap-2 text-sm font-medium"
                                        >
                                            <Star className="h-4 w-4" />
                                            Make Featured
                                        </button>
                                    )}
                                </div>

                                <div className="flex items-start gap-1 lg:flex-col">
                                    <button
                                        type="button"
                                        title="Move up"
                                        disabled={index === 0}
                                        onClick={() => reorderImages(index, index - 1)}
                                        className="rounded-md border border-neutral-200 p-2 disabled:opacity-30 dark:border-neutral-800"
                                    >
                                        <ChevronUp className="h-4 w-4" />
                                    </button>

                                    <button
                                        type="button"
                                        title="Move down"
                                        disabled={index === images.length - 1}
                                        onClick={() => reorderImages(index, index + 1)}
                                        className="rounded-md border border-neutral-200 p-2 disabled:opacity-30 dark:border-neutral-800"
                                    >
                                        <ChevronDown className="h-4 w-4" />
                                    </button>

                                    <button
                                        type="button"
                                        title="Delete image"
                                        onClick={() => deleteImage(image)}
                                        className="rounded-md border border-red-200 p-2 text-red-600 dark:border-red-900"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </section>

            {/* Product Video */}
            <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                <div className="mb-5 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 className="text-lg font-semibold">Product Video</h2>

                        <p className="mt-1 text-sm text-neutral-500">
                            Add a YouTube, Vimeo or uploaded product video.
                        </p>
                    </div>

                    {video && (
                        <button
                            type="button"
                            onClick={removeVideo}
                            className="inline-flex items-center gap-2 rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-900"
                        >
                            <Trash2 className="h-4 w-4" />
                            Remove Video
                        </button>
                    )}
                </div>

                <form onSubmit={saveVideo} className="space-y-5">
                    <div>
                        <label className="mb-2 block text-sm font-medium">Video Source</label>

                        <div className="grid gap-3 sm:grid-cols-3">
                            {(
                                [
                                    ['youtube', 'YouTube'],
                                    ['vimeo', 'Vimeo'],
                                    ['upload', 'Upload Video'],
                                ] as const
                            ).map(([value, label]) => (
                                <label
                                    key={value}
                                    className={`cursor-pointer rounded-lg border p-4 ${
                                        videoForm.data.type === value
                                            ? 'border-neutral-950 ring-1 ring-neutral-950 dark:border-white dark:ring-white'
                                            : 'border-neutral-200 dark:border-neutral-800'
                                    }`}
                                >
                                    <input
                                        type="radio"
                                        name="video_type"
                                        value={value}
                                        checked={videoForm.data.type === value}
                                        onChange={() => changeVideoType(value)}
                                        className="mr-2"
                                    />

                                    {label}
                                </label>
                            ))}
                        </div>

                        {videoForm.errors.type && (
                            <p className="mt-2 text-sm text-red-600">{videoForm.errors.type}</p>
                        )}
                    </div>

                    {videoForm.data.type !== 'upload' ? (
                        <div>
                            <label
                                htmlFor="product-video-url"
                                className="mb-1.5 block text-sm font-medium"
                            >
                                {videoForm.data.type === 'youtube' ? 'YouTube URL' : 'Vimeo URL'}
                            </label>

                            <input
                                id="product-video-url"
                                type="url"
                                value={videoForm.data.url}
                                onChange={(event) => videoForm.setData('url', event.target.value)}
                                placeholder={
                                    videoForm.data.type === 'youtube'
                                        ? 'https://www.youtube.com/watch?v=...'
                                        : 'https://vimeo.com/...'
                                }
                                className="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950"
                            />

                            {videoForm.errors.url && (
                                <p className="mt-1 text-sm text-red-600">{videoForm.errors.url}</p>
                            )}
                        </div>
                    ) : (
                        <div>
                            <label
                                htmlFor="product-video-file"
                                className="mb-1.5 block text-sm font-medium"
                            >
                                Video File
                            </label>

                            <input
                                id="product-video-file"
                                type="file"
                                accept="video/mp4,video/webm"
                                onChange={(event) =>
                                    videoForm.setData('video', event.target.files?.[0] ?? null)
                                }
                                className="block w-full text-sm"
                            />

                            <p className="mt-1 text-xs text-neutral-500">
                                MP4 or WebM. Maximum 100 MB.
                            </p>

                            {video?.type === 'upload' && !videoForm.data.video && (
                                <p className="mt-2 text-xs text-amber-600">
                                    Select a new video file before saving an uploaded-video source.
                                </p>
                            )}

                            {videoForm.errors.video && (
                                <p className="mt-1 text-sm text-red-600">
                                    {videoForm.errors.video}
                                </p>
                            )}
                        </div>
                    )}

                    <div>
                        <label
                            htmlFor="product-video-title"
                            className="mb-1.5 block text-sm font-medium"
                        >
                            Video Title
                        </label>

                        <input
                            id="product-video-title"
                            type="text"
                            maxLength={180}
                            value={videoForm.data.title}
                            onChange={(event) => videoForm.setData('title', event.target.value)}
                            className="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950"
                        />

                        {videoForm.errors.title && (
                            <p className="mt-1 text-sm text-red-600">{videoForm.errors.title}</p>
                        )}
                    </div>

                    {/* Video Preview */}
                    {(selectedVideoPreview || hostedVideoPreview || video?.file_url) && (
                        <div>
                            <p className="mb-2 text-sm font-medium">Video Preview</p>

                            <div className="overflow-hidden rounded-xl border border-neutral-200 bg-black dark:border-neutral-800">
                                {videoForm.data.type === 'upload' &&
                                (selectedVideoPreview || video?.file_url) ? (
                                    <video
                                        controls
                                        preload="metadata"
                                        className="aspect-video w-full"
                                        src={selectedVideoPreview ?? video?.file_url ?? undefined}
                                    />
                                ) : hostedVideoPreview ? (
                                    <iframe
                                        src={hostedVideoPreview}
                                        title={videoForm.data.title || 'Product video'}
                                        className="aspect-video w-full"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowFullScreen
                                    />
                                ) : null}
                            </div>
                        </div>
                    )}

                    <button
                        type="submit"
                        disabled={videoForm.processing}
                        className="inline-flex h-10 items-center gap-2 rounded-md bg-neutral-950 px-4 text-sm font-medium text-white disabled:opacity-50 dark:bg-white dark:text-neutral-950"
                    >
                        <Film className="h-4 w-4" />

                        {videoForm.processing ? 'Saving...' : 'Save Video'}
                    </button>
                </form>
            </section>
        </div>
    )
}
