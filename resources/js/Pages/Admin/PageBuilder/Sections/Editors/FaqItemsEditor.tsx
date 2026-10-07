import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react'
import type { ReactNode } from 'react'

export type FaqItem = {
    question: string
    answer: string
}

interface Props {
    items: FaqItem[]
    onChange: (items: FaqItem[]) => void
}

const MAX_ITEMS = 16

const MAX_QUESTION_LENGTH = 240
const MAX_ANSWER_LENGTH = 3000

export default function FaqItemsEditor({ items, onChange }: Props) {
    const addItem = () => {
        if (items.length >= MAX_ITEMS) {
            return
        }

        onChange([
            ...items,
            {
                question: 'New FAQ question',

                answer: 'Add the answer for this FAQ item.',
            },
        ])
    }

    const removeItem = (index: number) => {
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

        const current = nextItems[index]

        const target = nextItems[targetIndex]

        if (!current || !target) {
            return
        }

        nextItems[index] = target

        nextItems[targetIndex] = current

        onChange(nextItems)
    }

    const updateItem = (index: number, patch: Partial<FaqItem>) => {
        onChange(
            items.map((item, itemIndex) =>
                itemIndex === index
                    ? {
                          ...item,
                          ...patch,
                      }
                    : item,
            ),
        )
    }

    return (
        <div className="space-y-4">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        FAQ Items
                    </p>

                    <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        Add, edit, remove, and reorder FAQ questions.
                    </p>
                </div>

                <button
                    type="button"
                    onClick={addItem}
                    disabled={items.length >= MAX_ITEMS}
                    className={[
                        'inline-flex min-h-10 items-center justify-center gap-2',
                        'rounded-lg border border-neutral-300',
                        'bg-white px-4 py-2',
                        'text-sm font-medium text-neutral-800',
                        'transition hover:bg-neutral-50',
                        'focus-visible:outline-none',
                        'focus-visible:ring-2 focus-visible:ring-neutral-400',
                        'disabled:cursor-not-allowed disabled:opacity-50',
                        'dark:border-neutral-700',
                        'dark:bg-neutral-950',
                        'dark:text-neutral-100',
                        'dark:hover:bg-neutral-900',
                    ].join(' ')}
                >
                    <Plus aria-hidden="true" className="h-4 w-4" />
                    Add FAQ
                </button>
            </div>

            <div className="flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400">
                <span>
                    {items.length} FAQ
                    {items.length === 1 ? '' : 's'}
                </span>

                <span>Maximum {MAX_ITEMS}</span>
            </div>

            <div className="space-y-4">
                {items.map((item, index) => (
                    <article
                        key={index}
                        className="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
                    >
                        <header className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div className="min-w-0">
                                <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                    FAQ {index + 1}
                                </p>

                                <p className="mt-1 truncate text-xs text-neutral-500 dark:text-neutral-400">
                                    {item.question || 'Untitled question'}
                                </p>
                            </div>

                            <div
                                role="group"
                                aria-label={`FAQ ${index + 1} controls`}
                                className="flex items-center gap-2"
                            >
                                <IconButton
                                    label={`Move FAQ ${index + 1} up`}
                                    disabled={index === 0}
                                    onClick={() => moveItem(index, 'up')}
                                >
                                    <ArrowUp aria-hidden="true" className="h-4 w-4" />
                                </IconButton>

                                <IconButton
                                    label={`Move FAQ ${index + 1} down`}
                                    disabled={index === items.length - 1}
                                    onClick={() => moveItem(index, 'down')}
                                >
                                    <ArrowDown aria-hidden="true" className="h-4 w-4" />
                                </IconButton>

                                <IconButton
                                    label={`Remove FAQ ${index + 1}`}
                                    disabled={items.length <= 1}
                                    danger
                                    onClick={() => removeItem(index)}
                                >
                                    <Trash2 aria-hidden="true" className="h-4 w-4" />
                                </IconButton>
                            </div>
                        </header>

                        <div className="space-y-4">
                            <div className="space-y-1.5">
                                <label
                                    htmlFor={`faq-question-${index}`}
                                    className="block text-sm font-medium text-neutral-700 dark:text-neutral-300"
                                >
                                    Question
                                </label>

                                <input
                                    id={`faq-question-${index}`}
                                    type="text"
                                    value={item.question}
                                    maxLength={MAX_QUESTION_LENGTH}
                                    onChange={(event) =>
                                        updateItem(index, {
                                            question: event.target.value,
                                        })
                                    }
                                    className={inputClass}
                                />

                                <CharacterCount
                                    current={item.question.length}
                                    max={MAX_QUESTION_LENGTH}
                                />
                            </div>

                            <div className="space-y-1.5">
                                <label
                                    htmlFor={`faq-answer-${index}`}
                                    className="block text-sm font-medium text-neutral-700 dark:text-neutral-300"
                                >
                                    Answer
                                </label>

                                <textarea
                                    id={`faq-answer-${index}`}
                                    value={item.answer}
                                    maxLength={MAX_ANSWER_LENGTH}
                                    rows={5}
                                    onChange={(event) =>
                                        updateItem(index, {
                                            answer: event.target.value,
                                        })
                                    }
                                    className={textareaClass}
                                />

                                <CharacterCount
                                    current={item.answer.length}
                                    max={MAX_ANSWER_LENGTH}
                                />
                            </div>
                        </div>
                    </article>
                ))}
            </div>

            {items.length >= MAX_ITEMS && (
                <p className="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
                    This FAQ section has reached the maximum of {MAX_ITEMS} items.
                </p>
            )}
        </div>
    )
}

interface IconButtonProps {
    label: string

    disabled?: boolean
    danger?: boolean

    onClick: () => void

    children: ReactNode
}

function IconButton({
    label,
    disabled = false,
    danger = false,
    onClick,
    children,
}: IconButtonProps) {
    return (
        <button
            type="button"
            aria-label={label}
            title={label}
            disabled={disabled}
            onClick={onClick}
            className={[
                'inline-flex h-10 w-10 items-center justify-center',
                'rounded-lg border transition',
                'focus-visible:outline-none',
                'focus-visible:ring-2',
                'disabled:cursor-not-allowed disabled:opacity-40',

                danger
                    ? [
                          'border-red-200',
                          'text-red-600',
                          'hover:bg-red-50',
                          'focus-visible:ring-red-400',
                          'dark:border-red-900',
                          'dark:text-red-400',
                          'dark:hover:bg-red-950/30',
                      ].join(' ')
                    : [
                          'border-neutral-300',
                          'text-neutral-600',
                          'hover:bg-neutral-50',
                          'focus-visible:ring-neutral-400',
                          'dark:border-neutral-700',
                          'dark:text-neutral-300',
                          'dark:hover:bg-neutral-900',
                      ].join(' '),
            ].join(' ')}
        >
            {children}
        </button>
    )
}

interface CharacterCountProps {
    current: number
    max: number
}

function CharacterCount({ current, max }: CharacterCountProps) {
    return (
        <p className="text-right text-xs text-neutral-400">
            {current}/{max}
        </p>
    )
}

const inputClass =
    'w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800'

const textareaClass =
    'w-full resize-y rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm leading-6 text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-neutral-800'
