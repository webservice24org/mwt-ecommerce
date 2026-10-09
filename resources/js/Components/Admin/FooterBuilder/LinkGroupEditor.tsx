import { ArrowDown, ArrowUp, Link2, Plus, Trash2 } from 'lucide-react'
import type { ReactNode } from 'react'

import type { FooterLink, FooterLinkGroup } from '@/types/footer-builder'

const MAX_GROUPS = 4
const MAX_LINKS_PER_GROUP = 10

interface Props {
    groups: FooterLinkGroup[]
    disabled?: boolean

    onChange: (groups: FooterLinkGroup[]) => void
}

export default function LinkGroupEditor({ groups, disabled = false, onChange }: Props) {
    const addGroup = () => {
        if (disabled || groups.length >= MAX_GROUPS) {
            return
        }

        onChange([
            ...groups,
            {
                heading: '',
                links: [],
            },
        ])
    }

    const updateGroup = (groupIndex: number, group: FooterLinkGroup) => {
        onChange(groups.map((current, index) => (index === groupIndex ? group : current)))
    }

    const removeGroup = (groupIndex: number) => {
        if (disabled) {
            return
        }

        onChange(groups.filter((_group, index) => index !== groupIndex))
    }

    const moveGroup = (groupIndex: number, direction: 'up' | 'down') => {
        if (disabled) {
            return
        }

        const targetIndex = direction === 'up' ? groupIndex - 1 : groupIndex + 1

        if (targetIndex < 0 || targetIndex >= groups.length) {
            return
        }

        onChange(moveItem(groups, groupIndex, targetIndex))
    }

    return (
        <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
            <div className="flex flex-col gap-4 border-b border-neutral-200 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div className="flex min-w-0 items-start gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700">
                        <Link2 className="h-5 w-5" strokeWidth={1.9} />
                    </div>

                    <div className="min-w-0">
                        <h2 className="text-base font-semibold text-neutral-950">Footer links</h2>

                        <p className="mt-1 max-w-2xl text-sm leading-6 text-neutral-500">
                            Create up to four link columns and arrange their links in the order they
                            should appear.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    disabled={disabled || groups.length >= MAX_GROUPS}
                    onClick={addGroup}
                    className="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <Plus className="h-4 w-4" />
                    Add group
                </button>
            </div>

            <div className="p-5 sm:p-6">
                {groups.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-neutral-300 bg-neutral-50 px-5 py-10 text-center">
                        <p className="text-sm font-semibold text-neutral-800">
                            No footer link groups
                        </p>

                        <p className="mx-auto mt-1 max-w-lg text-sm leading-6 text-neutral-500">
                            A footer can be saved without link groups. Add one when you want
                            navigation columns in the footer.
                        </p>

                        {!disabled && (
                            <button
                                type="button"
                                onClick={addGroup}
                                className="mt-4 inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-neutral-900 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800"
                            >
                                <Plus className="h-4 w-4" />
                                Add first group
                            </button>
                        )}
                    </div>
                ) : (
                    <div className="space-y-5">
                        {groups.map((group, groupIndex) => (
                            <LinkGroupCard
                                key={groupIndex}
                                group={group}
                                groupIndex={groupIndex}
                                groupCount={groups.length}
                                disabled={disabled}
                                onChange={(value) => updateGroup(groupIndex, value)}
                                onRemove={() => removeGroup(groupIndex)}
                                onMove={(direction) => moveGroup(groupIndex, direction)}
                            />
                        ))}
                    </div>
                )}

                <div className="mt-5 flex flex-col gap-2 text-xs text-neutral-400 sm:flex-row sm:items-center sm:justify-between">
                    <span>
                        {groups.length}/{MAX_GROUPS} groups
                    </span>

                    <span>Groups and links render in the order shown here.</span>
                </div>
            </div>
        </section>
    )
}

interface LinkGroupCardProps {
    group: FooterLinkGroup
    groupIndex: number
    groupCount: number
    disabled: boolean

    onChange: (group: FooterLinkGroup) => void

    onRemove: () => void

