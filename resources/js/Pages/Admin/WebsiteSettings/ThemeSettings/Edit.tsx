import AdminBrandingEditor from '@/Components/Admin/WebsiteSettings/AdminBrandingEditor'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import ProductPrice from '@/Components/Frontend/Catalog/ProductPrice'
import { addButtonClass, orderButtonClass } from '@/Components/Frontend/Catalog/ProductPurchaseActions'
import { normalizeProductCardTheme, productCardThemeProps } from '@/lib/productCardTheme'
import type { CardColorKey, CardRadiusKey, ProductCardTheme, TitleHoverDecoration } from '@/lib/productCardTheme'
import { router } from '@inertiajs/react'
import { LayoutGrid, Palette, Package, RotateCcw, Save, ShoppingCart } from 'lucide-react'
import { useState } from 'react'
import type { FormEvent } from 'react'

interface Props {
    productCards: unknown
    abilities: { update: boolean }
}

// Register additional real theme sections here when they are implemented.
const sections = [
    { key: 'product-cards', label: 'Product Cards', icon: LayoutGrid },
    { key: 'admin-branding', label: 'Admin Branding', icon: Palette },
] as const
const cardFields: [CardColorKey, string, string][] = [
    ['background_color', 'Card background', '#ffffff'],
    ['title_color', 'Product title', '#0a0a0a'],
    ['title_hover_color', 'Product title hover color', '#525252'],
    ['text_color', 'Description and supporting text', '#525252'],
    ['regular_price_color', 'Regular / original price', '#0a0a0a'],
    ['offer_price_color', 'Offer price', '#0a0a0a'],
]
const buttonFields: Record<'add' | 'order', [CardColorKey, string, string][]> = {
    add: [
        ['add_background_color', 'Background', '#ffffff'],
        ['add_text_color', 'Text', '#4338ca'],
        ['add_border_color', 'Border', '#4f46e5'],
        ['add_hover_background_color', 'Hover background', '#eef2ff'],
        ['add_hover_text_color', 'Hover text', '#4338ca'],
    ],
    order: [
        ['order_background_color', 'Background', '#4f46e5'],
        ['order_text_color', 'Text', '#ffffff'],
        ['order_border_color', 'Border', '#4f46e5'],
        ['order_hover_background_color', 'Hover background', '#4338ca'],
        ['order_hover_text_color', 'Hover text', '#ffffff'],
    ],
}

