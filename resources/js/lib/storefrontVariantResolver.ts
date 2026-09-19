import type { StorefrontVariant } from '@/types/storefront'
import type {
    StorefrontVariantAttribute,
    StorefrontVariantSelection,
} from '@/types/storefront-variant-selection'

/**
 * Converts a variant into:
 *
 * {
 *     color: 'black',
 *     size: 'large',
 * }
 */
export function getVariantSelection(variant: StorefrontVariant): StorefrontVariantSelection {
    return Object.fromEntries(
        variant.attribute_values.map((value) => [value.attribute.slug, value.slug]),
    )
}

/**
 * Returns every selectable attribute and its unique values.
 *
 * Attribute/value ordering follows the order supplied by the
 * storefront payload.
 */
export function getVariantAttributes(
    variants: StorefrontVariant[],
    selection: StorefrontVariantSelection = {},
): StorefrontVariantAttribute[] {
    const attributes = new Map<
        string,
        {
            id: number
            name: string
            slug: string
            values: Map<string, StorefrontVariant['attribute_values'][number]>
        }
    >()

    for (const variant of variants) {
        for (const value of variant.attribute_values) {
            const attributeSlug = value.attribute.slug

            let attribute = attributes.get(attributeSlug)

            if (!attribute) {
                attribute = {
                    id: value.attribute.id,
                    name: value.attribute.name,
                    slug: attributeSlug,
                    values: new Map(),
                }

                attributes.set(attributeSlug, attribute)
            }

            if (!attribute.values.has(value.slug)) {
                attribute.values.set(value.slug, value)
            }
        }
    }

    return Array.from(attributes.values()).map((attribute) => ({
        id: attribute.id,
        name: attribute.name,
        slug: attribute.slug,

        options: Array.from(attribute.values.values()).map((value) => ({
            value,
            selected: selection[attribute.slug] === value.slug,
            available: isVariantOptionAvailable(variants, selection, attribute.slug, value.slug),
        })),
    }))
}

/**
 * Resolves a variant only when the selection represents the
 * variant's complete attribute combination.
 */
export function resolveSelectedVariant(
    variants: StorefrontVariant[],
    selection: StorefrontVariantSelection,
): StorefrontVariant | null {
    const selectedEntries = Object.entries(selection)

    if (selectedEntries.length === 0) {
        return null
    }

    return (
        variants.find((variant) => {
            const variantSelection = getVariantSelection(variant)
            const variantEntries = Object.entries(variantSelection)

            if (variantEntries.length !== selectedEntries.length) {
                return false
            }

            return variantEntries.every(
                ([attributeSlug, valueSlug]) => selection[attributeSlug] === valueSlug,
            )
        }) ?? null
    )
}

/**
 * Tests whether a proposed option can participate in at least one
 * real public variant while retaining selections from the other
 * attributes.
 *
 * The current attribute is replaced by the proposed value.
 */
export function isVariantOptionAvailable(
    variants: StorefrontVariant[],
    _currentSelection: StorefrontVariantSelection,
    attributeSlug: string,
    valueSlug: string,
): boolean {
    return variants.some((variant) => {
        const variantSelection = getVariantSelection(variant)

        return variantSelection[attributeSlug] === valueSlug
    })
}

/**
 * A partial selection matches when every currently selected
 * attribute exists on the variant with the same value.
 */
export function variantMatchesPartialSelection(
    variant: StorefrontVariant,
    selection: StorefrontVariantSelection,
): boolean {
    const variantSelection = getVariantSelection(variant)

    return Object.entries(selection).every(
        ([attributeSlug, valueSlug]) => variantSelection[attributeSlug] === valueSlug,
    )
}

/**
 * Remove selections that cannot participate in any remaining
 * public variant.
 *
 * Useful after one attribute changes and invalidates a previously
 * selected downstream option.
 */
export function normalizeVariantSelection(
    variants: StorefrontVariant[],
    selection: StorefrontVariantSelection,
): StorefrontVariantSelection {
    let normalized: StorefrontVariantSelection = {}

    for (const [attributeSlug, valueSlug] of Object.entries(selection)) {
        const candidate = {
            ...normalized,
            [attributeSlug]: valueSlug,
        }

        if (variants.some((variant) => variantMatchesPartialSelection(variant, candidate))) {
            normalized = candidate
        }
    }

    return normalized
}

export function getInitialVariantSelection(
    variants: StorefrontVariant[],
): StorefrontVariantSelection {
    if (variants.length === 0) {
        return {}
    }

    const defaultVariant = variants.find((variant) => variant.is_default) ?? variants[0]

    return getVariantSelection(defaultVariant)
}

export function updateVariantSelection(
    variants: StorefrontVariant[],
    currentSelection: StorefrontVariantSelection,
    attributeSlug: string,
    valueSlug: string,
): StorefrontVariantSelection {
    // The customer's newest choice always wins.
    const nextSelection: StorefrontVariantSelection = {
        [attributeSlug]: valueSlug,
    }

    // Try to preserve the customer's other existing selections.
    for (const [slug, selectedValue] of Object.entries(currentSelection)) {
        // We already added the attribute that was just changed.
        if (slug === attributeSlug) {
            continue
        }

        const candidate = {
            ...nextSelection,
            [slug]: selectedValue,
        }

        // Keep the old selection only if at least one real variant
        // supports the resulting combination.
        const isValid = variants.some((variant) =>
            variantMatchesPartialSelection(variant, candidate),
        )

        if (isValid) {
            nextSelection[slug] = selectedValue
        }
    }

    return nextSelection
}
