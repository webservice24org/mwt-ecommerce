<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Contracts\SectionDefinition;
use App\Domain\PageBuilder\Sections\Schemas\TestimonialsConfigSchema;
use InvalidArgumentException;

final class TestimonialsSection implements SectionDefinition
{
    public function type(): SectionType
    {
        return SectionType::Testimonials;
    }

    public function label(): string
    {
        return SectionType::Testimonials->label();
    }

    /**
     * @return list<SectionTemplateData>
     */
    public function templates(): array
    {
        return [
            new SectionTemplateData(
                key: 'grid_slider',
                label: 'Multi-Card Grid Slider',
                description: 'A paged testimonial carousel displaying multiple review cards together.',
                category: 'Testimonials',
            ),

            new SectionTemplateData(
                key: 'spotlight_slider',
                label: 'Single Spotlight Slider',
                description: 'A large centered testimonial carousel for featured customer stories.',
                category: 'Testimonials',
            ),

            new SectionTemplateData(
                key: 'card_slider',
                label: 'Horizontal Card Slider',
                description: 'A compact horizontal testimonial carousel with multiple customer cards.',
                category: 'Testimonials',
            ),
        ];
    }

    public function defaultTemplate(): string
    {
        return 'grid_slider';
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultConfig(): array
    {
        return $this->defaultConfigForTemplate(
            $this->defaultTemplate(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultConfigForTemplate(
        string $template,
    ): array {
        $config =
            $this->templateDefaultConfigs()[
                $template
            ] ?? null;

        if ($config === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown Testimonials template [%s].',
                    $template,
                ),
            );
        }

        return $config;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function templateDefaultConfigs(): array
    {
        return [
            'grid_slider' => $this->gridSliderDefaults(),

            'spotlight_slider' => $this->spotlightSliderDefaults(),

            'card_slider' => $this->cardSliderDefaults(),
        ];
    }

    public function configSchema(): SectionConfigSchema
    {
        return new TestimonialsConfigSchema;
    }

    /**
     * @return array<string, mixed>
     */
    private function commonDefaults(): array
    {
        return [
            'eyebrow' => null,

            'heading' => null,

            'description' => '',

            'items' => [],

            'alignment' => 'left',

            'background_color' => '#ffffff',

            'text_theme' => 'dark',

            'show_rating' => true,

            /*
             * Slider defaults.
             *
             * Testimonials are autoplay sliders
             * by default for all three designs.
             */
            'autoplay' => true,

            'autoplay_interval' => 5000,

            'pause_on_hover' => true,

            'loop' => true,

            'show_arrows' => true,

            'show_dots' => true,

            /*
             * Must match the SlideEffect values
             * exported by SlideTransition.tsx.
             */
            'slide_effect' => 'slide_left',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function gridSliderDefaults(): array
    {
        return array_replace(
            $this->commonDefaults(),
            [
                'eyebrow' => 'Customer Reviews',

                'heading' => 'Loved by Our Customers',

                'alignment' => 'left',

                'show_rating' => true,

                'slide_effect' => 'slide_left',

                'items' => [
                    $this->testimonial(
                        quote: 'The setup was quick and the experience has been excellent from day one.',
                        name: 'Sarah Jenkins',
                        role: 'Head of Growth, Atelier',
                        rating: 5,
                    ),

                    $this->testimonial(
                        quote: 'Clean structure and thoughtful components saved our team a great deal of development time.',
                        name: 'Marcus Chen',
                        role: 'Co-Founder, SaaSify',
                        rating: 5,
                    ),

                    $this->testimonial(
                        quote: 'The customer experience is polished, responsive, and easy for our team to manage.',
                        name: 'Elena Rostova',
                        role: 'Design Lead, Nordica',
                        rating: 5,
                    ),

                    $this->testimonial(
                        quote: 'The modular approach integrated smoothly with our existing application.',
                        name: 'David Vance',
                        role: 'VP Engineering, CloudScale',
                        rating: 5,
                    ),

                    $this->testimonial(
                        quote: 'Our storefront feels much faster and customers can find what they need more easily.',
                        name: 'Jessica Wu',
                        role: 'Product Lead, RetailHub',
                        rating: 5,
                    ),

                    $this->testimonial(
                        quote: 'A clean modern interface with a structure that remains easy to extend.',
                        name: 'Liam Thorne',
                        role: 'Founder, BrandStack',
                        rating: 5,
                    ),
                ],
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function spotlightSliderDefaults(): array
    {
        return array_replace(
            $this->commonDefaults(),
            [
                'eyebrow' => null,

                'heading' => null,

                'alignment' => 'center',

                'background_color' => '#1e1b4b',

                'text_theme' => 'light',

                'show_rating' => false,

                'slide_effect' => 'fade',

                'items' => [
                    $this->testimonial(
                        quote: 'Moving to the new storefront architecture dramatically improved performance and helped our organic growth.',
                        name: 'David Vance',
                        role: 'VP of Engineering, CloudScale',
                        rating: null,
                        badge: 'Featured Case Study #1',
                    ),

                    $this->testimonial(
                        quote: 'We scaled our customer experience without compromising the consistency of the interface.',
                        name: 'Sarah Jenkins',
                        role: 'CEO, Atelier Global',
                        rating: null,
                        badge: 'Featured Case Study #2',
                    ),
                ],
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function cardSliderDefaults(): array
    {
        return array_replace(
            $this->commonDefaults(),
            [
                'eyebrow' => 'Client Stories',

                'heading' => 'Continuous Feedback Stream',

                'alignment' => 'left',

                'show_rating' => false,

                'slide_effect' => 'slide_left',

                'items' => [
                    $this->testimonial(
                        quote: 'Implementation was straightforward and fitted naturally into our application workflow.',
                        name: 'Alex Rivera',
                        role: 'CTO, DevFlow',
                        rating: null,
                    ),

                    $this->testimonial(
                        quote: 'Our landing pages became easier to use across phones, tablets, and desktop screens.',
                        name: 'Jessica Wu',
                        role: 'Product Manager, RetailHub',
                        rating: null,
                    ),

                    $this->testimonial(
                        quote: 'The attention to detail makes customizing the layouts fast and predictable.',
                        name: 'Liam Thorne',
                        role: 'Founder, BrandStack',
                        rating: null,
                    ),

                    $this->testimonial(
                        quote: 'Modern design patterns and a clean code structure gave our team a strong foundation.',
                        name: 'Daniel Kim',
                        role: 'Head of UX, NextGen',
                        rating: null,
                    ),
                ],
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function testimonial(
        string $quote,
        string $name,
        ?string $role,
        ?int $rating,
        ?string $badge = null,
    ): array {
        return [
            'quote' => $quote,

            'name' => $name,

            'role' => $role,

            'rating' => $rating,

            /*
             * Page Builder media will populate
             * these fields later in H.3.
             */
            'image' => null,

            'image_alt' => null,

            /*
             * Used primarily by the Spotlight
             * design, but available to all items.
             */
            'badge' => $badge,
        ];
    }
}
