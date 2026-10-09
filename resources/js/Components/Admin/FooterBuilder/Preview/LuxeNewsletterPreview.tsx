import {
    Award,
    BadgeCheck,
    CreditCard,
    Headphones,
    Lock,
    Package,
    RefreshCcw,
    ShieldCheck,
    Truck,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'

import {
    readFooterBrand,
    readFooterCopyright,
    readFooterDeveloper,
    readFooterLinkGroups,
    readFooterNewsletter,
    readFooterPaymentMethods,
    readFooterSocialLinks,
    readFooterValueProps,
} from '@/types/footer-builder'
import type { FooterConfig, FooterPreviewDevice, FooterValuePropIcon } from '@/types/footer-builder'

import SharedBottomFooter from '@/Components/FooterBuilder/SharedBottomFooter'

interface Props {
    config: FooterConfig
    device: FooterPreviewDevice
}

const valuePropIcons: Record<FooterValuePropIcon, LucideIcon> = {
    package: Package,
    truck: Truck,
    'shield-check': ShieldCheck,
    'refresh-ccw': RefreshCcw,
    headphones: Headphones,
    'credit-card': CreditCard,
    lock: Lock,
    'badge-check': BadgeCheck,
    award: Award,
}

export default function LuxeNewsletterPreview({ config, device }: Props) {
    const brand = readFooterBrand(config)

    const copyright = readFooterCopyright(config)

    const developer = readFooterDeveloper(config)

    const linkGroups = readFooterLinkGroups(config)

    const socialLinks = readFooterSocialLinks(config)

    const valueProps = readFooterValueProps(config)

    const newsletter = readFooterNewsletter(config)

    const paymentMethods = readFooterPaymentMethods(config)

    const mobile = device === 'mobile'

    const tablet = device === 'tablet'

    const desktop = device === 'desktop'

    const brandName = brand.name.trim() !== '' ? brand.name : 'Your Store'

    const brandInitial = brandName.charAt(0).toUpperCase()

    const mainPadding = mobile ? 'px-4 py-8' : tablet ? 'px-6 py-10' : 'px-10 py-12'

    const valuePadding = mobile ? 'px-4 py-5' : tablet ? 'px-6 py-6' : 'px-10 py-6'

    return (
        <footer
            aria-label="Luxe Newsletter footer preview"
            className="overflow-hidden bg-neutral-900 text-neutral-100"
        >
            {valueProps.length > 0 && (
                <div
                    className={['border-b border-neutral-800 bg-neutral-900', valuePadding].join(
                        ' ',
                    )}
                >
                    <div
                        className="grid gap-5"
                        style={{
                            gridTemplateColumns: valuePropGrid(device, valueProps.length),
                        }}
                    >
                        {valueProps.map((item, index) => {
                            const Icon = valuePropIcons[item.icon]

                            return (
                                <div
                                    key={index}
                                    className={[
                                        'flex min-w-0 gap-3',
                                        mobile
                                            ? 'flex-col items-center text-center'
                                            : 'items-center text-left',
                                    ].join(' ')}
                                >
                                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-indigo-500/20 bg-indigo-500/10 text-indigo-400">
                                        <Icon className="h-5 w-5" strokeWidth={1.8} />
                                    </div>

                                    <div className="min-w-0">
                                        <p className="break-words text-[11px] font-bold uppercase tracking-[0.08em] text-white">
                                            {item.title || 'Value proposition'}
                                        </p>

                                        <p className="mt-1 break-words text-[11px] leading-5 text-neutral-400">
                                            {item.description || 'Supporting description'}
                                        </p>
                                    </div>
                                </div>
                            )
                        })}
                    </div>
                </div>
            )}

            <div className={['border-b border-neutral-800 bg-neutral-900', mainPadding].join(' ')}>
                <div
                    className={[
                        'grid min-w-0 gap-10',
                        desktop ? 'grid-cols-[minmax(0,5fr)_minmax(0,7fr)]' : 'grid-cols-1',
                    ].join(' ')}
                >
                    <div className="min-w-0">
                        <BrandMark initial={brandInitial} name={brandName} />

                        {newsletter.enabled ? (
                            <>
                                {newsletter.description !== '' && (
                                    <p className="mt-4 max-w-md break-words text-xs leading-6 text-neutral-400">
                                        {newsletter.description}
                                    </p>
                                )}

                                <div
                                    className={[
                                        'mt-4 flex max-w-md gap-2',
                                        mobile ? 'flex-col' : 'items-center',
                                    ].join(' ')}
                                >
                                    <div className="min-w-0 flex-1 rounded-xl border border-neutral-800 bg-neutral-950 px-4 py-3 text-xs text-neutral-500">
                                        {newsletter.placeholder || 'Enter your email address'}
                                    </div>

                                    <div
                                        className={[
                                            'shrink-0 rounded-xl bg-indigo-600 px-5 py-3 text-center text-xs font-bold text-white shadow-lg shadow-indigo-600/10',
                                            mobile ? 'w-full' : '',
                                        ].join(' ')}
                                    >
                                        {newsletter.button_label || 'Subscribe'}
                                    </div>
                                </div>
                            </>
                        ) : (
                            brand.description !== '' && (
                                <p className="mt-4 max-w-md break-words text-xs leading-6 text-neutral-400">
                                    {brand.description}
                                </p>
                            )
                        )}

                        {newsletter.enabled && brand.description !== '' && (
                            <p className="mt-4 max-w-md break-words text-[11px] leading-5 text-neutral-500">
                                {brand.description}
                            </p>
                        )}

                        {paymentMethods.length > 0 && (
                            <div className="mt-5">
                                <p className="text-[9px] font-bold uppercase tracking-[0.12em] text-neutral-500">
                                    Accepted payments
                                </p>

                                <div className="mt-2 flex flex-wrap gap-2">
                                    {paymentMethods.map((method, index) => (
                                        <span
                                            key={`${method}-${index}`}
                                            className="inline-flex min-h-6 items-center rounded border border-neutral-700 bg-neutral-800 px-2 py-1 text-[9px] font-bold text-neutral-300"
                                        >
                                            {method || 'PAY'}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="min-w-0">
                        {linkGroups.length > 0 ? (
                            <div
                                className="grid min-w-0 gap-x-7 gap-y-8"
                                style={{
                                    gridTemplateColumns: linkGroupGrid(device, linkGroups.length),
                                }}
                            >
                                {linkGroups.map((group, groupIndex) => (
                                    <div key={groupIndex} className="min-w-0">
                                        <p className="break-words text-[11px] font-bold uppercase tracking-[0.08em] text-white">
                                            {group.heading || `Group ${groupIndex + 1}`}
                                        </p>

                                        {group.links.length > 0 ? (
                                            <div className="mt-4 space-y-2.5">
                                                {group.links.map((link, linkIndex) => (
                                                    <span
                                                        key={linkIndex}
                                                        className="block break-words text-xs leading-5 text-neutral-400"
                                                    >
                                                        {link.label || 'Link'}
                                                    </span>
                                                ))}
                                            </div>
                                        ) : (
                                            <p className="mt-3 text-[11px] italic text-neutral-600">
                                                No links
                                            </p>
                                        )}
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="rounded-xl border border-dashed border-neutral-700 bg-neutral-950/40 p-5 text-xs leading-5 text-neutral-500">
                                No footer link groups configured.
                            </div>
                        )}
                    </div>
                </div>
            </div>

            <SharedBottomFooter
                template="luxe_newsletter"
                copyright={copyright}
                socialLinks={socialLinks}
                developer={developer}
                brandName={brandName}
                device={device}
                mode="preview"
            />
        </footer>
    )
}

function BrandMark({ initial, name }: { initial: string; name: string }) {
    return (
        <div className="flex min-w-0 items-center gap-2">
            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-sm font-black text-white">
                {initial || 'S'}
            </span>

            <span className="min-w-0 break-words text-lg font-bold tracking-tight text-white">
                {name}
            </span>
        </div>
    )
}

function valuePropGrid(device: FooterPreviewDevice, count: number): string {
    if (count <= 1) {
        return 'minmax(0, 1fr)'
    }

    const columns =
        device === 'desktop' ? Math.min(count, 4) : device === 'tablet' ? Math.min(count, 2) : 1

    return `repeat(${columns}, minmax(0, 1fr))`
}

function linkGroupGrid(device: FooterPreviewDevice, count: number): string {
    if (count <= 1) {
        return 'minmax(0, 1fr)'
    }

    const columns =
        device === 'desktop' ? Math.min(count, 3) : device === 'tablet' ? Math.min(count, 2) : 1

    return `repeat(${columns}, minmax(0, 1fr))`
}
