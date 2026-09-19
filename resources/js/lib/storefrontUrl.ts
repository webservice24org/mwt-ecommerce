export function storefrontAbsoluteUrl(path: string): string {
    if (/^https?:\/\//i.test(path)) {
        return path
    }

    if (typeof window === 'undefined') {
        return path
    }

    return new URL(path, window.location.origin).toString()
}
