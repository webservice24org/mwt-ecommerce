import { footerSocialPlatforms } from '@/types/footer-builder'
import type {
    FooterCopyrightConfig,
    FooterDeveloperConfig,
    FooterPreviewDevice,
    FooterSocialLink,
    FooterSocialPlatform,
    FooterTemplateKey,
} from '@/types/footer-builder'

import type { ReactNode } from 'react'

type FooterRenderMode = 'preview' | 'storefront'

interface Props {
    template: FooterTemplateKey

    copyright: FooterCopyrightConfig

    socialLinks: FooterSocialLink[]

    developer: FooterDeveloperConfig

    brandName: string

    /**
     * Admin preview supplies an explicit simulated
     * device width.
     *
     * Storefront rendering omits this value and
     * uses normal responsive Tailwind breakpoints.
     */
    device?: FooterPreviewDevice

    /**
     * preview:
     * - visually accurate
     * - no navigation
     *
     * storefront:
     * - real social links
     * - real developer link
     */
    mode: FooterRenderMode

    /**
     * Keeps the bottom background full width while
     * allowing the actual content to use the same
     * max-width container as the storefront footer.
     */
    contentClassName?: string
}

interface FooterBottomTheme {
    border: string

    copyright: string

    copyrightName: string

    social: string

    socialBorder: string

    socialBackground: string

    socialHover: string

    socialFocus: string

    developer: string

    developerAccent: string

    developerFocus: string
}

const themes: Record<FooterTemplateKey, FooterBottomTheme> = {
    luxe_newsletter: {
        border: 'border-neutral-800',

        copyright: 'text-neutral-400',

        copyrightName: 'text-neutral-200',

        social: 'text-neutral-400',

        socialBorder: 'border-neutral-800',

        socialBackground: 'bg-neutral-900',

        socialHover: 'hover:text-indigo-400 hover:border-indigo-500/40',

        socialFocus: 'focus-visible:ring-indigo-400',

        developer: 'text-neutral-400',

        developerAccent: 'text-indigo-400',

        developerFocus: 'focus-visible:ring-indigo-400',
    },

    minimal_localized: {
        border: 'border-neutral-900',

        copyright: 'text-neutral-500',

        copyrightName: 'text-neutral-300',

        social: 'text-neutral-400',

        socialBorder: 'border-transparent',

        socialBackground: 'bg-transparent',

        socialHover: 'hover:text-emerald-400',

        socialFocus: 'focus-visible:ring-emerald-400',

        developer: 'text-neutral-500',

        developerAccent: 'text-emerald-400',

        developerFocus: 'focus-visible:ring-emerald-400',
    },

    marketplace_trust: {
        border: 'border-neutral-800',

        copyright: 'text-neutral-400',

        copyrightName: 'text-amber-400',

        social: 'text-neutral-400',

        socialBorder: 'border-neutral-800',

        socialBackground: 'bg-neutral-900',

        socialHover: 'hover:text-amber-400 hover:border-amber-500/40',

        socialFocus: 'focus-visible:ring-amber-400',

        developer: 'text-neutral-400',

        developerAccent: 'text-amber-400',

        developerFocus: 'focus-visible:ring-amber-400',
    },
}

export default function SharedBottomFooter({
    template,
    copyright,
    socialLinks,
    developer,
    brandName,
    device,
    mode,
    contentClassName = '',
}: Props) {
    const theme = themes[template]

    const previewMobile = device === 'mobile'

    const previewTablet = device === 'tablet'

    const hasPreviewDevice = device !== undefined

    const paddingClass = previewMobile
        ? 'px-4 py-5'
        : previewTablet
          ? 'px-6 py-6'
          : hasPreviewDevice
            ? 'px-10 py-6'
            : 'px-4 py-5 sm:px-6 md:py-6'

    const layoutClass = previewMobile
        ? 'flex flex-col items-center gap-4 text-center'
        : hasPreviewDevice
          ? 'grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)] items-center gap-4'
          : [
                'flex flex-col items-center gap-4 text-center',
                'md:grid md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)] md:items-center',
            ].join(' ')

    const copyrightAlignment = previewMobile
        ? 'text-center'
        : hasPreviewDevice
          ? 'text-left'
          : 'text-center md:text-left'

    const developerAlignment = previewMobile
        ? 'justify-center text-center'
        : hasPreviewDevice
          ? 'justify-end text-right'
          : ['justify-center text-center', 'md:justify-end md:text-right'].join(' ')

    return (
        <div
            data-footer-bottom
            className={[
                'w-full min-w-0 max-w-full overflow-x-clip border-t bg-neutral-950',
                theme.border,
                paddingClass,
            ].join(' ')}
        >
            <div
                className={['w-full min-w-0 max-w-full', contentClassName]
                    .filter(Boolean)
                    .join(' ')}
            >
                <div className={['w-full min-w-0 max-w-full text-xs', layoutClass].join(' ')}>
                    <CopyrightArea
                        copyright={copyright}
                        brandName={brandName}
                        className={copyrightAlignment}
                        theme={theme}
                    />

                    <SocialArea
                        template={template}
                        socialLinks={socialLinks}
                        mode={mode}
                        theme={theme}
                    />

                    <DeveloperArea
                        developer={developer}
                        mode={mode}
                        className={developerAlignment}
                        theme={theme}
                    />
                </div>
            </div>
        </div>
    )
}

