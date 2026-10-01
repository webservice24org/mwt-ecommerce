import { useId, useRef, useState } from 'react'

import { uploadPageBuilderImage } from './upload-page-builder-image'

interface Props {
    pageId: number
    label: string
    value: string | null
    previewUrl?: string | null
    helpText?: string
    onChange: (value: string | null) => void
}

export default function PageBuilderImageField({
    pageId,
    label,
    value,
    previewUrl,
    helpText,
    onChange,
}: Props) {
    const inputId = useId()

    const fileInputRef = useRef<HTMLInputElement>(null)

    const [uploading, setUploading] = useState(false)

    const [error, setError] = useState<string | null>(null)

    const displayUrl = previewUrl ?? value

    const chooseImage = () => {
        if (uploading) {
            return
        }

        fileInputRef.current?.click()
    }

    const handleFileChange = async (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0]

        event.target.value = ''

        if (!file) {
            return
        }

        setError(null)
        setUploading(true)

        try {
            const response = await uploadPageBuilderImage({
                pageId,
                file,
            })

            /*
             * Store the public URL in section config.
             *
             * This matches the Hero editor's current
             * image/background-image contract.
             */
            onChange(response.image.url)
        } catch (exception) {
            setError(
                exception instanceof Error ? exception.message : 'The image could not be uploaded.',
            )
        } finally {
            setUploading(false)
        }
    }

    const removeImage = () => {
        setError(null)
        onChange(null)
    }

    return (
        <div className="space-y-3">
            <div>
                <label htmlFor={inputId} className="text-sm font-medium">
                    {label}
                </label>

                {helpText && <p className="mt-1 text-xs text-muted-foreground">{helpText}</p>}
            </div>

            {displayUrl && (
                <div className="overflow-hidden rounded-lg border bg-muted/20">
                    <img src={displayUrl} alt="" className="aspect-[16/6] w-full object-cover" />
                </div>
            )}

            <input
                ref={fileInputRef}
                id={inputId}
                type="file"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                onChange={handleFileChange}
                className="sr-only"
            />

            <div className="flex flex-wrap gap-2">
                <button
                    type="button"
                    disabled={uploading}
                    onClick={chooseImage}
                    className="inline-flex h-9 items-center justify-center rounded-md border px-3 text-sm font-medium transition hover:bg-muted disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {uploading ? 'Uploading...' : value ? 'Replace Image' : 'Choose Image'}
                </button>

                {value && (
                    <button
                        type="button"
                        disabled={uploading}
                        onClick={removeImage}
                        className="inline-flex h-9 items-center justify-center rounded-md border px-3 text-sm font-medium text-destructive transition hover:bg-muted disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Remove
                    </button>
                )}
            </div>

            {error && (
                <p role="alert" className="text-sm text-destructive">
                    {error}
                </p>
            )}
        </div>
    )
}