export default function Edit({ productCards, abilities }: Props) {
    const [section, setSection] = useState<typeof sections[number]['key']>('product-cards')
    const saved = normalizeProductCardTheme(productCards)
    const [draft, setDraft] = useState<ProductCardTheme>(saved)
    const [processing, setProcessing] = useState(false)
    const [errors, setErrors] = useState<Record<string, string>>({})
    const disabled = !abilities.update || processing
    const dirty = JSON.stringify(normalizeProductCardTheme(draft)) !== JSON.stringify(saved)

    const changeColor = (key: CardColorKey, value?: string) => {
        setDraft(current => {
            const next = { ...current }
            if (value === undefined) delete next[key]
            else next[key] = value
            return next
        })
    }
    const changeTitleHoverDecoration = (value: '' | TitleHoverDecoration) => {
        setDraft(current => {
            const next = { ...current }
            if (value === '') delete next.title_hover_decoration
            else next.title_hover_decoration = value
            return next
        })
    }
    const changeRadius = (key: CardRadiusKey, value: string) => {
        setDraft(current => {
            const next = { ...current }
            if (value === '') delete next[key]
            else next[key] = Number(value)
            return next
        })
    }
    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()
        if (disabled || !dirty) return
        setErrors({})
        router.put(route('admin.website-settings.theme-settings.product-cards.update'), { product_cards: normalizeProductCardTheme(draft) }, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onError: setErrors,
            onSuccess: page => setDraft(normalizeProductCardTheme(page.props.productCards)),
        })
    }

    const colorFields = (fields: [CardColorKey, string, string][]) => fields.map(([key, label, fallback]) => (
        <div key={key} className="min-w-0 space-y-2">
            <label htmlFor={key} className="block text-sm font-medium text-neutral-700 dark:text-neutral-200">{label}</label>
            <div className="flex flex-wrap items-center gap-2">
                <input id={key} type="color" value={draft[key] ?? fallback} onChange={event => changeColor(key, event.target.value)} className="h-10 w-12 cursor-pointer rounded border border-neutral-300 bg-white p-1 disabled:opacity-50" />
                <span className="text-xs text-neutral-600 dark:text-neutral-300">{draft[key] ?? 'Default'}</span>
                {draft[key] ? <button type="button" onClick={() => changeColor(key)} className="text-xs font-medium text-indigo-600 underline dark:text-indigo-300">Reset</button> : <button type="button" onClick={() => changeColor(key, fallback)} className="text-xs font-medium text-indigo-600 underline dark:text-indigo-300">Enable</button>}
            </div>
        </div>
    ))

    return (
        <AdminLayout title="Theme Settings" description="Manage your storefront and admin appearance in one place. Changes take effect after saving.">
            <div className="grid min-w-0 gap-0 overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900 md:grid-cols-[210px_minmax(0,1fr)]">
                <aside className="min-w-0 border-b border-neutral-200 bg-neutral-50 p-3 dark:border-neutral-800 dark:bg-neutral-950 md:border-b-0 md:border-r">
                    <p className="px-3 py-3 text-xs font-semibold uppercase tracking-wide text-neutral-500">Theme options</p>
                    <nav aria-label="Theme settings sections" className="space-y-1">
                        {sections.map(({ key, label, icon: Icon }) => <button key={key} type="button" onClick={() => setSection(key)} aria-pressed={section === key} className={`flex w-full items-center gap-2 rounded-lg px-3 py-3 text-left text-sm font-semibold focus-visible:ring-2 focus-visible:ring-indigo-500 ${section === key ? 'bg-indigo-600 text-white' : 'text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800'}`}><Icon className="size-4" aria-hidden="true" />{label}</button>)}
                    </nav>
                </aside>
                <form id="product-cards" hidden={section !== 'product-cards'} onSubmit={submit} className="min-w-0 p-4 sm:p-6">
                    <div className="mb-5 flex flex-wrap items-start justify-between gap-3">
                        <div><h2 className="text-xl font-semibold text-neutral-950 dark:text-white">Product Cards</h2><p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Applies to product grids, collection cards, carousels and related products.</p></div>
                        <button type="submit" disabled={disabled || !dirty} className="inline-flex min-h-10 items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"><Save className="size-4" aria-hidden="true" />{processing ? 'Saving…' : 'Save changes'}</button>
                    </div>
                    {!abilities.update && <p className="mb-4 text-sm text-neutral-500" role="status">You have read-only access.</p>}
                    {Object.keys(errors).length > 0 && <div role="alert" className="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{Object.entries(errors).map(([key, message]) => <p key={key}>{key.replace(/^product_cards\.?/, '').replace(/_/g, ' ')}: {message}</p>)}</div>}
                    <div className="grid min-w-0 items-start gap-6 xl:grid-cols-[minmax(0,1fr)_280px]">
                        <fieldset disabled={disabled} className="min-w-0 space-y-6">
                            <section className="space-y-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-800">
                                <h3 className="font-semibold text-neutral-900 dark:text-white">Card colors</h3>
                                <div className="grid gap-4 sm:grid-cols-2">{colorFields(cardFields)}</div>
                                <div className="space-y-2">
                                    <label htmlFor="title_hover_decoration" className="block text-sm font-medium text-neutral-700 dark:text-neutral-200">Product title hover decoration</label>
                                    <select id="title_hover_decoration" value={draft.title_hover_decoration ?? ''} onChange={event => {
                                        const value = event.target.value
                                        if (value === '' || value === 'underline' || value === 'none') changeTitleHoverDecoration(value)
                                    }} className="w-full rounded-lg border border-neutral-300 bg-white p-2 text-sm text-neutral-900">
                                        <option value="">Default (underline)</option>
                                        <option value="underline">Underline</option>
                                        <option value="none">None (color only)</option>
                                    </select>
                                    <p className="text-xs text-neutral-500">Set a hover color and choose None for a color-only effect, or Underline to combine both.</p>
                                </div>
                                <p className="text-xs text-neutral-500">The regular price color also applies to the crossed-out original price. Mixed variant price ranges use the regular color.</p>
                            </section>
                            {(['add', 'order'] as const).map(kind => {
                                const radiusKey: CardRadiusKey = kind === 'add' ? 'add_border_radius' : 'order_border_radius'
                                return <section key={kind} className="space-y-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-800">
                                    <h3 className="font-semibold text-neutral-900 dark:text-white">{kind === 'add' ? 'Add to Cart button' : 'Order Now button'}</h3>
                                    <div className="grid gap-4 sm:grid-cols-2">{colorFields(buttonFields[kind])}
                                        <div className="space-y-2"><label htmlFor={radiusKey} className="block text-sm font-medium text-neutral-700 dark:text-neutral-200">Corner radius</label><select id={radiusKey} value={draft[radiusKey] ?? ''} onChange={event => changeRadius(radiusKey, event.target.value)} className="w-full rounded-lg border border-neutral-300 bg-white p-2 text-sm text-neutral-900"><option value="">Default</option>{Array.from({ length: 33 }, (_, radius) => <option key={radius} value={radius}>{radius} px</option>)}</select></div>
                                    </div>
                                </section>
                            })}
                            <div className="flex flex-wrap items-center gap-3">
                                <button type="button" onClick={() => setDraft({})} disabled={Object.keys(draft).length === 0} className="inline-flex items-center gap-2 rounded-lg border border-neutral-300 px-3 py-2 text-sm font-medium text-neutral-700 disabled:opacity-50 dark:border-neutral-700 dark:text-neutral-200"><RotateCcw className="size-4" aria-hidden="true" />Reset all to defaults</button>
                                <button type="button" onClick={() => setDraft(saved)} disabled={!dirty} className="text-sm font-medium text-neutral-600 underline disabled:opacity-50 dark:text-neutral-300">Discard unsaved changes</button>
                            </div>
                            <p role="status" className="text-xs text-neutral-500">{dirty ? 'Unsaved changes. Save to publish your styles.' : 'All changes saved.'}</p>
                        </fieldset>
                        <CardPreview theme={draft} />
                    </div>
                </form>
                <div hidden={section !== 'admin-branding'} className="min-w-0"><AdminBrandingEditor canUpdate={abilities.update} /></div>
            </div>
        </AdminLayout>
    )
}

