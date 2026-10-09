import { Monitor, Smartphone, Tablet } from 'lucide-react'
import { useState } from 'react'

import LuxeNewsletterPreview from '@/Components/Admin/FooterBuilder/Preview/LuxeNewsletterPreview'
import MarketplaceTrustPreview from '@/Components/Admin/FooterBuilder/Preview/MarketplaceTrustPreview'
import MinimalLocalizationPreview from '@/Components/Admin/FooterBuilder/Preview/MinimalLocalizationPreview'
import type { FooterConfig, FooterPreviewDevice, FooterTemplateKey } from '@/types/footer-builder'

interface Props {
    template: FooterTemplateKey
    config: FooterConfig
    isEnabled: boolean
}

const templateLabels: Record<FooterTemplateKey, string> = {
    luxe_newsletter: 'Luxe Newsletter',

    minimal_localized: 'Minimal Localization',

    marketplace_trust: 'Marketplace Trust',
}

const previewWidths: Record<FooterPreviewDevice, number> = {
    desktop: 1120,
    tablet: 768,
    mobile: 390,
}

const deviceOptions: {
    value: FooterPreviewDevice
    label: string
    icon: typeof Monitor
}[] = [
    {
        value: 'desktop',
        label: 'Desktop',
        icon: Monitor,
    },
    {
        value: 'tablet',
        label: 'Tablet',
        icon: Tablet,
    },
    {
        value: 'mobile',
        label: 'Mobile',
        icon: Smartphone,
    },
]

export default function FooterLivePreview({ template, config, isEnabled }: Props) {
    const [device, setDevice] = useState<FooterPreviewDevice>('desktop')

    const width = previewWidths[device]

    return (
        <section className="min-w-0 overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
            <div className="flex flex-col gap-4 border-b border-neutral-200 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div className="min-w-0">
                    <h2 className="text-base font-semibold text-neutral-950">Live preview</h2>

                    <p className="mt-1 max-w-2xl text-sm leading-6 text-neutral-500">
                        Preview unsaved footer changes at desktop, tablet, and mobile widths.
                    </p>
                </div>

                <div
                    role="group"
                    aria-label="Preview device"
                    className="inline-flex w-fit rounded-lg border border-neutral-200 bg-neutral-50 p-1"
                >
                    {deviceOptions.map((option) => {
                        const Icon = option.icon

                        const active = device === option.value

                        return (
                            <button
                                key={option.value}
                                type="button"
                                aria-pressed={active}
                                onClick={() => setDevice(option.value)}
                                className={[
                                    'inline-flex h-9 items-center justify-center gap-2 rounded-md px-3 text-xs font-semibold transition',
                                    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-1',
                                    active
                                        ? 'bg-white text-neutral-950 shadow-sm ring-1 ring-neutral-200'
                                        : 'text-neutral-500 hover:text-neutral-800',
                                ].join(' ')}
                            >
                                <Icon aria-hidden="true" className="h-4 w-4" strokeWidth={1.8} />

                                <span className="hidden sm:inline">{option.label}</span>
                            </button>
                        )
                    })}
                </div>
            </div>

            <div className="border-b border-neutral-200 bg-neutral-50 px-5 py-3 sm:px-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-neutral-600 ring-1 ring-neutral-200">
                            {templateLabels[template]}
                        </span>

                        <span className="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-neutral-500 ring-1 ring-neutral-200">
                            {width}px
                        </span>
                    </div>

                    <span
                        className={[
                            'inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-semibold',
                            isEnabled
                                ? 'bg-emerald-50 text-emerald-700'
                                : 'bg-neutral-200 text-neutral-600',
                        ].join(' ')}
                    >
                        <span
                            className={[
                                'h-1.5 w-1.5 rounded-full',
                                isEnabled ? 'bg-emerald-500' : 'bg-neutral-400',
                            ].join(' ')}
                        />

                        {isEnabled ? 'Enabled' : 'Disabled'}
                    </span>
                </div>
            </div>

            <div className="min-w-0 overflow-x-auto bg-neutral-100 p-4 sm:p-6">
                <div
                    className={[
                        'mx-auto shrink-0 overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-black/5',
                        'transition-[width] duration-300',
                        !isEnabled ? 'opacity-75' : '',
                    ].join(' ')}
                    style={{
                        width: `${width}px`,

                        maxWidth: 'none',
                    }}
                >
                    {!isEnabled && (
                        <div className="border-b border-amber-200 bg-amber-50 px-4 py-2 text-center text-xs font-semibold text-amber-800">
                            Storefront footer disabled — preview remains visible for editing.
                        </div>
                    )}

                    <FooterPreviewRenderer template={template} config={config} device={device} />
                </div>
            </div>

            <div className="border-t border-neutral-200 bg-white px-5 py-3 text-xs leading-5 text-neutral-500 sm:px-6">
                All three Footer Builder designs now use their real live-preview renderers.
            </div>
        </section>
    )
}

function FooterPreviewRenderer({
    template,
    config,
    device,
}: {
    template: FooterTemplateKey
    config: FooterConfig
    device: FooterPreviewDevice
}) {
    switch (template) {
        case 'luxe_newsletter':
            return <LuxeNewsletterPreview config={config} device={device} />

        case 'minimal_localized':
            return <MinimalLocalizationPreview config={config} device={device} />

        case 'marketplace_trust':
            return <MarketplaceTrustPreview config={config} device={device} />
    }
}
