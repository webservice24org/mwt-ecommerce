export type StructuredDataPrimitive =
    | string
    | number
    | boolean
    | null

export type StructuredDataValue =
    | StructuredDataPrimitive
    | StructuredDataValue[]
    | {
          [key: string]:
              StructuredDataValue
      }

export interface StructuredDataDocument {
    '@context': string

    '@graph': Array<{
        [key: string]:
            StructuredDataValue
    }>
}