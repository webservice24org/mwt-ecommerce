import type { StorefrontAttributeValue, StorefrontVariant } from '@/types/storefront'

export type StorefrontVariantSelection = Record<string, string>

export interface StorefrontVariantAttributeOption {
    value: StorefrontAttributeValue
    available: boolean
    selected: boolean
}

export interface StorefrontVariantAttribute {
    id: number
    name: string
    slug: string
    options: StorefrontVariantAttributeOption[]
}

export interface StorefrontVariantSelectionState {
    selection: StorefrontVariantSelection
    selectedVariant: StorefrontVariant | null
    complete: boolean
}
