export function csrfToken(): string {
    const metaToken =
        document.querySelector<HTMLMetaElement>(
            'meta[name="csrf-token"]',
        )?.content

    if (metaToken) {
        return metaToken
    }

    const cookieToken =
        document.cookie
            .split('; ')
            .find((cookie) =>
                cookie.startsWith(
                    'XSRF-TOKEN=',
                ),
            )
            ?.split('=')[1]

    if (!cookieToken) {
        return ''
    }

    return decodeURIComponent(
        cookieToken,
    )
}

export function jsonRequestHeaders(): HeadersInit {
    const token =
        csrfToken()

    return {
        Accept: 'application/json',
        'Content-Type':
            'application/json',
        ...(token !== ''
            ? {
                  'X-CSRF-TOKEN':
                      token,
              }
            : {}),
    }
}