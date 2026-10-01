import type { KeyboardEvent } from 'react'

interface Options {
    event: KeyboardEvent<HTMLElement>
    onPrevious: () => void
    onNext: () => void
}

export function handleHeroKeyboard({ event, onPrevious, onNext }: Options): void {
    const target = event.target

    if (
        target instanceof HTMLInputElement ||
        target instanceof HTMLTextAreaElement ||
        target instanceof HTMLSelectElement
    ) {
        return
    }

    if (event.key === 'ArrowLeft') {
        event.preventDefault()
        onPrevious()

        return
    }

    if (event.key === 'ArrowRight') {
        event.preventDefault()
        onNext()
    }
}
