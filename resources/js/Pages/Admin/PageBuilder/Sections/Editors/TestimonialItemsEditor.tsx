import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react'

import PageBuilderImageField from '../../Media/PageBuilderImageField'

const MAX_ITEMS = 12
const MAX_QUOTE_LENGTH = 2000
const MAX_NAME_LENGTH = 120
const MAX_ROLE_LENGTH = 180
const MAX_BADGE_LENGTH = 120
const MAX_IMAGE_ALT_LENGTH = 255

export type TestimonialItem = {
    quote: string
    name: string
    role: string | null
    rating: number | null

    image: string | null
    image_alt: string | null

    badge: string | null
}

interface Props {
    pageId: number

    items: TestimonialItem[]

    showBadge: boolean

    onChange: (items: TestimonialItem[]) => void
}

export default function TestimonialItemsEditor({ pageId, items, showBadge, onChange }: Props) {
    const updateItem = (index: number, overrides: Partial<TestimonialItem>) => {
        const nextItems = items.map((item, itemIndex) =>
            itemIndex === index
                ? {
                      ...item,
                      ...overrides,
                  }
                : item,
        )

        onChange(nextItems)
    }

    const addItem = () => {
        if (items.length >= MAX_ITEMS) {
            return
        }

        const nextItem: TestimonialItem = {
            quote: '',
            name: '',
            role: null,
            rating: 5,

            image: null,
            image_alt: null,

            badge: showBadge ? `Featured Case Study #${items.length + 1}` : null,
        }

        onChange([...items, nextItem])
    }

    const removeItem = (index: number) => {
        /*
         * The server schema requires at
         * least one testimonial.
         */
        if (items.length <= 1) {
            return
        }

        onChange(items.filter((_item, itemIndex) => itemIndex !== index))
    }

    const moveItem = (index: number, direction: 'up' | 'down') => {
        const targetIndex = direction === 'up' ? index - 1 : index + 1

        if (targetIndex < 0 || targetIndex >= items.length) {
            return
        }

        const nextItems = [...items]

        const removed = nextItems.splice(index, 1)[0]

        if (!removed) {
            return
        }

        nextItems.splice(targetIndex, 0, removed)

        onChange(nextItems)
    }

    return (
        <section className="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800 sm:p-5">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        Testimonials
                    </h3>

                    <p className="mt-1 max-w-2xl text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        Add customer testimonials, upload avatars, and control the order used by the
                        storefront slider.
                    </p>
                </div>

                <button
                    type="button"
                    disabled={items.length >= MAX_ITEMS}
                    onClick={addItem}
                    className={[
                        'inline-flex min-h-9 shrink-0 items-center justify-center gap-2',
                        'rounded-lg border border-neutral-300 px-3 py-2',
                        'text-sm font-medium text-neutral-700',
                        'transition hover:bg-neutral-50',
                        'disabled:cursor-not-allowed disabled:opacity-50',
                        'dark:border-neutral-700 dark:text-neutral-200',
                        'dark:hover:bg-neutral-900',
                    ].join(' ')}
                >
                    <Plus aria-hidden="true" className="h-4 w-4" />
                    Add testimonial
                </button>
            </div>

            <div className="mt-4 flex items-center justify-between gap-3">
                <p className="text-xs text-neutral-500 dark:text-neutral-400">
                    {items.length}/{MAX_ITEMS} testimonials
                </p>

                {items.length >= MAX_ITEMS && (
                    <p className="text-xs font-medium text-amber-600 dark:text-amber-400">
                        Maximum reached
                    </p>
                )}
            </div>

            <div className="mt-5 space-y-5">
                {items.map((item, index) => (
                    <article
                        key={index}
                        className="overflow-hidden rounded-xl border border-neutral-200 bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-950/40"
                    >
                        <header className="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 bg-white px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950 sm:px-5">
                            <div className="min-w-0">
                                <h4 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                    Testimonial {index + 1}
                                </h4>

                                <p className="mt-0.5 truncate text-xs text-neutral-500 dark:text-neutral-400">
                                    {item.name.trim() !== '' ? item.name : 'New testimonial'}
                                </p>
                            </div>

                            <div className="flex shrink-0 items-center gap-1">
                                <IconButton
                                    label={`Move testimonial ${index + 1} up`}
                                    disabled={index === 0}
                                    onClick={() => moveItem(index, 'up')}
                                >
                                    <ArrowUp className="h-4 w-4" />
                                </IconButton>

                                <IconButton
                                    label={`Move testimonial ${index + 1} down`}
                                    disabled={index === items.length - 1}
                                    onClick={() => moveItem(index, 'down')}
                                >
                                    <ArrowDown className="h-4 w-4" />
                                </IconButton>

                                <IconButton
                                    label={`Remove testimonial ${index + 1}`}
                                    destructive
                                    disabled={items.length <= 1}
                                    onClick={() => removeItem(index)}
                                >
                                    <Trash2 className="h-4 w-4" />
                                </IconButton>
                            </div>
                        </header>

                        <div className="space-y-6 p-4 sm:p-5">
                            {showBadge && (
                                <TextField
                                    id={`testimonial-${index}-badge`}
                                    label="Badge"
                                    value={item.badge ?? ''}
                                    maxLength={MAX_BADGE_LENGTH}
                                    placeholder={`Featured Case Study #${index + 1}`}
                                    optional
                                    onChange={(value) =>
                                        updateItem(index, {
                                            badge: nullableString(value),
                                        })
                                    }
                                />
                            )}

                            <TextAreaField
                                id={`testimonial-${index}-quote`}
                                label="Quote"
                                value={item.quote}
                                maxLength={MAX_QUOTE_LENGTH}
                                rows={5}
                                onChange={(value) =>
                                    updateItem(index, {
                                        quote: value,
                                    })
                                }
                            />

                            <div className="grid gap-5 sm:grid-cols-2">
                                <TextField
                                    id={`testimonial-${index}-name`}
                                    label="Customer name"
                                    value={item.name}
                                    maxLength={MAX_NAME_LENGTH}
                                    placeholder="Customer name"
                                    onChange={(value) =>
                                        updateItem(index, {
                                            name: value,
                                        })
                                    }
                                />

                                <TextField
                                    id={`testimonial-${index}-role`}
                                    label="Role / company"
                                    value={item.role ?? ''}
                                    maxLength={MAX_ROLE_LENGTH}
                                    placeholder="CEO, Example Company"
                                    optional
                                    onChange={(value) =>
                                        updateItem(index, {
                                            role: nullableString(value),
                                        })
                                    }
                                />
                            </div>

                            <SelectField
                                id={`testimonial-${index}-rating`}
                                label="Rating"
                                value={item.rating === null ? '' : String(item.rating)}
                                options={[
                                    {
                                        value: '',
                                        label: 'No rating',
                                    },
                                    {
                                        value: '5',
                                        label: '5 stars',
                                    },
                                    {
                                        value: '4',
                                        label: '4 stars',
                                    },
                                    {
                                        value: '3',
                                        label: '3 stars',
                                    },
                                    {
                                        value: '2',
                                        label: '2 stars',
                                    },
                                    {
                                        value: '1',
                                        label: '1 star',
                                    },
                                ]}
                                onChange={(value) =>
                                    updateItem(index, {
                                        rating: value === '' ? null : Number(value),
                                    })
                                }
                            />

                            <div className="border-t border-neutral-200 pt-6 dark:border-neutral-800">
                                <div className="mb-4">
                                    <h5 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Customer avatar
                                    </h5>

                                    <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                        Upload an optional customer photo. A square image works best
                                        because the storefront designs display avatars as circles.
                                    </p>
                                </div>

                                <div className="space-y-5">
                                    <PageBuilderImageField
                                        pageId={pageId}
                                        label="Avatar image"
                                        value={item.image}
                                        helpText="JPEG, PNG, or WebP. The existing Page Builder media uploader is reused."
                                        onChange={(image) => {
                                            if (image === null) {
                                                updateItem(index, {
                                                    image: null,

                                                    image_alt: null,
                                                })

                                                return
                                            }

                                            updateItem(index, {
                                                image,
                                            })
                                        }}
                                    />

                                    {item.image && (
                                        <TextField
                                            id={`testimonial-${index}-image-alt`}
                                            label="Avatar alt text"
                                            value={item.image_alt ?? ''}
                                            maxLength={MAX_IMAGE_ALT_LENGTH}
                                            placeholder={
                                                item.name.trim() !== ''
                                                    ? `${item.name} portrait`
                                                    : 'Customer portrait'
                                            }
                                            optional
                                            onChange={(value) =>
                                                updateItem(index, {
                                                    image_alt: nullableString(value),
                                                })
                                            }
                                        />
                                    )}
                                </div>
                            </div>
                        </div>
                    </article>
                ))}
            </div>

            <p className="mt-5 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                At least one testimonial must remain. The server contract allows a maximum of twelve
                testimonials.
            </p>
        </section>
    )
}

