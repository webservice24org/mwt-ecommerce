export interface CustomerAddress {
    id: number
    label: string | null
    recipient_name: string
    phone: string
    address_line_1: string
    address_line_2: string | null
    city: string
    state_or_region: string | null
    postal_code: string | null
    country_code: string
    is_default: boolean
    created_at: string
}

export type CustomerAddressFormData = Record<string, string | boolean> & {
    label: string
    recipient_name: string
    phone: string
    address_line_1: string
    address_line_2: string
    city: string
    state_or_region: string
    postal_code: string
    country_code: string
    is_default: boolean
}
