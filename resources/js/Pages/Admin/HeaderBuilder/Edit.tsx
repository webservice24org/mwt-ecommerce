import { Head, router } from '@inertiajs/react'
import {
    AlertCircle,
    Eye,
    LockKeyhole,
    MonitorCog,
    PanelTop,
    Power,
    RotateCcw,
    Save,
    Settings2,
    Monitor,
    Tablet,
    Smartphone,
} from 'lucide-react'

import { useState } from 'react'
import type { FormEvent } from 'react'

import AnnouncementBarEditor from '@/Components/Admin/HeaderBuilder/AnnouncementBarEditor'
import BrandLogoEditor from '@/Components/Admin/HeaderBuilder/BrandLogoEditor'
import SearchSettingsEditor from '@/Components/Admin/HeaderBuilder/SearchSettingsEditor'
import MainNavigationEditor from '@/Components/Admin/HeaderBuilder/MainNavigationEditor'
import MegaMenuEditor from '@/Components/Admin/HeaderBuilder/MegaMenuEditor'
import HeaderActionsEditor from '@/Components/Admin/HeaderBuilder/HeaderActionsEditor'
import AccountMenuEditor from '@/Components/Admin/HeaderBuilder/AccountMenuEditor'
import MobileNavigationEditor from '@/Components/Admin/HeaderBuilder/MobileNavigationEditor'
import HeaderDesign1Preview from '@/Components/Admin/HeaderBuilder/Preview/HeaderDesign1Preview'
import HeaderTemplateSelector from '@/Components/Admin/HeaderBuilder/HeaderTemplateSelector'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type {
    HeaderAbilities,
    HeaderAccountConfig,
    HeaderActionsConfig,
    HeaderAnnouncementConfig,
    HeaderBrandConfig,
    HeaderConfig,
    HeaderData,
    HeaderMegaMenuConfig,
    HeaderMobileConfig,
    HeaderNavigationConfig,
    HeaderSearchConfig,
    HeaderTemplateKey,
    HeaderTemplateOption,
    HeaderPreviewDevice,
} from '@/types/header-builder'

interface Props {
    header: HeaderData
    templates: HeaderTemplateOption[]
    abilities: HeaderAbilities
}

