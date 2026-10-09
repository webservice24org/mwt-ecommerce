import { ArrowDown, ArrowUp, Plus, Share2, Trash2 } from 'lucide-react'
import type { ReactNode } from 'react'

import { footerSocialPlatforms, isFooterSocialPlatform } from '@/types/footer-builder'
import type { FooterSocialLink } from '@/types/footer-builder'

const MAX_SOCIAL_LINKS = 8

interface Props {
    links: FooterSocialLink[]
    disabled?: boolean

    onChange: (links: FooterSocialLink[]) => void
}

export default function SocialLinkEditor({ links, disabled = false, onChange }: Props) {
    const addLink = () => {
        if (disabled || links.length >= MAX_SOCIAL_LINKS) {
            return
        }

        onChange([
            ...links,
            {
                platform: 'facebook',

                url: '',
            },
        ])
    }

    const updateLink = (index: number, link: FooterSocialLink) => {
        if (disabled) {
            return
        }

        onChange(links.map((current, currentIndex) => (currentIndex === index ? link : current)))
    }

    const removeLink = (index: number) => {
        if (disabled) {
            return
        }

        onChange(links.filter((_link, currentIndex) => currentIndex !== index))
    }

    const moveLink = (index: number, direction: 'up' | 'down') => {
        if (disabled) {
            return
        }

        const targetIndex = direction === 'up' ? index - 1 : index + 1

        if (targetIndex < 0 || targetIndex >= links.length) {
            return
        }

        const next = [...links]

        const [moved] = next.splice(index, 1)

        if (moved === undefined) {
            return
        }

        next.splice(targetIndex, 0, moved)

        onChange(next)
    }

    return (
        <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
            <div className="flex flex-col gap-4 border-b border-neutral-200 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div className="flex min-w-0 items-start gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700">
                        <Share2 className="h-5 w-5" strokeWidth={1.9} />
                    </div>

                    <div className="min-w-0">
                        <h2 className="text-base font-semibold text-neutral-950">Social links</h2>

                        <p className="mt-1 max-w-2xl text-sm leading-6 text-neutral-500">
                            Add social profiles displayed in the shared bottom footer. You can add
                            up to eight links and control their order.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    disabled={disabled || links.length >= MAX_SOCIAL_LINKS}
                    onClick={addLink}
                    className="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <Plus className="h-4 w-4" />
                    Add social link
                </button>
            </div>

            <div className="p-5 sm:p-6">
                {links.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-neutral-300 bg-neutral-50 px-5 py-10 text-center">
                        <p className="text-sm font-semibold text-neutral-800">No social links</p>

                        <p className="mx-auto mt-1 max-w-xl text-sm leading-6 text-neutral-500">
                            Social profiles are optional. Add a link when you want a social icon to
                            appear in the footer.
                        </p>

                        {!disabled && (
                            <button
                                type="button"
                                onClick={addLink}
                                className="mt-4 inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-neutral-900 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800"
                            >
                                <Plus className="h-4 w-4" />
                                Add first link
                            </button>
                        )}
                    </div>
                ) : (
                    <div className="space-y-3">
                        {links.map((link, index) => (
                            <SocialLinkRow
                                key={index}
                                link={link}
                                index={index}
                                total={links.length}
                                disabled={disabled}
                                onChange={(value) => updateLink(index, value)}
                                onRemove={() => removeLink(index)}
                                onMove={(direction) => moveLink(index, direction)}
                            />
                        ))}
                    </div>
                )}

                <div className="mt-5 flex flex-col gap-2 text-xs text-neutral-400 sm:flex-row sm:items-center sm:justify-between">
                    <span>
                        {links.length}/{MAX_SOCIAL_LINKS} social links
                    </span>

                    <span>Social links render in the order shown here.</span>
                </div>
            </div>
        </section>
    )
}

interface SocialLinkRowProps {
    link: FooterSocialLink
    index: number
    total: number
    disabled: boolean

    onChange: (link: FooterSocialLink) => void

    onRemove: () => void

    onMove: (direction: 'up' | 'down') => void
}

function SocialLinkRow({
    link,
    index,
    total,
    disabled,
    onChange,
    onRemove,
    onMove,
}: SocialLinkRowProps) {
    return (
        <article className="min-w-0 rounded-xl border border-neutral-200 bg-neutral-50 p-4">
            <div className="grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-[minmax(0,0.55fr)_minmax(0,1.45fr)_auto] lg:items-end">
                <div className="min-w-0">
                    <label
                        htmlFor={`footer-social-${index}-platform`}
                        className="block text-xs font-medium text-neutral-600"
                    >
                        Platform
                    </label>

                    <select
                        id={`footer-social-${index}-platform`}
                        value={link.platform}
                        disabled={disabled}
                        onChange={(event) => {
                            const value = event.target.value

                            if (!isFooterSocialPlatform(value)) {
                                return
                            }

                            onChange({
                                ...link,

                                platform: value,
                            })
                        }}
                        className="mt-1.5 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none transition focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-500"
                    >
                        {footerSocialPlatforms.map((platform) => (
                            <option key={platform.value} value={platform.value}>
                                {platform.label}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="min-w-0">
                    <label
                        htmlFor={`footer-social-${index}-url`}
                        className="block text-xs font-medium text-neutral-600"
                    >
                        Profile URL
                    </label>

                    <input
                        id={`footer-social-${index}-url`}
                        type="text"
                        required
                        maxLength={2048}
                        value={link.url}
                        disabled={disabled}
                        onChange={(event) =>
                            onChange({
                                ...link,

                                url: event.target.value,
                            })
                        }
                        placeholder="https://example.com/profile"
                        className="mt-1.5 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none transition placeholder:text-neutral-400 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-500"
                    />

                    <p className="mt-1 truncate text-[11px] text-neutral-400">
                        HTTP(S), internal path, or fragment URL
                    </p>
                </div>

                <div className="flex items-center gap-1 lg:pb-5">
                    <IconButton
                        label={`Move social link ${index + 1} up`}
                        disabled={disabled || index === 0}
                        onClick={() => onMove('up')}
                    >
                        <ArrowUp className="h-4 w-4" />
                    </IconButton>

                    <IconButton
                        label={`Move social link ${index + 1} down`}
                        disabled={disabled || index === total - 1}
                        onClick={() => onMove('down')}
                    >
                        <ArrowDown className="h-4 w-4" />
                    </IconButton>

                    <IconButton
                        label={`Delete social link ${index + 1}`}
                        disabled={disabled}
                        destructive
                        onClick={onRemove}
                    >
                        <Trash2 className="h-4 w-4" />
                    </IconButton>
                </div>
            </div>
        </article>
    )
}

function IconButton({
    label,
    disabled,
    destructive = false,
    onClick,
    children,
}: {
    label: string
    disabled: boolean
    destructive?: boolean
    onClick: () => void
    children: ReactNode
}) {
    return (
        <button
            type="button"
            aria-label={label}
            title={label}
            disabled={disabled}
            onClick={onClick}
            className={[
                'inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border transition',
                'disabled:cursor-not-allowed disabled:opacity-35',
                destructive
                    ? 'border-red-200 bg-white text-red-600 hover:bg-red-50'
                    : 'border-neutral-300 bg-white text-neutral-600 hover:bg-neutral-100',
            ].join(' ')}
        >
            {children}
        </button>
    )
}