    onMove: (direction: 'up' | 'down') => void
}

function LinkGroupCard({
    group,
    groupIndex,
    groupCount,
    disabled,
    onChange,
    onRemove,
    onMove,
}: LinkGroupCardProps) {
    const addLink = () => {
        if (disabled || group.links.length >= MAX_LINKS_PER_GROUP) {
            return
        }

        onChange({
            ...group,

            links: [
                ...group.links,
                {
                    label: '',
                    url: '',
                },
            ],
        })
    }

    const updateLink = (linkIndex: number, link: FooterLink) => {
        onChange({
            ...group,

            links: group.links.map((current, index) => (index === linkIndex ? link : current)),
        })
    }

    const removeLink = (linkIndex: number) => {
        if (disabled) {
            return
        }

        onChange({
            ...group,

            links: group.links.filter((_link, index) => index !== linkIndex),
        })
    }

    const moveLink = (linkIndex: number, direction: 'up' | 'down') => {
        if (disabled) {
            return
        }

        const targetIndex = direction === 'up' ? linkIndex - 1 : linkIndex + 1

        if (targetIndex < 0 || targetIndex >= group.links.length) {
            return
        }

        onChange({
            ...group,

            links: moveItem(group.links, linkIndex, targetIndex),
        })
    }

    return (
        <article className="min-w-0 overflow-hidden rounded-xl border border-neutral-200">
            <div className="flex flex-col gap-4 bg-neutral-50 p-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0 flex-1">
                    <label
                        htmlFor={`footer-link-group-${groupIndex}-heading`}
                        className="block text-xs font-bold uppercase tracking-[0.12em] text-neutral-400"
                    >
                        Group {groupIndex + 1}
                    </label>

                    <input
                        id={`footer-link-group-${groupIndex}-heading`}
                        type="text"
                        required
                        maxLength={100}
                        disabled={disabled}
                        value={group.heading}
                        onChange={(event) =>
                            onChange({
                                ...group,

                                heading: event.target.value,
                            })
                        }
                        placeholder="e.g. Customer Service"
                        className="mt-2 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm font-medium text-neutral-900 outline-none transition placeholder:font-normal placeholder:text-neutral-400 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-500"
                    />

                    <p className="mt-1 text-right text-xs text-neutral-400">
                        {group.heading.length}
                        /100
                    </p>
                </div>

                <div className="flex shrink-0 items-center gap-1">
                    <IconButton
                        label={`Move group ${groupIndex + 1} up`}
                        disabled={disabled || groupIndex === 0}
                        onClick={() => onMove('up')}
                    >
                        <ArrowUp className="h-4 w-4" />
                    </IconButton>

                    <IconButton
                        label={`Move group ${groupIndex + 1} down`}
                        disabled={disabled || groupIndex === groupCount - 1}
                        onClick={() => onMove('down')}
                    >
                        <ArrowDown className="h-4 w-4" />
                    </IconButton>

                    <IconButton
                        label={`Delete group ${groupIndex + 1}`}
                        disabled={disabled}
                        destructive
                        onClick={onRemove}
                    >
                        <Trash2 className="h-4 w-4" />
                    </IconButton>
                </div>
            </div>

            <div className="p-4">
                <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 className="text-sm font-semibold text-neutral-900">Links</h3>

                        <p className="mt-1 text-xs text-neutral-500">
                            Up to {MAX_LINKS_PER_GROUP} links in this group.
                        </p>
                    </div>

                    <button
                        type="button"
                        disabled={disabled || group.links.length >= MAX_LINKS_PER_GROUP}
                        onClick={addLink}
                        className="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-3 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <Plus className="h-4 w-4" />
                        Add link
                    </button>
                </div>

                {group.links.length === 0 ? (
                    <div className="rounded-lg border border-dashed border-neutral-300 bg-neutral-50 px-4 py-6 text-center text-sm text-neutral-500">
                        This group has no links yet.
                    </div>
                ) : (
                    <div className="space-y-3">
                        {group.links.map((link, linkIndex) => (
                            <LinkRow
                                key={linkIndex}
                                link={link}
                                groupIndex={groupIndex}
                                linkIndex={linkIndex}
                                linkCount={group.links.length}
                                disabled={disabled}
                                onChange={(value) => updateLink(linkIndex, value)}
                                onRemove={() => removeLink(linkIndex)}
                                onMove={(direction) => moveLink(linkIndex, direction)}
                            />
                        ))}
                    </div>
                )}

                <p className="mt-3 text-right text-xs text-neutral-400">
                    {group.links.length}/{MAX_LINKS_PER_GROUP} links
                </p>
            </div>
        </article>
    )
}

