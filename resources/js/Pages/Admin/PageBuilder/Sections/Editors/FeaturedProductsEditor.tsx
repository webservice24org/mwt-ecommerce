import { useState, useEffect } from 'react'
import type { SectionConfig } from '@/types/page-builder'
import type { SectionEditorProps } from './SectionEditor'

const MIN_LIMIT = 1
const MAX_LIMIT = 24

export default function FeaturedProductsEditor({ value, onChange }: SectionEditorProps) {
    const parentTitle = getString(value.title)
    const limit = getNumber(value.limit, 8)

    // Local state to prevent losing focus during parent re-renders
    const [title, setTitle] = useState(parentTitle)

    // Keep local state in sync if parent value changes externally
    useEffect(() => {
        setTitle(parentTitle)
    }, [parentTitle])

    const updateConfig = (
        key: keyof Pick<FeaturedProductsConfig, 'title' | 'limit'>,
        nextValue: string | number,
    ) => {
        const nextConfig: SectionConfig = {
            ...value,
            [key]: nextValue,
        }

        onChange(nextConfig)
    }

    const handleTitleChange = (newTitle: string) => {
        setTitle(newTitle)
        updateConfig('title', newTitle)
    }

    return (
        <div className="space-y-6">
            <div>
                <label
                    htmlFor="featured-products-title"
                    className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                >
                    Section title
                </label>

                <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    The heading displayed above the featured products.
                </p>

                <input
                    id="featured-products-title"
                    type="text"
                    value={title}
                    maxLength={120}
                    required
                    autoComplete="on"
                    onChange={(event) => handleTitleChange(event.target.value)}
                    className="mt-2 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:border-neutral-500 dark:focus:ring-neutral-800"
                />

                <div className="mt-1 flex items-center justify-between gap-3 text-xs text-neutral-400">
                    <span>Maximum 120 characters.</span>
                    <span>{title.length}/120</span>
                </div>
            </div>

            <div>
                <label
                    htmlFor="featured-products-limit"
                    className="block text-sm font-medium text-neutral-900 dark:text-neutral-100"
                >
                    Product limit
                </label>

                <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    Choose how many featured products this section can display.
                </p>

                <input
                    id="featured-products-limit"
                    type="number"
                    value={limit}
                    min={MIN_LIMIT}
                    max={MAX_LIMIT}
                    step={1}
                    required
                    onChange={(event) => {
                        const nextLimit = Number(event.target.value)

                        if (!Number.isInteger(nextLimit)) {
                            return
                        }

                        updateConfig('limit', nextLimit)
                    }}
                    className="mt-2 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:border-neutral-500 dark:focus:ring-neutral-800 sm:max-w-48"
                />

                <p className="mt-1 text-xs text-neutral-400">
                    Allowed range: {MIN_LIMIT}–{MAX_LIMIT} products.
                </p>
            </div>
        </div>
    )
}

interface FeaturedProductsConfig {
    title: string
    limit: number
}

function getString(value: SectionConfig[string]): string {
    return typeof value === 'string' ? value : ''
}

function getNumber(value: SectionConfig[string], fallback: number): number {
    return typeof value === 'number' && Number.isFinite(value) ? value : fallback
}