import { Head, router } from '@inertiajs/react'
import { Eye, LockKeyhole, PanelBottom, Save } from 'lucide-react'
import { useState } from 'react'
import type { FormEvent } from 'react'

import DeveloperCreditEditor from '@/Components/Admin/FooterBuilder/DeveloperCreditEditor'
import FooterLivePreview from '@/Components/Admin/FooterBuilder/FooterLivePreview'
import FooterTemplateSelector from '@/Components/Admin/FooterBuilder/FooterTemplateSelector'
import LinkGroupEditor from '@/Components/Admin/FooterBuilder/LinkGroupEditor'
import LuxeNewsletterEditor from '@/Components/Admin/FooterBuilder/LuxeNewsletterEditor'
import MarketplaceTrustEditor from '@/Components/Admin/FooterBuilder/MarketplaceTrustEditor'
import MinimalLocalizationEditor from '@/Components/Admin/FooterBuilder/MinimalLocalizationEditor'
import SharedFooterFields from '@/Components/Admin/FooterBuilder/SharedFooterFields'
import SocialLinkEditor from '@/Components/Admin/FooterBuilder/SocialLinkEditor'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import {
    readFooterBrand,
    readFooterCertifications,
    readFooterCopyright,
    readFooterDeveloper,
    readFooterLinkGroups,
    readFooterLocalization,
    readFooterNewsletter,
    readFooterPaymentMethods,
    readFooterPopularLinks,
    readFooterPromotion,
    readFooterSocialLinks,
    readFooterValueProps,
} from '@/types/footer-builder'
import type {
    FooterAbilities,
    FooterBrandConfig,
    FooterConfig,
    FooterCopyrightConfig,
    FooterData,
    FooterDeveloperConfig,
    FooterLinkGroup,
    FooterLocalizationConfig,
    FooterNewsletterConfig,
    FooterPopularLink,
    FooterPromotionConfig,
    FooterSocialLink,
    FooterTemplateKey,
    FooterTemplateOption,
    FooterValueProp,
} from '@/types/footer-builder'

interface Props {
    footer: FooterData
    templates: FooterTemplateOption[]
    abilities: FooterAbilities
}