interface LinkRowProps {
    link: FooterLink
    groupIndex: number
    linkIndex: number
    linkCount: number
    disabled: boolean

    onChange: (link: FooterLink) => void

    onRemove: () => void

    onMove: (direction: 'up' | 'down') => void
}

function LinkRow({
    link,
    groupIndex,
    linkIndex,
    linkCount,
    disabled,
    onChange,
    onRemove,
    onMove,
}: LinkRowProps) {
    return (
        <div className="min-w-0 rounded-lg border border-neutral-200 bg-neutral-50 p-3">
            <div className="grid min-w-0 grid-cols-1 gap-3 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)_auto] lg:items-end">
                <div className="min-w-0">
                    <label
                        htmlFor={`footer-link-${groupIndex}-${linkIndex}-label`}
                        className="block text-xs font-medium text-neutral-600"
                    >
                        Label
                    </label>

                    <input
                        id={`footer-link-${groupIndex}-${linkIndex}-label`}
                        type="text"
                        required
                        maxLength={120}
                        disabled={disabled}
                        value={link.label}
                        onChange={(event) =>
                            onChange({
                                ...link,

                                label: event.target.value,
                            })
                        }
                        placeholder="e.g. Contact Us"
                        className="mt-1.5 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none transition placeholder:text-neutral-400 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-500"
                    />

                    <p className="mt-1 text-right text-[11px] text-neutral-400">
                        {link.label.length}
                        /120
                    </p>
                </div>

                <div className="min-w-0">
                    <label
                        htmlFor={`footer-link-${groupIndex}-${linkIndex}-url`}
                        className="block text-xs font-medium text-neutral-600"
                    >
                        URL
                    </label>

                    <input
                        id={`footer-link-${groupIndex}-${linkIndex}-url`}
                        type="text"
                        required
                        maxLength={2048}
                        disabled={disabled}
                        value={link.url}
                        onChange={(event) =>
                            onChange({
                                ...link,

                                url: event.target.value,
                            })
                        }
                        placeholder="/contact, #newsletter, or https://example.com"
                        className="mt-1.5 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none transition placeholder:text-neutral-400 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-500"
                    />

                    <p className="mt-1 truncate text-[11px] text-neutral-400">
                        Internal path, fragment, or HTTP(S) URL
                    </p>
                </div>

                <div className="flex items-center gap-1 lg:pb-5">
                    <IconButton
                        label={`Move link ${linkIndex + 1} up`}
                        disabled={disabled || linkIndex === 0}
                        onClick={() => onMove('up')}
                    >
                        <ArrowUp className="h-4 w-4" />
                    </IconButton>

                    <IconButton
                        label={`Move link ${linkIndex + 1} down`}
                        disabled={disabled || linkIndex === linkCount - 1}
                        onClick={() => onMove('down')}
                    >
                        <ArrowDown className="h-4 w-4" />
                    </IconButton>

                    <IconButton
                        label={`Delete link ${linkIndex + 1}`}
                        disabled={disabled}
                        destructive
                        onClick={onRemove}
                    >
                        <Trash2 className="h-4 w-4" />
                    </IconButton>
                </div>
            </div>
        </div>
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

function moveItem<T>(items: T[], from: number, to: number): T[] {
    const result = [...items]

    const [moved] = result.splice(from, 1)

    if (moved === undefined) {
        return items
    }

    result.splice(to, 0, moved)

    return result
}
