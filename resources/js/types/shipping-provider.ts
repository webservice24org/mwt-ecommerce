export interface ProviderStatusValue {
    value: string
    label: string
}

export interface ShippingProviderAccountSummary {
    id: number

    name: string

    is_active: boolean

    is_default: boolean

    auto_submit: boolean

    connection_status:
        ProviderStatusValue

    last_tested_at:
        | string
        | null

    last_connected_at:
        | string
        | null

    last_error:
        | string
        | null

    credentials_configured:
        boolean
}

export interface ShippingProviderSummary {
    code: string
    name: string

    external: boolean
    available: boolean
    implemented: boolean

    account:
        | ShippingProviderAccountSummary
        | null

    edit_url:
        | string
        | null

    test_url:
    | string
    | null

    disconnect_url:
        | string
        | null
}

export interface ShippingProviderEditAccount {
    id: number

    name: string

    credential_state: {
        client_id: boolean
        client_secret: boolean
        access_token: boolean
        username: boolean
        password: boolean
    }

    settings: {
        store_id:
            | number
            | null

        delivery_type:
            | number
            | null

        item_type:
            | number
            | null

        default_item_weight_kg:
            | number
            | null
    }

    connection_status:
        ProviderStatusValue
}

export interface ShippingProviderEditData {
    code: string
    name: string

    account:
        | ShippingProviderEditAccount
        | null

    defaults: {
        delivery_type: number
        item_type: number
        default_item_weight_kg: number
    }
}