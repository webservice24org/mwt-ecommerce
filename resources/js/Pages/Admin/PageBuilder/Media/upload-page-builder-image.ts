import type { PageBuilderImageUploadResponse } from './types'

interface UploadPageBuilderImageOptions {
    pageId: number
    file: File
    signal?: AbortSignal
}

function getXsrfToken(): string | null {
    const cookie = document.cookie.split('; ').find((item) => item.startsWith('XSRF-TOKEN='))

    if (!cookie) {
        return null
    }

    const [, token] = cookie.split('=', 2)

    if (!token) {
        return null
    }

    return decodeURIComponent(token)
}

export async function uploadPageBuilderImage({
    pageId,
    file,
    signal,
}: UploadPageBuilderImageOptions): Promise<PageBuilderImageUploadResponse> {
    const formData = new FormData()

    formData.append('image', file)

    const xsrfToken = getXsrfToken()

    const headers: HeadersInit = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    }

    if (xsrfToken) {
        headers['X-XSRF-TOKEN'] = xsrfToken
    }

    const response = await fetch(`/admin/pages/${pageId}/builder/images`, {
        method: 'POST',
        headers,
        body: formData,
        signal,
        credentials: 'same-origin',
    })

    if (!response.ok) {
        if (response.status === 422) {
            const payload = (await response.json()) as {
                message?: string
                errors?: Record<string, string[]>
            }

            const imageError = payload.errors?.image?.[0]

            throw new Error(imageError ?? payload.message ?? 'The image could not be uploaded.')
        }

        if (response.status === 419) {
            throw new Error('Your session has expired. Refresh the page and try again.')
        }

        throw new Error('The image could not be uploaded.')
    }

    return (await response.json()) as PageBuilderImageUploadResponse
}
