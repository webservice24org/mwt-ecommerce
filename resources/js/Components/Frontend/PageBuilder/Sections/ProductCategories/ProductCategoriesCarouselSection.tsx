import CategoryVisualCard from '@/Components/Frontend/Catalog/CategoryVisualCard'
import SlideTransition, {
    type SlideEffect,
} from '@/Components/Frontend/PageBuilder/Shared/SlideTransition'
import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'
import type {
    StorefrontPageBuilderCategory,
    StorefrontProductCategoriesSectionData,
} from '@/types/storefront-page'

import { usePrefersReducedMotion } from '../Hero/usePrefersReducedMotion'
import CategoryCarouselControls from './CategoryCarouselControls'
import { useCategoryCarousel } from './useCategoryCarousel'
import { useCategoryCarouselColumns } from './useCategoryCarouselColumns'

function isProductCategoriesData(
    data: Record<string, unknown>,
): data is StorefrontProductCategoriesSectionData {
    return Array.isArray(data.categories)
}

function getString(config: Record<string, unknown>, key: string, fallback: string): string {
    const value = config[key]

    return typeof value === 'string' && value.trim() !== '' ? value : fallback
}

function getBoolean(config: Record<string, unknown>, key: string, fallback: boolean): boolean {
    const value = config[key]

    return typeof value === 'boolean' ? value : fallback
}

function getNumber(config: Record<string, unknown>, key: string, fallback: number): number {
    const value = config[key]

    return typeof value === 'number' && Number.isFinite(value) ? value : fallback
}

function getColumns(config: Record<string, unknown>): number {
    const columns = getNumber(config, 'columns', 4)

    return [2, 3, 4, 5, 6].includes(columns) ? columns : 4
}

function getEffect(config: Record<string, unknown>): SlideEffect {
    const effect = config.effect

    switch (effect) {
        case 'fade':
        case 'slide_left':
        case 'slide_right':
        case 'slide_up':
        case 'slide_down':
            return effect

        default:
            return 'fade'
    }
}

function chunkCategories(
    categories: StorefrontPageBuilderCategory[],
    size: number,
): StorefrontPageBuilderCategory[][] {
    const pages: StorefrontPageBuilderCategory[][] = []

    for (let index = 0; index < categories.length; index += size) {
        pages.push(categories.slice(index, index + size))
    }

    return pages
}

export default function ProductCategoriesCarouselSection({ section }: StorefrontSectionProps) {
    if (!isProductCategoriesData(section.data)) {
        return null
    }

    const categories = section.data.categories

    if (categories.length === 0) {
        return null
    }

    return (
        <ProductCategoriesCarousel
            sectionId={section.id}
            categories={categories}
            title={getString(section.config, 'title', 'Shop by Category')}
            columns={getColumns(section.config)}
            showName={getBoolean(section.config, 'show_name', true)}
            showProductCount={getBoolean(section.config, 'show_product_count', false)}
            autoplay={getBoolean(section.config, 'autoplay', false)}
            autoplayDelay={getNumber(section.config, 'autoplay_delay', 5000)}
            showArrows={getBoolean(section.config, 'show_arrows', true)}
            showDots={getBoolean(section.config, 'show_dots', true)}
            effect={getEffect(section.config)}
        />
    )
}

interface ProductCategoriesCarouselProps {
    sectionId: number
    categories: StorefrontPageBuilderCategory[]
    title: string
    columns: number
    showName: boolean
    showProductCount: boolean
    autoplay: boolean
    autoplayDelay: number
    showArrows: boolean
    showDots: boolean
    effect: SlideEffect
}

function ProductCategoriesCarousel({
    sectionId,
    categories,
    title,
    columns,
    showName,
    showProductCount,
    autoplay,
    autoplayDelay,
    showArrows,
    showDots,
    effect,
}: ProductCategoriesCarouselProps) {
    const visibleColumns = useCategoryCarouselColumns(columns)

    const prefersReducedMotion = usePrefersReducedMotion()

    const pages = chunkCategories(categories, visibleColumns)

    const carousel = useCategoryCarousel({
        pageCount: pages.length,
        autoplay: autoplay && !prefersReducedMotion,
        autoplayDelay,
    })

    const slidesId = `category-carousel-${sectionId}-slides`

    const activePage = pages[carousel.activePage]

    if (!activePage) {
        return null
    }

    return (
        <section aria-labelledby={`section-${sectionId}-title`} className="min-w-0 py-8 sm:py-10">
            <div className="mb-6 min-w-0">
                <h2
                    id={`section-${sectionId}-title`}
                    className="break-words text-2xl font-semibold tracking-tight text-neutral-950 sm:text-3xl"
                >
                    {title}
                </h2>
            </div>

            <div
                role="region"
                aria-roledescription="carousel"
                aria-label={title}
                className="relative min-w-0 overflow-hidden"
                onMouseEnter={carousel.pause}
                onMouseLeave={carousel.resume}
                onFocusCapture={carousel.pause}
                onBlurCapture={(event) => {
                    if (!event.currentTarget.contains(event.relatedTarget)) {
                        carousel.resume()
                    }
                }}
                onKeyDown={(event) => {
                    const target = event.target

                    if (
                        target instanceof HTMLElement &&
                        (target.isContentEditable ||
                            target.tagName === 'INPUT' ||
                            target.tagName === 'TEXTAREA' ||
                            target.tagName === 'SELECT')
                    ) {
                        return
                    }

                    if (event.key === 'ArrowLeft') {
                        event.preventDefault()
                        carousel.previousPage()

                        return
                    }

                    if (event.key === 'ArrowRight') {
                        event.preventDefault()
                        carousel.nextPage()
                    }
                }}
            >
                <SlideTransition
                    activeIndex={carousel.activePage}
                    direction={carousel.direction}
                    effect={prefersReducedMotion ? 'none' : effect}
                >
                    <div
                        id={slidesId}
                        role="group"
                        aria-roledescription="slide"
                        aria-label={`${carousel.activePage + 1} of ${pages.length}`}
                        className="px-1"
                    >
                        <div
                            className="grid min-w-0 gap-4 sm:gap-5"
                            style={{
                                gridTemplateColumns: `repeat(${visibleColumns}, minmax(0, 1fr))`,
                            }}
                        >
                            {activePage.map((category) => (
                                <div key={category.id} className="min-w-0">
                                    <CategoryVisualCard
                                        category={category}
                                        showName={showName}
                                        showProductCount={showProductCount}
                                    />
                                </div>
                            ))}
                        </div>
                    </div>
                </SlideTransition>

                <CategoryCarouselControls
                    pageCount={pages.length}
                    activePage={carousel.activePage}
                    showArrows={showArrows}
                    showDots={showDots}
                    controlsId={slidesId}
                    onPrevious={carousel.previousPage}
                    onNext={carousel.nextPage}
                    onSelect={carousel.goToPage}
                />
            </div>
        </section>
    )
}
