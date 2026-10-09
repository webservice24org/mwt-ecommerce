import { Building2, Copyright } from 'lucide-react'

import type { FooterBrandConfig, FooterCopyrightConfig } from '@/types/footer-builder'

interface Props {
    brand: FooterBrandConfig
    copyright: FooterCopyrightConfig
    disabled?: boolean

    onBrandChange: (value: FooterBrandConfig) => void

    onCopyrightChange: (value: FooterCopyrightConfig) => void
}

export default function SharedFooterFields({
    brand,
    copyright,
    disabled = false,
    onBrandChange,
    onCopyrightChange,
}: Props) {
    const currentYear = new Date().getFullYear()

    return (
        <div className="grid min-w-0 grid-cols-1 gap-6 xl:grid-cols-2">
            <section className="min-w-0 overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                <div className="flex items-start gap-3 border-b border-neutral-200 p-5 sm:p-6">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700">
                        <Building2 className="h-5 w-5" strokeWidth={1.9} />
                    </div>

                    <div className="min-w-0">
                        <h2 className="text-base font-semibold text-neutral-950">Brand</h2>

                        <p className="mt-1 text-sm leading-6 text-neutral-500">
                            Shared store identity displayed by footer designs.
                        </p>
                    </div>
                </div>

                <div className="space-y-5 p-5 sm:p-6">
                    <div>
                        <label
                            htmlFor="footer-brand-name"
                            className="block text-sm font-medium text-neutral-800"
                        >
                            Brand name
                        </label>

                        <input
                            id="footer-brand-name"
                            type="text"
                            value={brand.name}
                            disabled={disabled}
                            required
                            maxLength={160}
                            onChange={(event) =>
                                onBrandChange({
                                    ...brand,

                                    name: event.target.value,
                                })
                            }
                            className="mt-2 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none transition placeholder:text-neutral-400 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-500"
                            placeholder="Your store name"
                        />

                        <div className="mt-1 flex items-center justify-between gap-3 text-xs text-neutral-400">
                            <span>Required</span>

                            <span>
                                {brand.name.length}
                                /160
                            </span>
                        </div>
                    </div>

                    <div>
                        <label
                            htmlFor="footer-brand-description"
                            className="block text-sm font-medium text-neutral-800"
                        >
                            Brand description
                        </label>

                        <textarea
                            id="footer-brand-description"
                            value={brand.description}
                            disabled={disabled}
                            maxLength={1000}
                            rows={5}
                            onChange={(event) =>
                                onBrandChange({
                                    ...brand,

                                    description: event.target.value,
                                })
                            }
                            className="mt-2 block w-full min-w-0 resize-y rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm leading-6 text-neutral-900 outline-none transition placeholder:text-neutral-400 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-500"
                            placeholder="Short description of your store or brand."
                        />

                        <div className="mt-1 flex items-center justify-between gap-3 text-xs text-neutral-400">
                            <span>Optional</span>

                            <span>
                                {brand.description.length}
                                /1000
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <section className="min-w-0 overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                <div className="flex items-start gap-3 border-b border-neutral-200 p-5 sm:p-6">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700">
                        <Copyright className="h-5 w-5" strokeWidth={1.9} />
                    </div>

                    <div className="min-w-0">
                        <h2 className="text-base font-semibold text-neutral-950">Copyright</h2>

                        <p className="mt-1 text-sm leading-6 text-neutral-500">
                            Shared copyright information displayed in the bottom footer.
                        </p>
                    </div>
                </div>

                <div className="space-y-5 p-5 sm:p-6">
                    <div>
                        <label
                            htmlFor="footer-copyright-name"
                            className="block text-sm font-medium text-neutral-800"
                        >
                            Copyright name
                        </label>

                        <input
                            id="footer-copyright-name"
                            type="text"
                            value={copyright.name}
                            disabled={disabled}
                            required
                            maxLength={160}
                            onChange={(event) =>
                                onCopyrightChange({
                                    ...copyright,

                                    name: event.target.value,
                                })
                            }
                            className="mt-2 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none transition placeholder:text-neutral-400 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-500"
                            placeholder="Your company or store name"
                        />

                        <div className="mt-1 flex items-center justify-between gap-3 text-xs text-neutral-400">
                            <span>Required</span>

                            <span>
                                {copyright.name.length}
                                /160
                            </span>
                        </div>
                    </div>

                    <div>
                        <label
                            htmlFor="footer-copyright-suffix"
                            className="block text-sm font-medium text-neutral-800"
                        >
                            Copyright suffix
                        </label>

                        <input
                            id="footer-copyright-suffix"
                            type="text"
                            value={copyright.suffix}
                            disabled={disabled}
                            required
                            maxLength={160}
                            onChange={(event) =>
                                onCopyrightChange({
                                    ...copyright,

                                    suffix: event.target.value,
                                })
                            }
                            className="mt-2 block h-10 w-full min-w-0 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none transition placeholder:text-neutral-400 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-500"
                            placeholder="All rights reserved."
                        />

                        <div className="mt-1 flex items-center justify-between gap-3 text-xs text-neutral-400">
                            <span>Required</span>

                            <span>
                                {copyright.suffix.length}
                                /160
                            </span>
                        </div>
                    </div>

                    <div className="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                        <p className="text-xs font-bold uppercase tracking-[0.12em] text-neutral-400">
                            Preview
                        </p>

                        <p className="mt-2 break-words text-sm leading-6 text-neutral-700">
                            © {currentYear} {copyright.name || 'Your Store'}.{' '}
                            {copyright.suffix || 'All rights reserved.'}
                        </p>
                    </div>
                </div>
            </section>
        </div>
    )
}