export default function Edit({ footer, templates, abilities }: Props) {
    const [template, setTemplate] = useState<FooterTemplateKey>(footer.template)

    const [config, setConfig] = useState<FooterConfig>(footer.config)

    const [isEnabled, setIsEnabled] = useState(footer.is_enabled)

    const [processing, setProcessing] = useState(false)

    const readOnly = !abilities.update

    const brand = readFooterBrand(config)

    const copyright = readFooterCopyright(config)

    const linkGroups = readFooterLinkGroups(config)

    const socialLinks = readFooterSocialLinks(config)

    const developer = readFooterDeveloper(config)

    const valueProps = readFooterValueProps(config)

    const newsletter = readFooterNewsletter(config)

    const paymentMethods = readFooterPaymentMethods(config)

    const localization = readFooterLocalization(config)

    const selectedTemplate = templates.find((item) => item.key === template) ?? null

    const templateChanged = template !== footer.template

    const enabledChanged = isEnabled !== footer.is_enabled

    const configChanged = JSON.stringify(config) !== JSON.stringify(footer.config)

    const isDirty = templateChanged || enabledChanged || configChanged

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        if (readOnly || processing || !isDirty) {
            return
        }

        setProcessing(true)

        router.put(
            route('admin.website-settings.footer-builder.update'),
            {
                template,

                config: config as never,

                is_enabled: isEnabled,
            },
            {
                preserveScroll: true,

                onFinish: () => {
                    setProcessing(false)
                },
            },
        )
    }

    const selectTemplate = (value: FooterTemplateKey) => {
        if (readOnly || processing || value === template) {
            return
        }

        setTemplate(value)
    }

    const revertTemplate = () => {
        if (readOnly || processing) {
            return
        }

        setTemplate(footer.template)
    }

    const updateBrand = (value: FooterBrandConfig) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            brand: {
                name: value.name,

                description: value.description,
            },
        }))
    }

    const updateCopyright = (value: FooterCopyrightConfig) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            copyright: {
                name: value.name,

                suffix: value.suffix,
            },
        }))
    }

    const updateLinkGroups = (value: FooterLinkGroup[]) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            link_groups: value,
        }))
    }

    const updateSocialLinks = (value: FooterSocialLink[]) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            social_links: value,
        }))
    }

    const updateDeveloper = (value: FooterDeveloperConfig) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            developer: {
                prefix: value.prefix,

                name: value.name,

                url: value.url,
            },
        }))
    }

    const updateValueProps = (value: FooterValueProp[]) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            value_props: value,
        }))
    }

    const updateNewsletter = (value: FooterNewsletterConfig) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            newsletter: {
                enabled: value.enabled,

                description: value.description,

                placeholder: value.placeholder,

                button_label: value.button_label,
            },
        }))
    }

    const updatePaymentMethods = (value: string[]) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            payment_methods: value,
        }))
    }

    const updateLocalization = (value: FooterLocalizationConfig) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            localization: {
                enabled: value.enabled,

                languages: value.languages,

                currencies: value.currencies,
            },
        }))
    }

    const promotion = readFooterPromotion(config)

    const popularLinks = readFooterPopularLinks(config)

    const certifications = readFooterCertifications(config)

    const updatePromotion = (value: FooterPromotionConfig) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            promotion: {
                enabled: value.enabled,

                badge: value.badge,

                message: value.message,

                code: value.code,

                button_label: value.button_label,

                button_url: value.button_url,
            },
        }))
    }

    const updatePopularLinks = (value: FooterPopularLink[]) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            popular_links: value,
        }))
    }

    const updateCertifications = (value: string[]) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            certifications: value,
        }))
    }

    return (
        <>
            <Head title="Footer Builder" />

            <AdminLayout
                title="Footer Builder"
                description="Choose the global storefront footer design and manage its configuration."
                actions={
                    abilities.update ? (
                        <button
                            type="submit"
                            form="footer-builder-form"
                            disabled={processing || !isDirty}
                            className="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-neutral-900 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Save className="h-4 w-4" strokeWidth={1.9} />

                            {processing ? 'Saving...' : 'Save Footer'}
                        </button>
                    ) : (
                        <div className="inline-flex h-10 items-center gap-2 rounded-lg border border-neutral-200 bg-white px-4 text-sm font-medium text-neutral-500">
                            <Eye className="h-4 w-4" strokeWidth={1.9} />
                            Read only
                        </div>
                    )
                }
            >
                <form id="footer-builder-form" onSubmit={submit} className="min-w-0 space-y-6">
                    {!abilities.update && (
                        <section className="flex min-w-0 gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                                <LockKeyhole className="h-4 w-4" strokeWidth={1.9} />
                            </div>

                            <div className="min-w-0">
                                <h2 className="text-sm font-semibold text-amber-950">
                                    Read-only access
                                </h2>

                                <p className="mt-1 text-sm leading-6 text-amber-800">
                                    You can review the current footer settings, but your
                                    administrator role does not allow changing the global footer.
                                </p>
                            </div>
                        </section>
                    )}

                    <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                        <div className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                            <div className="flex min-w-0 items-start gap-3">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700">
                                    <PanelBottom className="h-5 w-5" strokeWidth={1.9} />
                                </div>

                                <div className="min-w-0">
                                    <h2 className="text-base font-semibold text-neutral-950">
                                        Global footer
                                    </h2>

                                    <p className="mt-1 text-sm leading-6 text-neutral-500">
                                        Enable or disable the global storefront footer while
                                        preserving its saved configuration.
                                    </p>
                                </div>
                            </div>

                            <div className="flex shrink-0 items-center gap-3">
                                <span
                                    className={[
                                        'inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold',
                                        isEnabled
                                            ? 'bg-emerald-50 text-emerald-700'
                                            : 'bg-neutral-100 text-neutral-500',
                                    ].join(' ')}
                                >
                                    <span
                                        className={[
                                            'h-2 w-2 rounded-full',
                                            isEnabled ? 'bg-emerald-500' : 'bg-neutral-400',
                                        ].join(' ')}
                                    />

                                    {isEnabled ? 'Enabled' : 'Disabled'}
                                </span>

                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked={isEnabled}
                                    aria-label="Enable storefront footer"
                                    disabled={readOnly || processing}
                                    onClick={() => setIsEnabled((current) => !current)}
                                    className={[
                                        'relative inline-flex h-6 w-11 shrink-0 rounded-full transition-colors',
                                        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2',
                                        'disabled:cursor-not-allowed disabled:opacity-50',
                                        isEnabled ? 'bg-neutral-900' : 'bg-neutral-300',
                                    ].join(' ')}
                                >
                                    <span
                                        className={[
                                            'pointer-events-none absolute top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform',
                                            isEnabled ? 'translate-x-[22px]' : 'translate-x-0.5',
                                        ].join(' ')}
                                    />
                                </button>
                            </div>
                        </div>

                        {!isEnabled && (
                            <div className="border-t border-neutral-200 bg-neutral-50 px-5 py-3 text-sm text-neutral-600 sm:px-6">
                                The global footer is currently disabled. Its configuration is
                                preserved.
                            </div>
                        )}
                    </section>

                    <FooterTemplateSelector
                        templates={templates}
                        value={template}
                        savedValue={footer.template}
                        disabled={readOnly || processing}
                        onChange={selectTemplate}
                        onRevert={revertTemplate}
                    />

                    <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                        <div className="border-b border-neutral-200 p-5 sm:p-6">
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div className="min-w-0">
                                    <h2 className="text-base font-semibold text-neutral-950">
                                        Shared footer fields
                                    </h2>

                                    <p className="mt-1 max-w-3xl text-sm leading-6 text-neutral-500">
                                        These values are preserved across every footer design.
                                    </p>
                                </div>

                                <span className="w-fit shrink-0 rounded-full bg-neutral-100 px-3 py-1.5 text-xs font-semibold text-neutral-600">
                                    All designs
                                </span>
                            </div>
                        </div>

                        <div className="p-5 sm:p-6">
                            <SharedFooterFields
                                brand={brand}
                                copyright={copyright}
                                disabled={readOnly || processing}
                                onBrandChange={updateBrand}
                                onCopyrightChange={updateCopyright}
                            />
                        </div>
                    </section>

                    <LinkGroupEditor
                        groups={linkGroups}
                        disabled={readOnly || processing}
                        onChange={updateLinkGroups}
                    />

                    <SocialLinkEditor
                        links={socialLinks}
                        disabled={readOnly || processing}
                        onChange={updateSocialLinks}
                    />

                    <DeveloperCreditEditor
                        value={developer}
                        disabled={readOnly || processing}
                        onChange={updateDeveloper}
                    />

                    <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                        <div className="border-b border-neutral-200 p-5 sm:p-6">
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div className="min-w-0">
                                    <h2 className="text-base font-semibold text-neutral-950">
                                        Design-specific content
                                    </h2>

                                    <p className="mt-1 max-w-3xl text-sm leading-6 text-neutral-500">
                                        These fields depend on the currently selected footer design.
                                    </p>
                                </div>

                                {selectedTemplate && (
                                    <span className="w-fit shrink-0 rounded-full bg-neutral-100 px-3 py-1.5 text-xs font-semibold text-neutral-600">
                                        {selectedTemplate.label}
                                    </span>
                                )}
                            </div>
                        </div>

                        <div className="p-5 sm:p-6">
                            <div className="p-5 sm:p-6">
                                {template === 'luxe_newsletter' ? (
                                    <div className="min-w-0">
                                        <LuxeNewsletterEditor
                                            valueProps={valueProps}
                                            newsletter={newsletter}
                                            paymentMethods={paymentMethods}
                                            disabled={readOnly || processing}
                                            onValuePropsChange={updateValueProps}
                                            onNewsletterChange={updateNewsletter}
                                            onPaymentMethodsChange={updatePaymentMethods}
                                        />
                                    </div>
                                ) : template === 'minimal_localized' ? (
                                    <div className="min-w-0">
                                        <MinimalLocalizationEditor
                                            value={localization}
                                            disabled={readOnly || processing}
                                            onChange={updateLocalization}
                                        />
                                    </div>
                                ) : (
                                    <div className="min-w-0">
                                        <MarketplaceTrustEditor
                                            promotion={promotion}
                                            popularLinks={popularLinks}
                                            certifications={certifications}
                                            disabled={readOnly || processing}
                                            onPromotionChange={updatePromotion}
                                            onPopularLinksChange={updatePopularLinks}
                                            onCertificationsChange={updateCertifications}
                                        />
                                    </div>
                                )}
                            </div>
                        </div>
                    </section>

                    <FooterLivePreview template={template} config={config} isEnabled={isEnabled} />

                    {abilities.update && (
                        <div className="flex flex-col-reverse gap-3 border-t border-neutral-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                            <div className="min-w-0">
                                <p className="text-sm text-neutral-500">
                                    {isDirty
                                        ? 'You have unsaved changes.'
                                        : 'All changes are saved.'}
                                </p>

                                {templateChanged && (
                                    <p className="mt-1 text-xs text-amber-700">
                                        Footer design has changed and has not yet been saved.
                                    </p>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={processing || !isDirty}
                                className="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-neutral-900 px-5 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Save className="h-4 w-4" strokeWidth={1.9} />

                                {processing ? 'Saving...' : 'Save Footer'}
                            </button>
                        </div>
                    )}
                </form>
            </AdminLayout>
        </>
    )
}
