import { Head, Link } from '@inertiajs/react'
import { ArrowLeft } from 'lucide-react'

import InvoiceDocument from '@/Components/Invoice/InvoiceDocument'
import CustomerLayout from '@/Layouts/Customer/CustomerLayout'
import type { InvoiceBranding, InvoiceData } from '@/types/invoice'

interface Props {
    invoice: InvoiceData
    branding: InvoiceBranding
    back_url: string
}

export default function Show({ invoice, branding, back_url }: Props) {
    return (
        <>
            <Head title={`Invoice ${invoice.invoice_number}`} />

            <CustomerLayout
                title={`Invoice ${invoice.invoice_number}`}
                description={`Invoice for order ${invoice.order_number}`}
            >
                <div className="invoice-screen-only mb-5">
                    <Link
                        href={back_url}
                        className="inline-flex min-h-11 items-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to order
                    </Link>
                </div>

                <InvoiceDocument invoice={invoice} branding={branding} />
            </CustomerLayout>
        </>
    )
}
