import {
    getInitialVariantSelection,
    getVariantAttributes,
    resolveSelectedVariant,
    updateVariantSelection,
} from '@/lib/storefrontVariantResolver'
import type { StorefrontVariant } from '@/types/storefront'
import type { StorefrontVariantSelection } from '@/types/storefront-variant-selection'
import { useMemo, useState } from 'react'

export function useStorefrontVariantSelection(variants: StorefrontVariant[]) {
    const [selection, setSelection] = useState<StorefrontVariantSelection>(() =>
        getInitialVariantSelection(variants),
    )

    const selectedVariant = useMemo(
        () => resolveSelectedVariant(variants, selection),
        [variants, selection],
    )

    const attributes = useMemo(
        () => getVariantAttributes(variants, selection),
        [variants, selection],
    )

    function select(attributeSlug: string, valueSlug: string): void {
        setSelection((current) =>
            updateVariantSelection(variants, current, attributeSlug, valueSlug),
        )
    }

    function reset(): void {
        setSelection(getInitialVariantSelection(variants))
    }

    return {
        selection,
        selectedVariant,
        attributes,
        select,
        reset,
        complete: selectedVariant !== null,
    }
}