function CopyrightArea({
    copyright,
    brandName,
    className,
    theme,
}: {
    copyright: FooterCopyrightConfig

    brandName: string

    className: string

    theme: FooterBottomTheme
}) {
    const name =
        copyright.name.trim() !== ''
            ? copyright.name
            : brandName.trim() !== ''
              ? brandName
              : 'Store'

    const suffix = copyright.suffix.trim()

    return (
        <p
            className={[
                'min-w-0 max-w-full [overflow-wrap:anywhere] leading-5',
                className,
                theme.copyright,
            ].join(' ')}
        >
            <span aria-hidden="true">©</span>
            <span className="sr-only">Copyright</span> {new Date().getFullYear()}{' '}
            <span
                className={[
                    'max-w-full [overflow-wrap:anywhere] font-bold',
                    theme.copyrightName,
                ].join(' ')}
            >
                {name}
            </span>
            {suffix !== '' && (
                <>
                    {' '}
                    <span className="max-w-full [overflow-wrap:anywhere]">{suffix}</span>
                </>
            )}
        </p>
    )
}

function SocialArea({
    template,
    socialLinks,
    mode,
    theme,
}: {
    template: FooterTemplateKey

    socialLinks: FooterSocialLink[]

    mode: FooterRenderMode

    theme: FooterBottomTheme
}) {
    if (socialLinks.length === 0) {
        /*
         * Preserve the middle column so the
         * copyright and developer areas remain
         * aligned on desktop/tablet.
         */
        return <div aria-hidden="true" className="min-h-1 min-w-0 max-w-full" />
    }

    if (template === 'minimal_localized') {
        return (
            <SocialContainer mode={mode}>
                {socialLinks.map((social, index) => (
                    <MinimalSocialItem
                        key={`${social.platform}-${index}`}
                        social={social}
                        showSeparator={index > 0}
                        mode={mode}
                        theme={theme}
                    />
                ))}
            </SocialContainer>
        )
    }

    return (
        <SocialContainer mode={mode} boxed>
            {socialLinks.map((social, index) => (
                <BoxedSocialItem
                    key={`${social.platform}-${index}`}
                    social={social}
                    mode={mode}
                    theme={theme}
                />
            ))}
        </SocialContainer>
    )
}

function SocialContainer({
    mode,
    boxed = false,
    children,
}: {
    mode: FooterRenderMode

    boxed?: boolean

    children: ReactNode
}) {
    const className = [
        'flex min-w-0 max-w-full flex-wrap items-center justify-center',
        boxed ? 'gap-2' : 'gap-x-3 gap-y-2',
    ].join(' ')

    if (mode === 'storefront') {
        return (
            <nav aria-label="Footer social links" className="min-w-0 max-w-full">
                <ul className={className}>{children}</ul>
            </nav>
        )
    }

    return (
        <div aria-label="Footer social links preview" className={className}>
            {children}
        </div>
    )
}