export default function Edit({ header, templates, abilities }: Props) {
    const [template, setTemplate] = useState<HeaderTemplateKey>(header.template)

    const [previewDevice, setPreviewDevice] = useState<HeaderPreviewDevice>('desktop')

    const [isEnabled, setIsEnabled] = useState(header.is_enabled)

    const [config, setConfig] = useState<HeaderConfig>(header.config)

    const [processing, setProcessing] = useState(false)

    const [errors, setErrors] = useState<Record<string, string>>({})

    const readOnly = !abilities.update

    const enabledChanged = isEnabled !== header.is_enabled

    const templateChanged = template !== header.template

    const configChanged = JSON.stringify(config) !== JSON.stringify(header.config)

    const isDirty = enabledChanged || templateChanged || configChanged

    const selectedTemplate = templates.find((item) => item.key === template) ?? null

    const toggleEnabled = () => {
        if (readOnly || processing) {
            return
        }

        setIsEnabled((current) => !current)
    }

    const selectTemplate = (value: HeaderTemplateKey) => {
        if (readOnly || processing || value === template) {
            return
        }

        setTemplate(value)
    }

    const updateAnnouncement = (value: HeaderAnnouncementConfig) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            announcement: value,
        }))
    }

    const updateBrand = (value: HeaderBrandConfig) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            brand: value,
        }))
    }

    const updateSearch = (value: HeaderSearchConfig) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            search: value,
        }))
    }

    const updateNavigation = (value: HeaderNavigationConfig) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            navigation: value,
        }))
    }

    const updateMegaMenu = (value: HeaderMegaMenuConfig) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            mega_menu: value,
        }))
    }

    const updateActions = (value: HeaderActionsConfig) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            actions: value,
        }))
    }

    const updateAccount = (value: HeaderAccountConfig) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            actions: {
                ...current.actions,

                account: value,
            },
        }))
    }

    const updateMobile = (value: HeaderMobileConfig) => {
        if (readOnly || processing) {
            return
        }

        setConfig((current) => ({
            ...current,

            mobile: value,
        }))
    }

    const revertChanges = () => {
        if (readOnly || processing) {
            return
        }

        setTemplate(header.template)

        setIsEnabled(header.is_enabled)

        setConfig(header.config)

        setErrors({})
    }

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        if (readOnly || processing || !isDirty) {
            return
        }

        setProcessing(true)

        setErrors({})

        router.put(
            route('admin.website-settings.header-builder.update'),
            {
                template,

                config: config as never,

                is_enabled: isEnabled,
            },
            {
                preserveScroll: true,

                /*
                 * Rebuild this page from the persisted
                 * server props after a successful save.
                 *
                 * This keeps those props as the new
                 * clean baseline without synchronizing
                 * local state from a useEffect.
                 */
                preserveState: false,

                onError: (responseErrors) => {
                    setErrors(responseErrors)
                },

                onFinish: () => {
                    setProcessing(false)
                },
            },
        )
    }

    return (
        <>
            <Head title="Header Builder" />

            <AdminLayout
                title="Header Builder"
                description="Choose and configure the global storefront header used across your ecommerce website."
                actions={
                    abilities.update ? (
                        <div className="flex min-w-0 flex-wrap items-center justify-end gap-3">
                            {isDirty && (
                                <span className="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
                                    Unsaved changes
                                </span>
                            )}

                            {isDirty && (
                                <button
                                    type="button"
                                    disabled={processing}
                                    onClick={revertChanges}
                                    className="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-neutral-200 bg-white px-4 text-sm font-medium text-neutral-600 transition hover:bg-neutral-50 hover:text-neutral-950 disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2"
                                >
                                    <RotateCcw
                                        className="h-4 w-4"
                                        strokeWidth={1.9}
                                        aria-hidden="true"
                                    />
                                    Revert
                                </button>
                            )}

                            <button
                                type="submit"
                                form="header-builder-form"
                                disabled={processing || !isDirty}
                                className="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2"
                            >
                                {processing ? (
                                    <span
                                        aria-hidden="true"
                                        className="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"
                                    />
                                ) : (
                                    <Save
                                        className="h-4 w-4"
                                        strokeWidth={1.9}
                                        aria-hidden="true"
                                    />
                                )}

                                {processing ? 'Saving...' : 'Save Header'}
                            </button>
                        </div>
                    ) : (
                        <div className="inline-flex min-h-10 items-center gap-2 rounded-lg border border-neutral-200 bg-white px-4 text-sm font-medium text-neutral-500 shadow-sm">
                            <Eye className="h-4 w-4" strokeWidth={1.9} aria-hidden="true" />
                            Read only
                        </div>
                    )
                }
            >
                <form id="header-builder-form" onSubmit={submit} className="min-w-0 space-y-6">
                    {!abilities.update && <ReadOnlyNotice />}

                    {Object.keys(errors).length > 0 && <ValidationNotice errors={errors} />}

                    <HeaderStatusCard
                        isEnabled={isEnabled}
                        selectedTemplate={selectedTemplate}
                        readOnly={readOnly}
                        processing={processing}
                        onToggle={toggleEnabled}
                    />

                    <AnnouncementBarEditor
                        value={config.announcement}
                        disabled={readOnly || processing}
                        onChange={updateAnnouncement}
                    />

                    <BrandLogoEditor
                        value={config.brand}
                        disabled={readOnly || processing}
                        onChange={updateBrand}
                    />

                    <SearchSettingsEditor
                        value={config.search}
                        disabled={readOnly || processing}
                        onChange={updateSearch}
                    />

                    <MainNavigationEditor
                        value={config.navigation}
                        disabled={readOnly || processing}
                        onChange={updateNavigation}
                    />

                    <MegaMenuEditor
                        value={config.mega_menu}
                        disabled={readOnly || processing}
                        onChange={updateMegaMenu}
                    />
                    <HeaderActionsEditor
                        value={config.actions}
                        disabled={readOnly || processing}
                        onChange={updateActions}
                    />

                    <AccountMenuEditor
                        value={config.actions.account}
                        disabled={readOnly || processing}
                        onChange={updateAccount}
                    />

                    <MobileNavigationEditor
                        value={config.mobile}
                        disabled={readOnly || processing}
                        onChange={updateMobile}
                    />
                    <div className="grid min-w-0 grid-cols-1 gap-6 xl:grid-cols-12">
                        <div className="min-w-0 xl:col-span-7">
                            <HeaderTemplateSelector
                                templates={templates}
                                selected={template}
                                disabled={readOnly || processing}
                                onSelect={selectTemplate}
                            />
                        </div>

                        <div className="min-w-0 xl:col-span-5">
                            <ConfigurationOverview
                                config={config}
                                template={template}
                                selectedTemplate={selectedTemplate}
                            />
                        </div>
                    </div>

                    <PreviewPanel
                        config={config}
                        template={template}
                        selectedTemplate={selectedTemplate}
                        isEnabled={isEnabled}
                        device={previewDevice}
                        onDeviceChange={setPreviewDevice}
                    />

                    {abilities.update && (
                        <div className="flex min-w-0 flex-col gap-3 rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
                            <div className="min-w-0">
                                <p className="text-sm font-semibold text-neutral-950">
                                    Header configuration
                                </p>

                                <p className="mt-1 text-sm leading-6 text-neutral-500">
                                    {isDirty
                                        ? 'You have unsaved Header Builder changes.'
                                        : 'All Header Builder changes are saved.'}
                                </p>
                            </div>

                            <div className="flex shrink-0 flex-wrap items-center gap-3">
                                {isDirty && (
                                    <button
                                        type="button"
                                        disabled={processing}
                                        onClick={revertChanges}
                                        className="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-neutral-200 bg-white px-4 text-sm font-medium text-neutral-600 transition hover:bg-neutral-50 hover:text-neutral-950 disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2"
                                    >
                                        <RotateCcw
                                            className="h-4 w-4"
                                            strokeWidth={1.9}
                                            aria-hidden="true"
                                        />
                                        Revert
                                    </button>
                                )}

                                <button
                                    type="submit"
                                    disabled={processing || !isDirty}
                                    className="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2"
                                >
                                    {processing ? (
                                        <span
                                            aria-hidden="true"
                                            className="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"
                                        />
                                    ) : (
                                        <Save
                                            className="h-4 w-4"
                                            strokeWidth={1.9}
                                            aria-hidden="true"
                                        />
                                    )}

                                    {processing ? 'Saving...' : 'Save Header'}
                                </button>
                            </div>
                        </div>
                    )}
                </form>
            </AdminLayout>
        </>
    )
}