interface IconButtonProps {
    label: string
    disabled?: boolean
    destructive?: boolean
    children: React.ReactNode

    onClick: () => void
}

function IconButton({
    label,
    disabled = false,
    destructive = false,
    children,
    onClick,
}: IconButtonProps) {
    return (
        <button
            type="button"
            aria-label={label}
            title={label}
            disabled={disabled}
            onClick={onClick}
            className={[
                'inline-flex h-9 w-9 items-center justify-center rounded-lg border',
                'transition',
                'focus-visible:outline-none focus-visible:ring-2',
                'focus-visible:ring-neutral-400',
                'disabled:cursor-not-allowed disabled:opacity-40',

                destructive
                    ? [
                          'border-red-200 text-red-600',
                          'hover:bg-red-50',
                          'dark:border-red-900/60 dark:text-red-400',
                          'dark:hover:bg-red-950/30',
                      ].join(' ')
                    : [
                          'border-neutral-200 text-neutral-600',
                          'hover:bg-neutral-100',
                          'dark:border-neutral-800 dark:text-neutral-300',
                          'dark:hover:bg-neutral-900',
                      ].join(' '),
            ].join(' ')}
        >
            {children}
        </button>
    )
}

interface TextFieldProps {
    id: string
    label: string
    value: string
    maxLength: number

