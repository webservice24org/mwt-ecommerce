import StorefrontPageLayout from '@/Components/Frontend/PageBuilder/StorefrontPageLayout'
import StorefrontSectionRenderer from '@/Components/Frontend/PageBuilder/StorefrontSectionRenderer'
import StorefrontBreadcrumbs from '@/Components/Frontend/Navigation/StorefrontBreadcrumbs'
import StorefrontSeo from '@/Components/Frontend/Seo/StorefrontSeo'
import FrontendLayout from '@/Layouts/Frontend/FrontendLayout'

import type {
    ResolvedSeo,
} from '@/types/seo'

import type {
    StructuredDataDocument,
} from '@/types/structured-data'

import type {
    StorefrontBreadcrumbItem,
} from '@/types/storefront'

import type {
    StorefrontPage,
} from '@/types/storefront-page'

interface Props {
    page: StorefrontPage
    seo: ResolvedSeo
    breadcrumbStructuredData:
        | StructuredDataDocument
        | null
}

interface BuilderPageProps {
    page: StorefrontPage
}

export default function Show({
    page,
    seo,
    breadcrumbStructuredData,
}: Props) {
    const breadcrumbs:
        StorefrontBreadcrumbItem[] = [
            {
                label:
                    'Home',

                href:
                    '/',
            },

            {
                label:
                    page.title,
            },
        ]

    return (
        <FrontendLayout>
            {/*
             * SEO values are now resolved by Laravel.
             *
             * React no longer determines:
             * - title fallback
             * - description fallback
             * - social-image fallback
             */}
            <StorefrontSeo
                seo={seo}
                jsonLd={
                    breadcrumbStructuredData
                }
            />

            <main className="w-full min-w-0">
                {page.show_breadcrumbs && (
                    <div className="mx-auto w-full max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
                        <StorefrontBreadcrumbs
                            items={
                                breadcrumbs
                            }
                        />
                    </div>
                )}

                <StorefrontPageLayout
                    layout={
                        page.layout
                    }
                >
                    {page.content_mode ===
                    'classic' ? (
                        <ClassicPage
                            page={
                                page
                            }
                            constrained={
                                page.layout ===
                                'full_width'
                            }
                        />
                    ) : (
                        <BuilderPage
                            page={
                                page
                            }
                        />
                    )}
                </StorefrontPageLayout>
            </main>
        </FrontendLayout>
    )
}

interface PageBodyProps {
    page: StorefrontPage
    constrained: boolean
}

function ClassicPage({
    page,
    constrained,
}: PageBodyProps) {
    return (
        <article
            className={
                constrained
                    ? 'mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8'
                    : 'w-full min-w-0 py-6'
            }
        >
            <header className="mb-8">
                <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">
                    {page.title}
                </h1>
            </header>

            {page.featured_image && (
                <img
                    src={
                        page.featured_image
                    }
                    alt=""
                    className="mb-8 h-auto w-full rounded-xl object-cover"
                />
            )}

            {page.content && (
                <div
                    className="prose max-w-none dark:prose-invert"
                    dangerouslySetInnerHTML={{
                        __html:
                            page.content,
                    }}
                />
            )}
        </article>
    )
}

function BuilderPage({
    page,
}: BuilderPageProps) {
    if (
        page.sections.length ===
        0
    ) {
        return (
            <div className="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="rounded-xl border border-dashed border-neutral-300 p-8 text-center text-sm text-neutral-500">
                    This page does not
                    contain any visible
                    sections yet.
                </div>
            </div>
        )
    }

    return (
        <div className="w-full min-w-0">
            {page.sections.map(
                (
                    section,
                ) => (
                    <StorefrontSectionRenderer
                        key={
                            section.id
                        }
                        section={
                            section
                        }
                    />
                ),
            )}
        </div>
    )
}