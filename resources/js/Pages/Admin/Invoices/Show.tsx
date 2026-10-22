import { Head, Link } from '@inertiajs/react'
import { ArrowLeft } from 'lucide-react'

import InvoiceDocument from '@/Components/Invoice/InvoiceDocument'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
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

            <AdminLayout
                title={`Invoice ${invoice.invoice_number}`}
                description={`Invoice for order ${invoice.order_number}`}
                actions={
                    <Link
                        href={back_url}
                        className="invoice-screen-only inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to order
                    </Link>
                }
            >
                <div className="bg-slate-100 p-4 sm:p-6 lg:p-8">
                    <InvoiceDocument invoice={invoice} branding={branding} />
                </div>
            </AdminLayout>
        </>
    )
}