function MinimalSocialItem({
    social,
    showSeparator,
    mode,
    theme,
}: {
    social: FooterSocialLink

    showSeparator: boolean

    mode: FooterRenderMode

    theme: FooterBottomTheme
}) {
    const label = socialPlatformLabel(social.platform)

    if (mode === 'storefront') {
        return (
            <li className="inline-flex min-w-0 max-w-full items-center gap-3">
                {showSeparator && (
                    <span aria-hidden="true" className="shrink-0 text-neutral-800">
                        •
                    </span>
                )}

                <a
                    href={social.url}
                    aria-label={`Follow us on ${label}`}
                    className={[
                        'min-w-0 max-w-full [overflow-wrap:anywhere] rounded-sm font-medium transition-colors',
                        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-950',
                        theme.social,
                        theme.socialHover,
                        theme.socialFocus,
                    ].join(' ')}
                >
                    {label}
                </a>
            </li>
        )
    }

    return (
        <span className="inline-flex min-w-0 max-w-full items-center gap-3">
            {showSeparator && (
                <span aria-hidden="true" className="shrink-0 text-neutral-800">
                    •
                </span>
            )}

            <span
                title={label}
                className={[
                    'min-w-0 max-w-full [overflow-wrap:anywhere] font-medium',
                    theme.social,
                ].join(' ')}
            >
                {label}
            </span>
        </span>
    )
}

function BoxedSocialItem({
    social,
    mode,
    theme,
}: {
    social: FooterSocialLink

    mode: FooterRenderMode

    theme: FooterBottomTheme
}) {
    const label = socialPlatformLabel(social.platform)

    const mark = socialPlatformMark(social.platform)

    const className = [
        'inline-flex h-8 min-w-8 max-w-full shrink-0 items-center justify-center rounded-xl border px-2',
        'transition-colors',
        theme.social,
        theme.socialBorder,
        theme.socialBackground,

        mode === 'storefront'
            ? [
                  theme.socialHover,
                  theme.socialFocus,
                  'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-950',
              ].join(' ')
            : '',
    ]
        .filter(Boolean)
        .join(' ')

    if (mode === 'storefront') {
        return (
            <li className="max-w-full">
                <a
                    href={social.url}
                    aria-label={`Follow us on ${label}`}
                    title={label}
                    className={className}
                >
                    <span aria-hidden="true" className="max-w-full text-[9px] font-bold uppercase">
                        {mark}
                    </span>
                </a>
            </li>
        )
    }

    return (
        <span title={label} className={className}>
            <span aria-hidden="true" className="max-w-full text-[9px] font-bold uppercase">
                {mark}
            </span>

            <span className="sr-only">{label}</span>
        </span>
    )
}

function DeveloperArea({
    developer,
    mode,
    className,
    theme,
}: {
    developer: FooterDeveloperConfig

    mode: FooterRenderMode

    className: string

    theme: FooterBottomTheme
}) {
    const prefix = developer.prefix.trim() !== '' ? developer.prefix : 'Created by'

    const name = developer.name.trim() !== '' ? developer.name : 'Developer'

    const url = developer.url.trim()

    const canLink = mode === 'storefront' && url !== ''

    return (
        <div
            className={[
                'flex min-w-0 max-w-full items-center gap-2 font-mono text-[10px] leading-5',
                className,
                theme.developer,
            ].join(' ')}
        >
            <LiveStatusDot />

            <span className="min-w-0 max-w-full [overflow-wrap:anywhere]">
                {prefix}{' '}
                {canLink ? (
                    <a
                        href={url}
                        className={[
                            'max-w-full [overflow-wrap:anywhere] rounded-sm font-bold hover:underline',
                            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-950',
                            theme.developerAccent,
                            theme.developerFocus,
                        ].join(' ')}
                    >
                        {name}
                    </a>
                ) : (
                    <span
                        className={[
                            'max-w-full [overflow-wrap:anywhere] font-bold',
                            theme.developerAccent,
                        ].join(' ')}
                    >
                        {name}
                    </span>
                )}
            </span>
        </div>
    )
}

function LiveStatusDot() {
    return (
        <span aria-hidden="true" className="relative inline-flex h-2 w-2 shrink-0">
            <span className="absolute inset-0 rounded-full bg-emerald-400 opacity-75 motion-safe:animate-ping" />

            <span className="relative inline-flex h-2 w-2 rounded-full bg-emerald-500" />
        </span>
    )
}

function socialPlatformLabel(platform: FooterSocialPlatform): string {
    return footerSocialPlatforms.find((option) => option.value === platform)?.label ?? platform
}

function socialPlatformMark(platform: FooterSocialPlatform): string {
    switch (platform) {
        case 'facebook':
            return 'FB'

        case 'instagram':
            return 'IG'

        case 'x':
            return 'X'

        case 'tiktok':
            return 'TT'

        case 'youtube':
            return 'YT'

        case 'pinterest':
            return 'P'

        case 'linkedin':
            return 'IN'
    }
}
