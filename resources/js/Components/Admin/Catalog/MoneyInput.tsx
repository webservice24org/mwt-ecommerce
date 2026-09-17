import { useState } from 'react'

type Props = {
    id: string
    label: string
    value: number | null
    onChange: (value: number | null) => void
    error?: string
    required?: boolean
    helpText?: string
    placeholder?: string
    prefix?: string
}

function minorToMajor(value: number | null): string {
    if (value === null) {
        return ''
    }

    const whole = Math.floor(value / 100)
    const fraction = value % 100

    return `${whole}.${fraction.toString().padStart(2, '0')}`
}

function majorToMinor(value: string): number | null {
    const normalized = value.trim()

    if (normalized === '') {
        return null
    }

    if (!/^\d+(?:\.\d{0,2})?$/.test(normalized)) {
        return null
    }

    const [wholePart, decimalPart = ''] = normalized.split('.')

    const whole = Number.parseInt(wholePart, 10)
    const fraction = Number.parseInt(decimalPart.padEnd(2, '0'), 10)

    if (!Number.isSafeInteger(whole) || !Number.isSafeInteger(fraction)) {
        return null
    }

    const minor = whole * 100 + fraction

    return Number.isSafeInteger(minor) ? minor : null
}

export default function MoneyInput({
    id,
    label,
    value,
    onChange,
    error,
    required = false,
    helpText,
    placeholder = '0.00',
    prefix = '',
}: Props) {
    const [draft, setDraft] = useState<string | null>(null)

    const displayValue = draft ?? minorToMajor(value)

    const handleChange = (nextValue: string) => {
        if (!/^\d*(?:\.\d{0,2})?$/.test(nextValue)) {
            return
        }

        setDraft(nextValue)
        onChange(majorToMinor(nextValue))
    }

    const handleBlur = () => {
        setDraft(null)
    }

    return (
        <div>
            <label htmlFor={id} className="text-sm font-medium text-neutral-900">
                {label}
                {required && <span className="text-red-600"> *</span>}
            </label>

            <div className="relative mt-1">
                {prefix && (
                    <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-neutral-500">
                        {prefix}
                    </span>
                )}

                <input
                    id={id}
                    type="text"
                    inputMode="decimal"
                    value={displayValue}
                    onChange={(event) => handleChange(event.target.value)}
                    onBlur={handleBlur}
                    placeholder={placeholder}
                    required={required}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? `${id}-error` : undefined}
                    className={`block w-full rounded-lg border border-neutral-300 py-2.5 pr-3 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500 ${
                        prefix ? 'pl-8' : 'pl-3'
                    }`}
                />
            </div>

            {helpText && <p className="mt-1 text-xs text-neutral-500">{helpText}</p>}

            {error && (
                <p id={`${id}-error`} className="mt-1 text-sm text-red-600">
                    {error}
                </p>
            )}
        </div>
    )
}
