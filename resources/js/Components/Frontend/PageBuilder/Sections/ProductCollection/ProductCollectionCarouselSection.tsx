import ProductCollectionCard from '@/Components/Frontend/PageBuilder/Sections/ProductCollection/ProductCollectionCard'
import SlideTransition, {
    type SlideEffect,
} from '@/Components/Frontend/PageBuilder/Shared/SlideTransition'
import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'
import type { StorefrontProductCard } from '@/types/storefront'

import { usePrefersReducedMotion } from '../Hero/usePrefersReducedMotion'
import CategoryCarouselControls from '../ProductCategories/CategoryCarouselControls'
import { useCategoryCarousel } from '../ProductCategories/useCategoryCarousel'
import { useCategoryCarouselColumns } from '../ProductCategories/useCategoryCarouselColumns'

interface ProductCollectionSectionData extends Record<string, unknown> {
    products: StorefrontProductCard[]
}

export default function ProductCollectionCarouselSection({ section }: StorefrontSectionProps) {
    if (!isProductCollectionData(section.data)) {
        return null
    }

    const products = section.data.products

    if (products.length === 0) {
        return null
    }

    return (
        <ProductCollectionCarousel
            sectionId={section.id}
            products={products}
            title={getString(section.config, 'title', 'Products')}
            columns={getColumns(section.config)}
            showPrice={getBoolean(section.config, 'show_price', true)}
            showBadges={getBoolean(section.config, 'show_badges', true)}
            autoplay={getBoolean(section.config, 'autoplay', false)}
            autoplayDelay={getNumber(section.config, 'autoplay_delay', 5000)}
            showArrows={getBoolean(section.config, 'show_arrows', true)}
            showDots={getBoolean(section.config, 'show_dots', true)}
            effect={getEffect(section.config)}
        />
    )
}

interface ProductCollectionCarouselProps {
    sectionId: number
    products: StorefrontProductCard[]
    title: string
    columns: number
    showPrice: boolean
    showBadges: boolean
    autoplay: boolean
    autoplayDelay: number
    showArrows: boolean
    showDots: boolean
    effect: SlideEffect
}

function ProductCollectionCarousel({
    sectionId,
    products,
    title,
    columns,
    showPrice,
    showBadges,
    autoplay,
    autoplayDelay,
    showArrows,
    showDots,
    effect,
}: ProductCollectionCarouselProps) {
    const visibleColumns = useCategoryCarouselColumns(columns)

    const prefersReducedMotion = usePrefersReducedMotion()

    const pages = chunkProducts(products, visibleColumns)

    const carousel = useCategoryCarousel({
        pageCount: pages.length,
        autoplay: autoplay && !prefersReducedMotion,
        autoplayDelay,
    })

    const slidesId = `product-collection-carousel-${sectionId}-slides`

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
                            className="grid min-w-0 gap-5 sm:gap-6"
                            style={{
                                gridTemplateColumns: `repeat(${visibleColumns}, minmax(0, 1fr))`,
                            }}
                        >
                            {activePage.map((product) => (
                                <div key={product.id} className="min-w-0">
                                    <ProductCollectionCard
                                        product={product}
                                        showPrice={showPrice}
                                        showBadges={showBadges}
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

function isProductCollectionData(
    data: Record<string, unknown>,
): data is ProductCollectionSectionData {
    return Array.isArray(data.products)
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

function chunkProducts(products: StorefrontProductCard[], size: number): StorefrontProductCard[][] {
    const pages: StorefrontProductCard[][] = []

    for (let index = 0; index < products.length; index += size) {
        pages.push(products.slice(index, index + size))
    }

    return pages
}
