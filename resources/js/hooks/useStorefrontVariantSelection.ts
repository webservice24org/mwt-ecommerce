import {
    getInitialVariantSelection,
    getVariantAttributes,
    resolveSelectedVariant,
    updateVariantSelection,
} from '@/lib/storefrontVariantResolver'
import type { StorefrontVariant } from '@/types/storefront'
import type { StorefrontVariantSelection } from '@/types/storefront-variant-selection'
import { useEffect, useMemo, useState } from 'react'

function selectionForVariant(
    variants: StorefrontVariant[],
    initialVariantId: number | null,
): StorefrontVariantSelection {
    if (initialVariantId === null) {
        return getInitialVariantSelection(variants)
    }

    const variant =
        variants.find(
            (candidate) =>
                candidate.id ===
                initialVariantId,
        ) ?? null

    if (variant === null) {
        return getInitialVariantSelection(variants)
    }

    return variant.attribute_values.reduce<StorefrontVariantSelection>(
        (selection, value) => {
            selection[value.attribute.slug] =
                value.slug

            return selection
        },
        {},
    )
}

function validInitialVariantId(
    variants: StorefrontVariant[],
    initialVariantId: number | null,
): number | null {
    if (initialVariantId === null) {
        return null
    }

    return variants.some(
        (variant) =>
            variant.id ===
            initialVariantId,
    )
        ? initialVariantId
        : null
}

export function useStorefrontVariantSelection(
    variants: StorefrontVariant[],
    initialVariantId: number | null = null,
) {
    const [selection, setSelection] =
        useState<StorefrontVariantSelection>(
            () =>
                selectionForVariant(
                    variants,
                    initialVariantId,
                ),
        )

    /*
     * Preserve the exact server-selected variant for the
     * initial deep link.
     *
     * This matters for legitimate variants which have no
     * attribute values and therefore cannot be distinguished
     * by the attribute-selection map alone.
     *
     * Once the customer changes an option, normal
     * attribute-based resolution becomes authoritative.
     */
    const [
        preferredVariantId,
        setPreferredVariantId,
    ] =
        useState<number | null>(
            () =>
                validInitialVariantId(
                    variants,
                    initialVariantId,
                ),
        )

    useEffect(() => {
        setSelection(
            selectionForVariant(
                variants,
                initialVariantId,
            ),
        )

        setPreferredVariantId(
            validInitialVariantId(
                variants,
                initialVariantId,
            ),
        )
    }, [
        variants,
        initialVariantId,
    ])

    const resolvedVariant =
        useMemo(
            () =>
                resolveSelectedVariant(
                    variants,
                    selection,
                ),
            [
                variants,
                selection,
            ],
        )

    const selectedVariant =
        useMemo(() => {
            if (
                preferredVariantId !==
                null
            ) {
                const preferred =
                    variants.find(
                        (variant) =>
                            variant.id ===
                            preferredVariantId,
                    ) ?? null

                if (preferred !== null) {
                    return preferred
                }
            }

            return resolvedVariant
        }, [
            variants,
            preferredVariantId,
            resolvedVariant,
        ])

    const attributes =
        useMemo(
            () =>
                getVariantAttributes(
                    variants,
                    selection,
                ),
            [
                variants,
                selection,
            ],
        )

    function select(
        attributeSlug: string,
        valueSlug: string,
    ): void {
        setPreferredVariantId(
            null,
        )

        setSelection(
            (current) =>
                updateVariantSelection(
                    variants,
                    current,
                    attributeSlug,
                    valueSlug,
                ),
        )
    }

    function reset(): void {
        setPreferredVariantId(
            null,
        )

        setSelection(
            getInitialVariantSelection(
                variants,
            ),
        )
    }

    return {
        selection,
        selectedVariant,
        attributes,
        select,
        reset,
        complete:
            selectedVariant !==
            null,
    }
}