function CardPreview({ theme }: { theme: ProductCardTheme }) {
    const [onSale, setOnSale] = useState(true)
    const props = productCardThemeProps(theme)
    return <section className="min-w-0 space-y-3 xl:sticky xl:top-6">
        <div className="flex flex-wrap items-center justify-between gap-2"><h3 className="font-semibold text-neutral-900 dark:text-white">Live preview</h3><label className="flex items-center gap-2 text-xs text-neutral-600 dark:text-neutral-300"><input type="checkbox" checked={onSale} onChange={event => setOnSale(event.target.checked)} />On sale</label></div>
        <article className={`${props.className} overflow-hidden rounded-xl border border-neutral-200 bg-white`} style={props.style}>
            <div className="flex aspect-square items-center justify-center bg-neutral-100"><Package className="size-20 text-neutral-400" aria-hidden="true" /></div>
            <div className="space-y-2 p-4">
                <p className="mwt-card-copy text-xs font-medium uppercase text-neutral-500">Sample brand</p>
                <h4 tabIndex={0} className="mwt-card-title cursor-pointer text-base font-medium text-neutral-950 underline-offset-4 transition hover:text-neutral-600 hover:underline focus-visible:ring-2 focus-visible:ring-indigo-500">Premium everyday product</h4>
                <p className="mwt-card-copy text-sm text-neutral-600">An example product description.</p>
                <ProductPrice mode="resolved" pricing={{ price: onSale ? 550000 : 675000, compare_at_price: onSale ? 675000 : null, on_sale: onSale }} />
                <div className="flex flex-wrap gap-2 pt-2" aria-label="Non-interactive purchase button preview"><span className={addButtonClass}><ShoppingCart className="size-4" aria-hidden="true" />Add to Cart</span><span className={orderButtonClass}>Order Now</span></div>
            </div>
        </article>
        <p className="text-xs leading-5 text-neutral-500">Sample product only. Hover over the title or buttons to preview hover styles. You can also focus the sample title with your keyboard. Check text contrast before saving. Real cart, checkout and product-detail purchase controls are unaffected.</p>
    </section>
}
