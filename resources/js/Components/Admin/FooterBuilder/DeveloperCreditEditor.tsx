import { Code2, ExternalLink } from 'lucide-react'

import type { FooterDeveloperConfig } from '@/types/footer-builder'

interface Props {
    value: FooterDeveloperConfig
    disabled?: boolean

    onChange: (value: FooterDeveloperConfig) => void
}

export default function DeveloperCreditEditor({ value, disabled = false, onChange }: Props) {
    return (
        <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
            <div className="flex min-w-0 items-start gap-3 border-b border-neutral-200 p-5 sm:p-6">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700">
                    <Code2 className="h-5 w-5" strokeWidth={1.9} />
                </div>

                <div className="min-w-0">
                    <h2 className="text-base font-semibold text-neutral-950">Developer credit</h2>

                    <p className="mt-1 max-w-3xl text-sm leading-6 text-neutral-500">
                        Configure the developer or agency attribution displayed in the shared bottom
                        footer.
                    </p>
                </div>
            </div>

            <div className="p-5 sm:p-6">
                <div className="grid min-w-0 grid-cols-1 gap-5 lg:grid-cols-2">
                    <div>
                        <label
                            htmlFor="footer-developer-prefix"
                            className="block text-sm font-medium text-neutral-800"
                        >
                            Prefix
                        </label>

                        <input
                            id="footer-developer-prefix"
                            type="text"
                            required
                            value={value.prefix}
                            disabled={disabled}
                            onChange={(event) =>
                                onChange({
                                    ...value,

                                    prefix: event.target.value,
                                })
                            }
                            placeholder="Created by"
                            className="mt-2 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none transition placeholder:text-neutral-400 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-500"
                        />

                        <p className="mt-1 text-xs text-neutral-400">
                            Text shown immediately before the developer name.
                        </p>
                    </div>

                    <div>
                        <label
                            htmlFor="footer-developer-name"
                            className="block text-sm font-medium text-neutral-800"
                        >
                            Developer name
                        </label>

                        <input
                            id="footer-developer-name"
                            type="text"
                            required
                            value={value.name}
                            disabled={disabled}
                            onChange={(event) =>
                                onChange({
                                    ...value,

                                    name: event.target.value,
                                })
                            }
                            placeholder="Your Agency"
                            className="mt-2 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none transition placeholder:text-neutral-400 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-500"
                        />

                        <p className="mt-1 text-xs text-neutral-400">
                            The linked developer, studio, or agency name.
                        </p>
                    </div>
                </div>

                <div className="mt-5">
                    <label
                        htmlFor="footer-developer-url"
                        className="block text-sm font-medium text-neutral-800"
                    >
                        Developer URL
                    </label>

                    <div className="relative mt-2">
                        <ExternalLink
                            aria-hidden="true"
                            className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400"
                        />

                        <input
                            id="footer-developer-url"
                            type="text"
                            required
                            value={value.url}
                            disabled={disabled}
                            onChange={(event) =>
                                onChange({
                                    ...value,

                                    url: event.target.value,
                                })
                            }
                            placeholder="https://example.com"
                            className="block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white pl-10 pr-3 text-sm text-neutral-900 outline-none transition placeholder:text-neutral-400 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-500"
                        />
                    </div>

                    <p className="mt-1 text-xs text-neutral-400">
                        Supports the same safe URL formats as the rest of the Footer Builder.
                    </p>
                </div>

                <div className="mt-6 rounded-xl border border-neutral-200 bg-neutral-950 p-5">
                    <p className="text-[10px] font-bold uppercase tracking-[0.14em] text-neutral-500">
                        Bottom footer preview
                    </p>

                    <div className="mt-4 flex min-w-0 flex-wrap items-center gap-2 font-mono text-xs text-neutral-400">
                        <LiveStatusDot />

                        <span className="break-words">
                            {value.prefix || 'Created by'}{' '}
                            <span className="font-bold text-amber-400">
                                {value.name || 'Developer'}
                            </span>
                        </span>
                    </div>

                    {value.url !== '' && (
                        <p className="mt-3 break-all text-[11px] text-neutral-600">{value.url}</p>
                    )}
                </div>

                <div className="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                    <div className="flex items-start gap-3">
                        <LiveStatusDot />

                        <p className="min-w-0 text-xs leading-5 text-emerald-800">
                            The live status dot is a built-in Footer Builder presentation detail. It
                            is intentionally not stored as a configurable developer setting.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    )
}

function LiveStatusDot() {
    return (
        <span className="relative mt-1 inline-flex h-2 w-2 shrink-0" aria-hidden="true">
            <span className="absolute inset-0 rounded-full bg-emerald-400 opacity-75 motion-safe:animate-ping" />

            <span className="relative inline-flex h-2 w-2 rounded-full bg-emerald-500" />
        </span>
    )
}
