function normalizeUrlValue(value: string | null): string | null {
    if (value === null) {
        return null
    }

    const normalized = value.trim()

    if (normalized === '' || containsUnsafeCharacters(normalized)) {
        return null
    }

    return normalized
}

export function getSafeFeatureLinkUrl(value: string | null): string | null {
    const normalized = normalizeUrlValue(value)

    if (!normalized) {
        return null
    }

    /*
     * Same-page anchors are useful for things
     * like FAQ, shipping, or policy sections.
     */
    if (normalized.startsWith('#')) {
        return normalized.length > 1 ? normalized : null
    }

    if (isSafeRootRelativeUrl(normalized)) {
        return normalized
    }

    return getSafeAbsoluteHttpUrl(normalized)
}

export function getSafeFeatureImageUrl(value: string | null): string | null {
    const normalized = normalizeUrlValue(value)

    if (!normalized) {
        return null
    }

    if (isSafeRootRelativeUrl(normalized)) {
        return normalized
    }

    return getSafeAbsoluteHttpUrl(normalized)
}

function isSafeRootRelativeUrl(value: string): boolean {
    return value.startsWith('/') && !value.startsWith('//')
}

function getSafeAbsoluteHttpUrl(value: string): string | null {
    try {
        const url = new URL(value)

        if (url.protocol !== 'http:' && url.protocol !== 'https:') {
            return null
        }

        /*
         * Embedded credentials have no place
         * in Page Builder content URLs.
         */
        if (url.username !== '' || url.password !== '') {
            return null
        }

        return value
    } catch {
        return null
    }
}

function containsUnsafeCharacters(value: string): boolean {
    /*
     * Raw whitespace/control characters and
     * backslashes are rejected. URLs should
     * use normal URL encoding instead.
     */
    return (
        /\s/u.test(value) ||
        value.includes('\\') ||
        Array.from(value).some((character) => {
            const code = character.charCodeAt(0)

            return code <= 31 || code === 127
        })
    )
}
