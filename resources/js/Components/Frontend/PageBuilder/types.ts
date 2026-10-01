import type { ReactNode } from 'react'

import type { StorefrontResolvedSection } from '@/types/storefront-page'

export interface StorefrontSectionProps {
    section: StorefrontResolvedSection
}

export type StorefrontSectionRender = (props: StorefrontSectionProps) => ReactNode