function ReadOnlyNotice() {
    return (
        <section className="flex min-w-0 items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                <LockKeyhole className="h-4 w-4" strokeWidth={1.9} aria-hidden="true" />
            </div>

            <div className="min-w-0">
                <h2 className="text-sm font-semibold text-amber-950">Read-only access</h2>

                <p className="mt-1 text-sm leading-6 text-amber-800">
                    You can review the current header settings, but your administrator role does not
                    allow changing the global storefront header.
                </p>
            </div>
        </section>
    )
}

function ValidationNotice({ errors }: { errors: Record<string, string> }) {
    const entries = Object.entries(errors)

    return (
        <section
            role="alert"
            aria-live="polite"
            className="flex min-w-0 items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4"
        >
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-700">
                <AlertCircle className="h-4 w-4" strokeWidth={1.9} aria-hidden="true" />
            </div>

            <div className="min-w-0">
                <h2 className="text-sm font-semibold text-red-950">Header could not be saved</h2>

                <ul className="mt-2 space-y-1 text-sm leading-6 text-red-800">
                    {entries.map(([key, message]) => (
                        <li key={key} className="break-words">
                            {message}
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    )
}

function HeaderStatusCard({
    isEnabled,
    selectedTemplate,
    readOnly,
    processing,
    onToggle,
}: {
    isEnabled: boolean
    selectedTemplate: HeaderTemplateOption | null
    readOnly: boolean
    processing: boolean
    onToggle: () => void
}) {
    return (
        <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
            <div className="flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div className="flex min-w-0 items-start gap-3">
                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700">
                        <PanelTop className="h-5 w-5" strokeWidth={1.9} aria-hidden="true" />
                    </div>

                    <div className="min-w-0">
                        <div className="flex min-w-0 flex-wrap items-center gap-2">
                            <h2 className="text-base font-semibold text-neutral-950">
                                Global storefront header
                            </h2>

                            <HeaderStatusBadge enabled={isEnabled} />
                        </div>

                        <p className="mt-1 max-w-2xl text-sm leading-6 text-neutral-500">
                            Enable or disable the global storefront header while preserving its
                            saved configuration.
                        </p>
                    </div>
                </div>

                <div className="flex min-w-0 flex-col gap-3 sm:items-end">
                    <div className="min-w-0 sm:text-right">
                        <p className="text-[11px] font-semibold uppercase tracking-[0.12em] text-neutral-400">
                            Selected template
                        </p>

                        <p className="mt-1 break-words text-sm font-semibold text-neutral-900">
                            {selectedTemplate?.label ?? 'Unknown template'}
                        </p>
                    </div>

                    <button
                        type="button"
                        disabled={readOnly || processing}
                        aria-pressed={isEnabled}
                        onClick={onToggle}
                        className={[
                            'inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition-colors',
                            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2',
                            isEnabled
                                ? 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'
                                : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100',
                            readOnly || processing ? 'cursor-not-allowed opacity-50' : '',
                        ].join(' ')}
                    >
                        <Power className="h-4 w-4" strokeWidth={1.9} aria-hidden="true" />

                        {isEnabled ? 'Disable Header' : 'Enable Header'}
                    </button>
                </div>
            </div>
        </section>
    )
}

function HeaderStatusBadge({ enabled }: { enabled: boolean }) {
    return (
        <span
            className={[
                'inline-flex shrink-0 items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold',
                enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-neutral-100 text-neutral-500',
            ].join(' ')}
        >
            <span
                aria-hidden="true"
                className={[
                    'h-2 w-2 rounded-full',
                    enabled ? 'bg-emerald-500' : 'bg-neutral-400',
                ].join(' ')}
            />

            {enabled ? 'Enabled' : 'Disabled'}
        </span>
    )
}

function ConfigurationOverview({
    config,
    template,
    selectedTemplate,
}: {
    config: HeaderConfig
    template: HeaderTemplateKey
    selectedTemplate: HeaderTemplateOption | null
}) {
    const sections = [
        {
            label: 'Announcement',
            enabled: config.announcement.enabled,
        },
        {
            label: 'Search',
            enabled: config.search.enabled,
        },
        {
            label: 'Navigation',
            enabled: config.navigation.enabled,
        },
        {
            label: 'Mega Menu',
            enabled: config.mega_menu.enabled,
        },
        {
            label: 'Wishlist',
            enabled: config.actions.wishlist.enabled,
        },
        {
            label: 'Cart',
            enabled: config.actions.cart.enabled,
        },
        {
            label: 'Account',
            enabled: config.actions.account.enabled,
        },
    ]

    return (
        <section className="h-full overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
            <div className="border-b border-neutral-100 p-5 sm:p-6">
                <div className="flex items-start gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700">
                        <Settings2 className="h-5 w-5" strokeWidth={1.9} aria-hidden="true" />
                    </div>

                    <div className="min-w-0">
                        <h2 className="text-sm font-semibold text-neutral-950">Configuration</h2>

                        <p className="mt-1 text-sm leading-6 text-neutral-500">
                            Current working Header Builder configuration.
                        </p>
                    </div>
                </div>
            </div>

            <div className="p-5 sm:p-6">
                <div className="rounded-xl border border-neutral-100 bg-neutral-50 p-4">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.1em] text-neutral-400">
                        Working template
                    </p>

                    <p className="mt-1 break-words text-sm font-semibold text-neutral-950">
                        {selectedTemplate?.label ?? template}
                    </p>

                    <p className="mt-1 break-all font-mono text-[11px] text-neutral-400">
                        {template}
                    </p>
                </div>

                <dl className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                    {sections.map((section) => (
                        <div
                            key={section.label}
                            className="flex min-w-0 items-center justify-between gap-3 rounded-xl border border-neutral-100 bg-neutral-50 px-3 py-3"
                        >
                            <dt className="min-w-0 truncate text-sm font-medium text-neutral-700">
                                {section.label}
                            </dt>

                            <dd
                                className={[
                                    'shrink-0 rounded-full px-2 py-1 text-[10px] font-bold uppercase tracking-wide',
                                    section.enabled
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-neutral-200 text-neutral-500',
                                ].join(' ')}
                            >
                                {section.enabled ? 'On' : 'Off'}
                            </dd>
                        </div>
                    ))}
                </dl>

                <div className="mt-4 rounded-xl border border-neutral-100 p-4">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.1em] text-neutral-400">
                        Brand
                    </p>

                    <p className="mt-1 break-words text-sm font-semibold text-neutral-900">
                        {config.brand.name}

                        {config.brand.accent ?? ''}
                    </p>
                </div>
            </div>
        </section>
    )
}

function PreviewPanel({
    config,
    template,
    selectedTemplate,
    isEnabled,
    device,
    onDeviceChange,
}: {
    config: HeaderConfig
    template: HeaderTemplateKey
    selectedTemplate: HeaderTemplateOption | null
    isEnabled: boolean
    device: HeaderPreviewDevice
    onDeviceChange: (device: HeaderPreviewDevice) => void
}) {
    return (
        <section className="min-w-0 overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
            <div className="flex flex-col gap-4 border-b border-neutral-100 p-5 lg:flex-row lg:items-center lg:justify-between sm:p-6">
                <div className="flex min-w-0 items-start gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                        <MonitorCog className="h-5 w-5" strokeWidth={1.9} aria-hidden="true" />
                    </div>

                    <div className="min-w-0">
                        <div className="flex min-w-0 flex-wrap items-center gap-2">
                            <h2 className="text-sm font-semibold text-neutral-950">
                                Header Design 1 preview
                            </h2>

                            <span className="rounded-full bg-neutral-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-neutral-500">
                                {device === 'desktop'
                                    ? 'Desktop'
                                    : device === 'tablet'
                                      ? 'Tablet'
                                      : 'Mobile'}
                            </span>
                        </div>

                        <p className="mt-1 text-sm leading-6 text-neutral-500">
                            Previewing{' '}
                            <span className="font-semibold text-neutral-700">
                                {selectedTemplate?.label ?? template}
                            </span>{' '}
                            using the current unsaved Header Builder configuration.
                        </p>
                    </div>
                </div>

                <div className="flex min-w-0 flex-wrap items-center gap-3">
                    <PreviewDeviceSelector value={device} onChange={onDeviceChange} />

                    <HeaderStatusBadge enabled={isEnabled} />
                </div>
            </div>

            <div className="min-w-0 overflow-hidden bg-neutral-100 p-4 sm:p-6">
                <PreviewViewport device={device}>
                    <HeaderDesign1Preview
                        key={device}
                        config={config}
                        isEnabled={isEnabled}
                        device={device}
                    />
                </PreviewViewport>

                {!isEnabled && (
                    <p className="mt-4 text-center text-xs font-semibold text-amber-600">
                        The global header is currently disabled. The preview remains visible so its
                        configuration can still be edited.
                    </p>
                )}
            </div>
        </section>
    )
}

function PreviewDeviceSelector({
    value,
    onChange,
}: {
    value: HeaderPreviewDevice
    onChange: (value: HeaderPreviewDevice) => void
}) {
    return (
        <div
            role="group"
            aria-label="Preview device"
            className="inline-flex items-center rounded-xl border border-neutral-200 bg-neutral-50 p-1"
        >
            <button
                type="button"
                aria-pressed={value === 'desktop'}
                onClick={() => onChange('desktop')}
                className={[
                    'inline-flex min-h-9 items-center gap-2 rounded-lg px-3 text-xs font-semibold transition',
                    value === 'desktop'
                        ? 'bg-white text-neutral-950 shadow-sm'
                        : 'text-neutral-500 hover:text-neutral-900',
                ].join(' ')}
            >
                <Monitor className="h-4 w-4" strokeWidth={1.9} aria-hidden="true" />
                Desktop
            </button>

            <button
                type="button"
                aria-pressed={value === 'tablet'}
                onClick={() => onChange('tablet')}
                className={[
                    'inline-flex min-h-9 items-center gap-2 rounded-lg px-3 text-xs font-semibold transition',
                    value === 'tablet'
                        ? 'bg-white text-neutral-950 shadow-sm'
                        : 'text-neutral-500 hover:text-neutral-900',
                ].join(' ')}
            >
                <Tablet className="h-4 w-4" strokeWidth={1.9} aria-hidden="true" />
                Tablet
            </button>

            <button
                type="button"
                aria-pressed={value === 'mobile'}
                onClick={() => onChange('mobile')}
                className={[
                    'inline-flex min-h-9 items-center gap-2 rounded-lg px-3 text-xs font-semibold transition',
                    value === 'mobile'
                        ? 'bg-white text-neutral-950 shadow-sm'
                        : 'text-neutral-500 hover:text-neutral-900',
                ].join(' ')}
            >
                <Smartphone className="h-4 w-4" strokeWidth={1.9} aria-hidden="true" />
                Mobile
            </button>
        </div>
    )
}

function PreviewViewport({
    device,
    children,
}: {
    device: HeaderPreviewDevice
    children: React.ReactNode
}) {
    const widthClass =
        device === 'mobile'
            ? 'max-w-[390px]'
            : device === 'tablet'
              ? 'max-w-[820px]'
              : 'max-w-[1440px]'

    const label =
        device === 'mobile'
            ? 'Mobile viewport · 390px'
            : device === 'tablet'
              ? 'Tablet viewport · 820px'
              : null

    return (
        <div className="flex w-full min-w-0 justify-center">
            <div
                className={['w-full min-w-0 transition-[max-width] duration-300', widthClass].join(
                    ' ',
                )}
            >
                {label && (
                    <div className="mb-3 flex items-center justify-center">
                        <span className="rounded-full border border-neutral-200 bg-white px-3 py-1 text-[10px] font-semibold text-neutral-500 shadow-sm">
                            {label}
                        </span>
                    </div>
                )}

                {children}
            </div>
        </div>
    )
}
