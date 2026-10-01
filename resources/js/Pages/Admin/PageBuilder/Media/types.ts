export interface PageBuilderImage {
    path: string
    url: string
    original_name: string
    mime_type: string
    file_size: number
    width: number | null
    height: number | null
}

export interface PageBuilderImageUploadResponse {
    image: PageBuilderImage
}
