import { Printer } from 'lucide-react'

import type { InvoiceBranding, InvoiceData } from '@/types/invoice'

interface Props {
    invoice: InvoiceData
    branding: InvoiceBranding
}

export default function InvoiceDocument({ invoice, branding }: Props) {
    const printInvoice = () => {
        window.print()
    }

    return (
        <>
            <style>{`
                @media print {
                    @page {
                        size: A4 portrait;
                        margin: 0;
                    }

                    html,
                    body {
                        margin: 0 !important;
                        padding: 0 !important;
                        width: 100% !important;
                        min-height: 0 !important;
                        background: #ffffff !important;
                    }

                    /*
                     * Hide the surrounding admin/customer
                     * application shell and print only the
                     * invoice document.
                     */
                    body * {
                        visibility: hidden !important;
                    }

                    .invoice-print-root,
                    .invoice-print-root * {
                        visibility: visible !important;
                    }

                    .invoice-print-root {
                        position: absolute !important;
                        inset: 0 auto auto 0 !important;
                        display: flex !important;
                        flex-direction: column !important;
                        width: 100% !important;
                        min-height: 297mm !important;
                        max-width: none !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        border: 0 !important;
                        border-radius: 0 !important;
                        box-shadow: none !important;
                        overflow: visible !important;
                        background: #ffffff !important;

                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }

                    .invoice-print-root * {
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }

                    .invoice-screen-only {
                        display: none !important;
                    }

                    .invoice-print-header {
                        padding: 18px 22px !important;
                    }

                    .invoice-print-body {
                        flex: 1 0 auto !important;
                        padding: 18px 22px !important;
                    }

                    .invoice-print-info-grid {
                        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                        gap: 12px !important;
                        margin-bottom: 14px !important;
                    }

                    .invoice-print-info-card {
                        padding: 12px !important;
                        break-inside: avoid !important;
                        page-break-inside: avoid !important;
                    }

                    .invoice-print-table-wrap {
                        overflow: visible !important;
                    }

                    .invoice-print-table {
                        width: 100% !important;
                        min-width: 0 !important;
                        table-layout: fixed !important;
                        font-size: 10px !important;
                    }

                    .invoice-print-table th,
                    .invoice-print-table td {
                        padding: 7px 8px !important;
                    }

                    .invoice-print-table th:first-child,
                    .invoice-print-table td:first-child {
                        width: 7% !important;
                    }

                    .invoice-print-table th:nth-child(2),
                    .invoice-print-table td:nth-child(2) {
                        width: 45% !important;
                    }

                    .invoice-print-table th:nth-child(3),
                    .invoice-print-table td:nth-child(3) {
                        width: 10% !important;
                    }

                    .invoice-print-table th:nth-child(4),
                    .invoice-print-table td:nth-child(4),
                    .invoice-print-table th:nth-child(5),
                    .invoice-print-table td:nth-child(5) {
                        width: 19% !important;
                    }

                    .invoice-print-table thead {
                        display: table-header-group !important;
                    }

                    .invoice-print-table tr {
                        break-inside: avoid !important;
                        page-break-inside: avoid !important;
                    }

                    .invoice-print-totals {
                        margin-top: 12px !important;
                    }

                    .invoice-print-totals-box {
                        width: 275px !important;
                    }

                    .invoice-print-footer {
                        margin-top: auto !important;
                        padding: 14px 22px !important;
                        break-inside: avoid !important;
                        page-break-inside: avoid !important;
                    }

                    #app,
                    main {
                        min-height: 0 !important;
                    }
                }
            `}</style>

            <article className="invoice-print-root mx-auto max-w-4xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <header
                    className="invoice-print-header p-8 text-white sm:p-10"
                    style={{
                        backgroundImage: `linear-gradient(to right, ${branding.header_start_color}, ${branding.header_end_color})`,
                    }}
                >
                    <div className="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex min-w-0 items-center gap-4">
                            {branding.logo_url ? (
                                <div className="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white p-2 shadow-md">
                                    <img
                                        src={branding.logo_url}
                                        alt={`${branding.company_name} logo`}
                                        className="max-h-full max-w-full object-contain"
                                    />
                                </div>
                            ) : (
                                <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-white/95 text-xl font-black text-slate-900 shadow-md">
                                    {companyInitials(branding.company_name)}
                                </div>
                            )}

                            <div className="min-w-0">
                                <h1 className="break-words text-2xl font-bold tracking-tight">
                                    {branding.company_name}
                                </h1>

                                <p className="mt-1 text-xs font-semibold uppercase tracking-[0.18em] text-white/75">
                                    {branding.company_tagline}
                                </p>
                            </div>
                        </div>

                        <div className="sm:text-right">
                            <span className="inline-flex rounded-full border border-white/20 bg-black/15 px-3 py-1 text-xs font-semibold uppercase tracking-wider backdrop-blur">
                                Official Invoice
                            </span>

                            <h2 className="mt-2 break-all text-3xl font-black tracking-tight">
                                #{invoice.invoice_number}
                            </h2>

                            <p className="mt-1 text-xs text-white/80">
                                Issued: {formatDate(invoice.issued_at)}
                            </p>

                            <p className="mt-1 text-xs text-white/70">
                                Order #{invoice.order_number}
                            </p>
                        </div>
                    </div>
                </header>

                <div className="invoice-print-body p-6 sm:p-10">
                    <div className="invoice-print-info-grid mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <InfoCard title="Billed To">
                            <p className="text-base font-bold text-slate-900">
                                {invoice.customer.name}
                            </p>

                            <p className="mt-1 break-all text-slate-600">
                                {invoice.customer.email}
                            </p>

                            <div className="mt-3 text-slate-600">
                                <address className="break-words not-italic leading-6">
                                    <AddressLines address={invoice.delivery_address} />
                                </address>
                            </div>
                        </InfoCard>

                        <InfoCard title="Invoice Info">
                            <InfoRow label="Invoice" value={invoice.invoice_number} />

                            <InfoRow label="Order" value={invoice.order_number} />

                            <InfoRow label="Issued" value={formatDate(invoice.issued_at)} />

                            <InfoRow label="Currency" value={invoice.currency_code} />
                        </InfoCard>
                    </div>

                    <div className="invoice-print-table-wrap overflow-x-auto rounded-xl border border-slate-300">
                        <table className="invoice-print-table w-full min-w-[680px] border-collapse text-left">
                            <thead>
                                <tr className="border-b border-slate-300 bg-slate-50 text-xs uppercase tracking-wider text-slate-700">
                                    <th className="border-r border-slate-300 px-4 py-3.5 text-center font-bold">
                                        #
                                    </th>

                                    <th className="border-r border-slate-300 px-4 py-3.5 font-bold">
                                        Item Description
                                    </th>

                                    <th className="border-r border-slate-300 px-4 py-3.5 text-center font-bold">
                                        Qty
                                    </th>

                                    <th className="border-r border-slate-300 px-4 py-3.5 text-right font-bold">
                                        Unit Price
                                    </th>

                                    <th className="px-4 py-3.5 text-right font-bold">Amount</th>
                                </tr>
                            </thead>

                            <tbody className="divide-y divide-slate-300 text-sm text-slate-800">
                                {invoice.items.map((item, index) => (
                                    <tr
                                        key={`${item.sku ?? item.product_name}-${index}`}
                                        className={index % 2 === 0 ? 'bg-white' : 'bg-slate-50/70'}
                                    >
                                        <td className="border-r border-slate-300 px-4 py-3.5 text-center font-medium text-slate-500">
                                            {index + 1}
                                        </td>

                                        <td className="min-w-0 break-words border-r border-slate-300 px-4 py-3.5 font-medium text-slate-900">
                                            {item.sku && (
                                                <p className="mt-1 break-all text-xs font-normal text-slate-400">
                                                    SKU: {item.sku}
                                                </p>
                                            )}

                                            {item.variant_description && (
                                                <p className="mt-1 break-words text-xs font-normal text-slate-500">
                                                    {item.variant_description}
                                                </p>
                                            )}

                                            {item.sku && (
                                                <p className="mt-1 text-xs font-normal text-slate-400">
                                                    SKU: {item.sku}
                                                </p>
                                            )}
                                        </td>

                                        <td className="border-r border-slate-300 px-4 py-3.5 text-center">
                                            {item.quantity}
                                        </td>

                                        <td className="border-r border-slate-300 px-4 py-3.5 text-right">
                                            {formatInvoiceMoney(
                                                item.unit_price,
                                                invoice.currency_code,
                                            )}
                                        </td>

                                        <td className="px-4 py-3.5 text-right font-bold text-slate-900">
                                            {formatInvoiceMoney(
                                                item.line_total,
                                                invoice.currency_code,
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="invoice-print-totals mt-6 flex justify-end">
                        <div className="invoice-print-totals-box w-full overflow-hidden rounded-xl border border-slate-300 shadow-sm sm:w-80">
                            <TotalRow
                                label="Subtotal"
                                value={formatInvoiceMoney(invoice.subtotal, invoice.currency_code)}
                            />

                            {invoice.discount_total > 0 && (
                                <TotalRow
                                    label="Discount"
                                    value={`-${formatInvoiceMoney(
                                        invoice.discount_total,
                                        invoice.currency_code,
                                    )}`}
                                />
                            )}

                            <TotalRow
                                label="Shipping Fee"
                                value={formatInvoiceMoney(
                                    invoice.delivery_total,
                                    invoice.currency_code,
                                )}
                            />

                            {invoice.tax_total > 0 && (
                                <TotalRow
                                    label="Tax"
                                    value={formatInvoiceMoney(
                                        invoice.tax_total,
                                        invoice.currency_code,
                                    )}
                                />
                            )}

                            <div
                                className="flex justify-between gap-4 px-4 py-3 text-base font-bold text-white"
                                style={{
                                    backgroundColor: branding.header_end_color,
                                }}
                            >
                                <span>Total Amount</span>

                                <span>
                                    {formatInvoiceMoney(invoice.grand_total, invoice.currency_code)}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <footer
                    className="invoice-print-footer flex flex-col items-start justify-between gap-5 border-t-4 p-6 text-slate-300 sm:flex-row sm:items-center sm:p-8"
                    style={{
                        backgroundColor: branding.footer_color,

                        borderTopColor: branding.header_end_color,
                    }}
                >
                    <div>
                        <p className="text-sm font-medium text-white">{branding.footer_credit}</p>

                        {branding.support_email && (
                            <p className="mt-1 text-xs text-slate-300">
                                Questions about this invoice? Contact{' '}
                                <a
                                    href={`mailto:${branding.support_email}`}
                                    className="font-medium underline"
                                >
                                    {branding.support_email}
                                </a>
                            </p>
                        )}
                    </div>

                    <button
                        type="button"
                        onClick={printInvoice}
                        className="invoice-screen-only inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:opacity-90"
                        style={{
                            backgroundColor: branding.header_end_color,
                        }}
                    >
                        <Printer className="h-4 w-4" aria-hidden="true" />
                        Print Invoice
                    </button>
                </footer>
            </article>
        </>
    )
}

function InfoCard({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <section className="invoice-print-info-card rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm">
            <h3 className="mb-3 text-xs font-bold uppercase tracking-wider text-slate-700">
                {title}
            </h3>

            {children}
        </section>
    )
}

function InfoRow({ label, value }: { label: string; value: string }) {
    return (
        <p className="mt-1 text-slate-700 first:mt-0">
            <span className="font-semibold text-slate-900">{label}:</span> {value}
        </p>
    )
}

function TotalRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex justify-between gap-4 border-b border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-600">
            <span>{label}</span>

            <span className="font-medium text-slate-900">{value}</span>
        </div>
    )
}

function AddressLines({ address }: { address: InvoiceData['delivery_address'] }) {
    return (
        <address className="not-italic leading-6">
            <span className="block">{address.recipient_name}</span>

            <span className="block">{address.address_line_1}</span>

            {address.address_line_2 && <span className="block">{address.address_line_2}</span>}

            <span className="block">
                {address.city}
                {address.state_or_region ? `, ${address.state_or_region}` : ''}
            </span>

            {address.postal_code && <span className="block">{address.postal_code}</span>}

            <span className="block">{address.country_code}</span>

            <span className="mt-1 block">{address.phone}</span>
        </address>
    )
}

function companyInitials(value: string): string {
    return value
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('')
}

function formatDate(value: string): string {
    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return value
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'long',
    }).format(date)
}

function formatInvoiceMoney(minorUnits: number, currency: string): string {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(minorUnits / 100)
}
