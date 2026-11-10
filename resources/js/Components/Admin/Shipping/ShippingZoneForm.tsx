import type { InertiaFormProps } from '@inertiajs/react'
import { Globe2, Plus, Trash2 } from 'lucide-react'
import type { FormEvent } from 'react'

import type {
    ShippingZoneFormData,
    ShippingZoneRuleOption,
} from '@/types/shipping'

type Props = {
    form: InertiaFormProps<ShippingZoneFormData>
    ruleTypes: ShippingZoneRuleOption[]
    submitLabel: string
    onSubmit: (event: FormEvent<HTMLFormElement>) => void
}

export default function ShippingZoneForm({
    form,
    ruleTypes,
    submitLabel,
    onSubmit,
}: Props) {
    const errors = form.errors as Record<string, string>

    const addRule = () => {
        form.setData('rules', [
            ...form.data.rules,
            {
                type: ruleTypes[0]?.value ?? '',
                value: '',
            },
        ])
    }

    const updateRule = (
        index: number,
        field: 'type' | 'value',
        value: string,
    ) => {
        form.setData(
            'rules',
            form.data.rules.map((rule, ruleIndex) =>
                ruleIndex === index
                    ? {
                          ...rule,
                          [field]: value,
                      }
                    : rule,
            ),
        )
    }

    const removeRule = (index: number) => {
        form.setData(
            'rules',
            form.data.rules.filter(
                (_, ruleIndex) => ruleIndex !== index,
            ),
        )
    }

    const toggleCatchAll = (checked: boolean) => {
        form.setData('is_catch_all', checked)

        if (checked) {
            form.setData('rules', [])
        }
    }

    return (
        <form
            onSubmit={onSubmit}
            className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]"
        >
            <div className="space-y-6">
                <section className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <div className="mb-6">
                        <h2 className="text-base font-semibold text-neutral-900">
                            Zone information
                        </h2>

                        <p className="mt-1 text-sm text-neutral-500">
                            Define the delivery area represented by this shipping
                            zone.
                        </p>
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label
                                htmlFor="name"
                                className="text-sm font-medium text-neutral-900"
                            >
                                Zone name
                            </label>

                            <input
                                id="name"
                                type="text"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData(
                                        'name',
                                        event.target.value,
                                    )
                                }
                                placeholder="Rangamati"
                                maxLength={120}
                                required
                                className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                            />

                            {form.errors.name && (
                                <p className="mt-1 text-sm text-red-600">
                                    {form.errors.name}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="code"
                                className="text-sm font-medium text-neutral-900"
                            >
                                Code
                            </label>

                            <input
                                id="code"
                                type="text"
                                value={form.data.code}
                                onChange={(event) =>
                                    form.setData(
                                        'code',
                                        event.target.value,
                                    )
                                }
                                placeholder="rangamati"
                                maxLength={80}
                                className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                            />

                            <p className="mt-1 text-xs text-neutral-500">
                                Leave blank to generate it from the zone name.
                            </p>

                            {form.errors.code && (
                                <p className="mt-1 text-sm text-red-600">
                                    {form.errors.code}
                                </p>
                            )}
                        </div>
                    </div>
                </section>

                <section className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 className="text-base font-semibold text-neutral-900">
                                Matching rules
                            </h2>

                            <p className="mt-1 max-w-2xl text-sm leading-6 text-neutral-500">
                                Rules decide whether a customer delivery address
                                belongs to this zone. Multiple values of the
                                same type can be used for several matching
                                areas.
                            </p>
                        </div>

                        {!form.data.is_catch_all && (
                            <button
                                type="button"
                                onClick={addRule}
                                className="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50"
                            >
                                <Plus
                                    className="h-4 w-4"
                                    aria-hidden="true"
                                />

                                Add rule
                            </button>
                        )}
                    </div>

                    {form.data.is_catch_all ? (
                        <div className="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <div className="flex gap-3">
                                <Globe2
                                    className="mt-0.5 h-5 w-5 shrink-0 text-amber-700"
                                    aria-hidden="true"
                                />

                                <div>
                                    <p className="font-medium text-amber-900">
                                        Catch-all zone
                                    </p>

                                    <p className="mt-1 text-sm leading-6 text-amber-800">
                                        This zone does not need individual
                                        matching rules. It can be used as a
                                        fallback when no higher-priority zone
                                        matches the delivery address.
                                    </p>
                                </div>
                            </div>
                        </div>
                    ) : form.data.rules.length === 0 ? (
                        <div className="mt-6 rounded-xl border border-dashed border-neutral-300 bg-neutral-50 p-6 text-center">
                            <p className="text-sm font-medium text-neutral-700">
                                No matching rules yet.
                            </p>

                            <p className="mt-1 text-sm text-neutral-500">
                                Add at least one rule to define which addresses
                                belong to this zone.
                            </p>

                            <button
                                type="button"
                                onClick={addRule}
                                className="mt-4 inline-flex items-center gap-2 rounded-lg bg-neutral-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-neutral-800"
                            >
                                <Plus
                                    className="h-4 w-4"
                                    aria-hidden="true"
                                />

                                Add first rule
                            </button>
                        </div>
                    ) : (
                        <div className="mt-6 space-y-4">
                            {form.data.rules.map((rule, index) => (
                                <div
                                    key={index}
                                    className="grid gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-4 sm:grid-cols-[200px_minmax(0,1fr)_auto]"
                                >
                                    <div>
                                        <label
                                            htmlFor={`rule-type-${index}`}
                                            className="text-xs font-semibold uppercase tracking-wide text-neutral-500"
                                        >
                                            Rule type
                                        </label>

                                        <select
                                            id={`rule-type-${index}`}
                                            value={rule.type}
                                            onChange={(event) =>
                                                updateRule(
                                                    index,
                                                    'type',
                                                    event.target.value,
                                                )
                                            }
                                            className="mt-1 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                                        >
                                            {ruleTypes.map((option) => (
                                                <option
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </option>
                                            ))}
                                        </select>

                                        {errors[`rules.${index}.type`] && (
                                            <p className="mt-1 text-xs text-red-600">
                                                {
                                                    errors[
                                                        `rules.${index}.type`
                                                    ]
                                                }
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <label
                                            htmlFor={`rule-value-${index}`}
                                            className="text-xs font-semibold uppercase tracking-wide text-neutral-500"
                                        >
                                            Match value
                                        </label>

                                        <input
                                            id={`rule-value-${index}`}
                                            type="text"
                                            value={rule.value}
                                            onChange={(event) =>
                                                updateRule(
                                                    index,
                                                    'value',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Example: BD, Rangamati, 4000"
                                            maxLength={190}
                                            className="mt-1 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                                        />

                                        {errors[`rules.${index}.value`] && (
                                            <p className="mt-1 text-xs text-red-600">
                                                {
                                                    errors[
                                                        `rules.${index}.value`
                                                    ]
                                                }
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex items-end">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                removeRule(index)
                                            }
                                            className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-red-200 bg-white text-red-600 transition hover:bg-red-50"
                                            aria-label={`Remove rule ${index + 1}`}
                                        >
                                            <Trash2
                                                className="h-4 w-4"
                                                aria-hidden="true"
                                            />
                                        </button>
                                    </div>
                                </div>
                            ))}

                            {form.errors.rules && (
                                <p className="text-sm text-red-600">
                                    {form.errors.rules}
                                </p>
                            )}
                        </div>
                    )}
                </section>
            </div>

            <div className="space-y-6">
                <section className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <h2 className="text-base font-semibold text-neutral-900">
                        Zone settings
                    </h2>

                    <div className="mt-5 space-y-5">
                        <label className="flex cursor-pointer items-start gap-3">
                            <input
                                type="checkbox"
                                checked={form.data.is_active}
                                onChange={(event) =>
                                    form.setData(
                                        'is_active',
                                        event.target.checked,
                                    )
                                }
                                className="mt-1 h-4 w-4 rounded border-neutral-300"
                            />

                            <span>
                                <span className="block text-sm font-medium text-neutral-900">
                                    Active
                                </span>

                                <span className="mt-0.5 block text-xs leading-5 text-neutral-500">
                                    Inactive zones are ignored during checkout.
                                </span>
                            </span>
                        </label>

                        <label className="flex cursor-pointer items-start gap-3 border-t border-neutral-200 pt-5">
                            <input
                                type="checkbox"
                                checked={form.data.is_catch_all}
                                onChange={(event) =>
                                    toggleCatchAll(
                                        event.target.checked,
                                    )
                                }
                                className="mt-1 h-4 w-4 rounded border-neutral-300"
                            />

                            <span>
                                <span className="block text-sm font-medium text-neutral-900">
                                    Catch-all zone
                                </span>

                                <span className="mt-0.5 block text-xs leading-5 text-neutral-500">
                                    Use this as a fallback area instead of
                                    defining matching rules.
                                </span>
                            </span>
                        </label>

                        <div className="border-t border-neutral-200 pt-5">
                            <label
                                htmlFor="priority"
                                className="text-sm font-medium text-neutral-900"
                            >
                                Priority
                            </label>

                            <input
                                id="priority"
                                type="number"
                                min={0}
                                max={1000000}
                                value={form.data.priority}
                                onChange={(event) =>
                                    form.setData(
                                        'priority',
                                        Number(
                                            event.target.value,
                                        ) || 0,
                                    )
                                }
                                className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                            />

                            <p className="mt-1 text-xs leading-5 text-neutral-500">
                                Lower numbers are evaluated first. Use a high
                                number such as 1000 for a fallback zone.
                            </p>

                            {form.errors.priority && (
                                <p className="mt-1 text-sm text-red-600">
                                    {form.errors.priority}
                                </p>
                            )}
                        </div>
                    </div>
                </section>

                <section className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="inline-flex w-full items-center justify-center rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {form.processing
                            ? 'Saving...'
                            : submitLabel}
                    </button>
                </section>
            </div>
        </form>
    )
}