    placeholder?: string
    optional?: boolean

    onChange: (value: string) => void
}

function TextField({
    id,
    label,
    value,
    maxLength,
    placeholder,
    optional = false,
    onChange,
}: TextFieldProps) {
    return (
        <div>
            <div className="flex items-center justify-between gap-3">
                <label
                    htmlFor={id}
                    className="text-sm font-medium text-neutral-900 dark:text-neutral-100"
                >
                    {label}

                    {optional && (
                        <span className="ml-1 font-normal text-neutral-400">(optional)</span>
                    )}
                </label>

                <span className="shrink-0 text-xs text-neutral-400">
                    {value.length}/{maxLength}
                </span>
            </div>

            <input
                id={id}
                type="text"
                value={value}
                maxLength={maxLength}
                placeholder={placeholder}
                onChange={(event) => onChange(event.target.value)}
                className="mt-2 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
            />
        </div>
    )
}

interface TextAreaFieldProps {
    id: string
    label: string
    value: string
    maxLength: number
    rows: number

    onChange: (value: string) => void
}

function TextAreaField({ id, label, value, maxLength, rows, onChange }: TextAreaFieldProps) {
    return (
        <div>
            <div className="flex items-center justify-between gap-3">
                <label
                    htmlFor={id}
                    className="text-sm font-medium text-neutral-900 dark:text-neutral-100"
                >
                    {label}
                </label>

                <span className="shrink-0 text-xs text-neutral-400">
                    {value.length}/{maxLength}
                </span>
            </div>

            <textarea
                id={id}
                value={value}
                rows={rows}
                maxLength={maxLength}
                onChange={(event) => onChange(event.target.value)}
                className="mt-2 w-full resize-y rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm leading-6 text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
            />
        </div>
    )
}

interface SelectFieldProps {
    id: string
    label: string
    value: string

    options: Array<{
        value: string
        label: string
    }>

    onChange: (value: string) => void
}

function SelectField({ id, label, value, options, onChange }: SelectFieldProps) {
    return (
        <div>
            <label
                htmlFor={id}
                className="text-sm font-medium text-neutral-900 dark:text-neutral-100"
            >
                {label}
            </label>

            <select
                id={id}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="mt-2 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </div>
    )
}

function nullableString(value: string): string | null {
    return value === '' ? null : value
}
