export interface InvoiceItem {
    product_name: string
    sku: string | null
    variant_description: string | null
    unit_price: number
    quantity: number
    line_total: number
}

export interface InvoiceAddress {
    recipient_name: string
    phone: string
    address_line_1: string
    address_line_2: string | null
    city: string
    state_or_region: string | null
    postal_code: string | null
    country_code: string
}

export interface InvoiceData {
    id: number
    invoice_number: string
    order_number: string

    customer: {
        name: string
        email: string
    }

    currency_code: string
    subtotal: number
    discount_total: number
    delivery_total: number
    tax_total: number
    grand_total: number

    delivery_address: InvoiceAddress
    items: InvoiceItem[]

    issued_at: string
}

export interface InvoiceBranding {
    company_name: string
    company_tagline: string
    logo_url: string | null
    header_start_color: string
    header_end_color: string
    footer_color: string
    support_email: string | null
    footer_credit: string
}
