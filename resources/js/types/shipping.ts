export type ShippingZoneRuleOption = {
    value: string
    label: string
}

export type ShippingZoneRuleFormValue = {
    type: string
    value: string
}

export type ShippingZoneFormData = {
    name: string
    code: string
    is_active: boolean
    is_catch_all: boolean
    priority: number
    rules: ShippingZoneRuleFormValue[]
}

export type ShippingZoneIndexItem = {
    id: number
    name: string
    code: string
    is_active: boolean
    is_catch_all: boolean
    priority: number
    rules_count: number
    rates_count: number
}

export type ShippingZoneEditRule = {
    id: number
    type: string
    value: string
}

export type ShippingZoneEditItem = {
    id: number
    name: string
    code: string
    is_active: boolean
    is_catch_all: boolean
    priority: number
    rules: ShippingZoneEditRule[]
}

export type ShippingMethodOption = {
    id: number
    name: string
    code: string
    driver: string
    is_active: boolean
    sort_order: number
}

export type ShippingRateZoneOption = {
    id: number
    name: string
    code: string
    is_active: boolean
    is_catch_all: boolean
    priority: number
}

export type ShippingRateFormOptions = {
    shippingMethods: ShippingMethodOption[]
    zones: ShippingRateZoneOption[]
}

export type ShippingRateFormData = {
    shipping_method_id: number | null
    name: string
    code: string
    amount: number
    min_order_amount: number | null
    max_order_amount: number | null
    free_shipping_threshold: number | null
    is_active: boolean
    priority: number
    zone_ids: number[]
}

export type ShippingRateMethod = {
    id: number
    name: string
    code: string
    is_active: boolean
}

export type ShippingRateZone = {
    id: number
    name: string
    code: string
}

export type ShippingRateIndexItem = {
    id: number
    name: string
    code: string
    amount: number
    min_order_amount: number | null
    max_order_amount: number | null
    free_shipping_threshold: number | null
    is_active: boolean
    priority: number

    shipping_method: ShippingRateMethod | null

    zones: ShippingRateZone[]

    is_global: boolean
}

export type ShippingRateEditItem = {
    id: number
    shipping_method_id: number
    name: string
    code: string
    amount: number
    min_order_amount: number | null
    max_order_amount: number | null
    free_shipping_threshold: number | null
    is_active: boolean
    priority: number
    zone_ids: number[]
}