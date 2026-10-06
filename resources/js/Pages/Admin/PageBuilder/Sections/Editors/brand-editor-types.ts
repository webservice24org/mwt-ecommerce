import type { JsonValue } from '@/types/page-builder'

export type BrandSourceConfig =
    | {
          [key: string]: JsonValue
          type: 'all'
      }
    | {
          [key: string]: JsonValue
          type: 'manual'
          brand_ids: number[]
      }

export interface PageBuilderBrandOption {
    id: number
    name: string
    slug: string
    logo_url: string | null
    is_active: boolean
}
