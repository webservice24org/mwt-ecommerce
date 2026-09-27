import StorefrontBreadcrumbs from '@/Components/Frontend/Navigation/StorefrontBreadcrumbs'
import StorefrontSeo from '@/Components/Frontend/Seo/StorefrontSeo'
import FrontendLayout from '@/Layouts/Frontend/FrontendLayout'
import { buildBreadcrumbJsonLd } from '@/lib/storefrontSeo'
import type { StorefrontBreadcrumbItem } from '@/types/storefront'
import type { StorefrontPage } from '@/types/storefront-page'
import StorefrontSectionRenderer from '@/Components/Frontend/PageBuilder/StorefrontSectionRenderer'

interface Props {
    page: StorefrontPage
}

export default function Show({ page }: Props) {
    const title = page.seo.meta_title ?? page.title

    const description = page.seo.meta_description ?? `Learn more about ${page.title}.`

    const canonicalPath = `/pages/${page.slug}`

    const breadcrumbs: StorefrontBreadcrumbItem[] = [
        {
            label: 'Home',
            href: '/',
        },
        {
            label: page.title,
        },
    ]

    const breadcrumbJsonLd = buildBreadcrumbJsonLd(breadcrumbs)

    return (
        <FrontendLayout>
            <StorefrontSeo
                title={title}
                description={description}
                canonicalPath={canonicalPath}
                jsonLd={breadcrumbJsonLd}
            />

            <main>
                <div className="mx-auto w-full max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
                    <StorefrontBreadcrumbs items={breadcrumbs} />
                </div>

                {page.content_mode === 'classic' ? (
                    <ClassicPage page={page} />
                ) : (
                    <BuilderPage page={page} />
                )}
            </main>
        </FrontendLayout>
    )
}

function ClassicPage({ page }: Props) {
    return (
        <article className="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <header className="mb-8">
                <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">{page.title}</h1>
            </header>

            {page.featured_image && (
                <img
                    src={page.featured_image}
                    alt=""
                    className="mb-8 h-auto w-full rounded-xl object-cover"
                />
            )}

            {page.content && (
                <div
                    className="prose max-w-none dark:prose-invert"
                    dangerouslySetInnerHTML={{
                        __html: page.content,
                    }}
                />
            )}
        </article>
    )
}

function BuilderPage({ page }: Props) {
    if (page.sections.length === 0) {
        return (
            <div className="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="rounded-xl border border-dashed border-neutral-300 p-8 text-center text-sm text-neutral-500">
                    This page does not contain any visible sections yet.
                </div>
            </div>
        )
    }

    return (
        <div className="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            {page.sections.map((section) => (
                <StorefrontSectionRenderer key={section.id} section={section} />
            ))}
        </div>
    )
}
