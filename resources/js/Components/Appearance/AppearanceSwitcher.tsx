import { Monitor, Moon, Sun } from 'lucide-react'
import { useSyncExternalStore } from 'react'

type Appearance = 'light' | 'dark' | 'system'

function currentAppearance(): Appearance {
    if (typeof document === 'undefined') return 'system'
    const value = document.documentElement.getAttribute('data-mwt-appearance')
    return value === 'light' || value === 'dark' ? value : 'system'
}

function subscribe(listener: () => void) {
    window.addEventListener('mwt:appearance:changed', listener)
    return () => window.removeEventListener('mwt:appearance:changed', listener)
}

function changeAppearance(value: string) {
    if (value !== 'light' && value !== 'dark' && value !== 'system') return
    window.dispatchEvent(new CustomEvent('mwt:appearance:set', { detail: value }))
}

export default function AppearanceSwitcher({ className = '' }: { className?: string }) {
    const preference = useSyncExternalStore(subscribe, currentAppearance, () => 'system')
    const Icon = preference === 'light' ? Sun : preference === 'dark' ? Moon : Monitor

    return (
        <label className={`mwt-appearance-control ${className}`}>
            <Icon className="mwt-appearance-icon" aria-hidden="true" />
            <span className="sr-only">Color appearance</span>
            <select aria-label="Color appearance" value={preference} onChange={event => changeAppearance(event.target.value)}>
                <option value="light">Light</option>
                <option value="dark">Dark</option>
                <option value="system">System</option>
            </select>
        </label>
    )
}
