import { useForm } from '@inertiajs/react'
import {
    CalendarDays,
    CheckCircle2,
    CircleDollarSign,
} from 'lucide-react'
import type { FormEvent } from 'react'

import { formatMoney } from '@/lib/money'

interface StatusValue {
    value: string
    label: string
}

interface Props {
    currencyCode: string
    total: number

    payment: {
        method: StatusValue

        status: StatusValue

        due_at: string | null

        paid_at: string | null

        actions: {
            can_mark_due: boolean
            can_mark_paid: boolean

            due_url: string
            paid_url: string
        }
    }
}

interface DueForm {
    payment_due_at: string
    note: string
}

interface PaidForm {
    note: string
}

export default function OrderPaymentPanel({
    currencyCode,
    total,
    payment,
}: Props) {
    const dueForm =
        useForm<DueForm>({
            payment_due_at: '',
            note: '',
        })

    const paidForm =
        useForm<PaidForm>({
            note: '',
        })

    const submitDue = (
        event:
            FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        dueForm.patch(
            payment
                .actions
                .due_url,
            {
                preserveScroll:
                    true,

                onSuccess: () => {
                    dueForm.reset()
                },
            },
        )
    }

    const submitPaid = (
        event:
            FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        paidForm.patch(
            payment
                .actions
                .paid_url,
            {
                preserveScroll:
                    true,

                onSuccess: () => {
                    paidForm.reset()
                },
            },
        )
    }

    return (
        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="border-b border-slate-200 px-5 py-4 sm:px-6">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100">
                        <CircleDollarSign
                            className="h-5 w-5 text-slate-600"
                            aria-hidden="true"
                        />
                    </div>

                    <div>
                        <h2 className="font-semibold text-slate-900">
                            Payment collection
                        </h2>

                        <p className="mt-1 text-sm text-slate-500">
                            Record COD collection independently
                            from delivery and order completion.
                        </p>
                    </div>
                </div>
            </div>

            <div className="p-5 sm:p-6">
                <div className="grid gap-4 sm:grid-cols-3">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Method
                        </p>

                        <p className="mt-1 font-semibold text-slate-900">
                            {payment.method.label}
                        </p>
                    </div>

                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Status
                        </p>

                        <p className="mt-1 font-semibold text-slate-900">
                            {payment.status.label}
                        </p>
                    </div>

                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Amount
                        </p>

                        <p className="mt-1 text-lg font-bold text-slate-950">
                            {formatMoney(
                                total,
                                currencyCode,
                            )}
                        </p>
                    </div>
                </div>

                {payment.status.value ===
                    'paid' && (
                    <div className="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                        <div className="flex gap-3">
                            <CheckCircle2
                                className="mt-0.5 h-5 w-5 shrink-0 text-emerald-700"
                                aria-hidden="true"
                            />

                            <div>
                                <p className="font-semibold text-emerald-900">
                                    Payment collected
                                </p>

                                <p className="mt-1 text-sm text-emerald-700">
                                    {payment.paid_at
                                        ? `Paid ${formatDate(
                                              payment.paid_at,
                                          )}`
                                        : 'Full payment has been recorded.'}
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {payment.status.value ===
                    'due' && (
                    <div className="mt-5 rounded-xl border border-orange-200 bg-orange-50 p-4">
                        <div className="flex gap-3">
                            <CalendarDays
                                className="mt-0.5 h-5 w-5 shrink-0 text-orange-700"
                                aria-hidden="true"
                            />

                            <div>
                                <p className="font-semibold text-orange-900">
                                    Payment outstanding
                                </p>

                                <p className="mt-1 text-sm text-orange-700">
                                    Due:{' '}
                                    {formatDateOnly(
                                        payment.due_at,
                                    )}
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {payment.actions
                    .can_mark_due && (
                    <form
                        onSubmit={
                            submitDue
                        }
                        className="mt-6 rounded-xl border border-orange-200 bg-orange-50/50 p-4"
                    >
                        <h3 className="font-semibold text-slate-900">
                            Payment was not collected
                        </h3>

                        <p className="mt-1 text-sm text-slate-600">
                            Mark the delivered order as Due and
                            record when payment is expected.
                        </p>

                        <div className="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label
                                    htmlFor="payment_due_at"
                                    className="text-sm font-medium text-slate-700"
                                >
                                    Expected payment date
                                </label>

                                <input
                                    id="payment_due_at"
                                    type="date"
                                    required
                                    value={
                                        dueForm
                                            .data
                                            .payment_due_at
                                    }
                                    onChange={(
                                        event,
                                    ) =>
                                        dueForm.setData(
                                            'payment_due_at',
                                            event
                                                .target
                                                .value,
                                        )
                                    }
                                    className="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
                                />

                                {dueForm.errors
                                    .payment_due_at && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {
                                            dueForm
                                                .errors
                                                .payment_due_at
                                        }
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="payment_due_note"
                                    className="text-sm font-medium text-slate-700"
                                >
                                    Note
                                </label>

                                <input
                                    id="payment_due_note"
                                    type="text"
                                    value={
                                        dueForm
                                            .data
                                            .note
                                    }
                                    onChange={(
                                        event,
                                    ) =>
                                        dueForm.setData(
                                            'note',
                                            event
                                                .target
                                                .value,
                                        )
                                    }
                                    placeholder="Optional note"
                                    className="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
                                />
                            </div>
                        </div>

                        <button
                            type="submit"
                            disabled={
                                dueForm.processing
                            }
                            className="mt-4 inline-flex min-h-10 items-center justify-center rounded-lg bg-orange-600 px-4 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-50"
                        >
                            {dueForm.processing
                                ? 'Saving...'
                                : 'Mark as Due'}
                        </button>
                    </form>
                )}

                {payment.actions
                    .can_mark_paid && (
                    <form
                        onSubmit={
                            submitPaid
                        }
                        className="mt-6 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4"
                    >
                        <h3 className="font-semibold text-slate-900">
                            Payment collected
                        </h3>

                        <p className="mt-1 text-sm text-slate-600">
                            Confirm that the full COD amount has
                            been received.
                        </p>

                        <div className="mt-4">
                            <label
                                htmlFor="payment_paid_note"
                                className="text-sm font-medium text-slate-700"
                            >
                                Collection note
                            </label>

                            <input
                                id="payment_paid_note"
                                type="text"
                                value={
                                    paidForm
                                        .data
                                        .note
                                }
                                onChange={(
                                    event,
                                ) =>
                                    paidForm.setData(
                                        'note',
                                        event
                                            .target
                                            .value,
                                    )
                                }
                                placeholder="Cash received / courier collected"
                                className="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={
                                paidForm.processing
                            }
                            className="mt-4 inline-flex min-h-10 items-center justify-center rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-50"
                        >
                            {paidForm.processing
                                ? 'Saving...'
                                : 'Mark as Paid'}
                        </button>
                    </form>
                )}

                {payment.status.value ===
                    'unpaid' &&
                    !payment.actions
                        .can_mark_paid && (
                        <div className="mt-5 rounded-xl bg-amber-50 p-4 text-sm leading-6 text-amber-800">
                            COD payment remains unpaid. Payment
                            collection controls become available
                            after the order is delivered.
                        </div>
                    )}
            </div>
        </section>
    )
}

function formatDate(
    value: string,
): string {
    return new Intl.DateTimeFormat(
        undefined,
        {
            dateStyle: 'medium',
            timeStyle: 'short',
        },
    ).format(
        new Date(value),
    )
}

function formatDateOnly(
    value: string | null,
): string {
    if (!value) {
        return 'Not specified'
    }

    return new Intl.DateTimeFormat(
        undefined,
        {
            dateStyle: 'medium',
        },
    ).format(
        new Date(value),
    )
}