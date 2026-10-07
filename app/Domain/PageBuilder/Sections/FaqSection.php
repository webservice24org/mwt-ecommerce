<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Contracts\SectionDefinition;
use App\Domain\PageBuilder\Sections\Schemas\FaqConfigSchema;
use InvalidArgumentException;

final class FaqSection implements SectionDefinition
{
    public function type(): SectionType
    {
        return SectionType::Faq;
    }

    public function label(): string
    {
        return $this->type()->label();
    }

    public function templates(): array
    {
        return [
            new SectionTemplateData(
                key: 'accordion',
                label: 'Classic Accordion',
                description: 'A clean single-column FAQ accordion for common customer questions.',
                category: 'FAQ',
            ),

            new SectionTemplateData(
                key: 'two_column',
                label: 'Two-Column FAQ',
                description: 'Display frequently asked questions in a responsive two-column layout.',
                category: 'FAQ',
            ),

            new SectionTemplateData(
                key: 'side_panel',
                label: 'Side Panel FAQ',
                description: 'A support-focused FAQ layout with introductory content beside the questions.',
                category: 'FAQ',
            ),
        ];
    }

    public function defaultTemplate(): string
    {
        return 'accordion';
    }

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
        $configs =
            $this->templateDefaultConfigs();

        if (
            ! array_key_exists(
                $template,
                $configs,
            )
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown FAQ template [%s].',
                    $template,
                ),
            );
        }

        return $configs[$template];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function templateDefaultConfigs(): array
    {
        return [
            'accordion' => [
                'eyebrow' => 'Help Center',

                'heading' => 'Frequently Asked Questions',

                'description' => 'Find quick answers to common questions about ordering, delivery, payments, returns, and support.',

                'items' => $this->defaultItems(),

                'alignment' => 'center',

                'background_color' => '#ffffff',

                'text_theme' => 'dark',

                'open_first' => true,

                'allow_multiple_open' => false,
            ],

            'two_column' => [
                'eyebrow' => 'Common Questions',

                'heading' => 'Everything You Need to Know',

                'description' => 'Browse answers to the questions customers ask most often.',

                'items' => $this->defaultItems(),

                'alignment' => 'left',

                'background_color' => '#ffffff',

                'text_theme' => 'dark',

                'open_first' => false,

                'allow_multiple_open' => true,
            ],

            'side_panel' => [
                'eyebrow' => 'Need Help?',

                'heading' => 'Questions, Answered',

                'description' => 'Helpful information about shopping with us. If you still need assistance, our support team is here to help.',

                'items' => $this->defaultItems(),

                'alignment' => 'left',

                'background_color' => '#ffffff',

                'text_theme' => 'dark',

                'open_first' => true,

                'allow_multiple_open' => false,
            ],
        ];
    }

    public function configSchema(): SectionConfigSchema
    {
        return new FaqConfigSchema;
    }

    /**
     * @return list<array{
     *     question: string,
     *     answer: string
     * }>
     */
    private function defaultItems(): array
    {
        return [
            [
                'question' => 'How long does delivery take?',

                'answer' => 'Delivery times depend on your location and the selected shipping method. Estimated delivery information is shown during checkout.',
            ],

            [
                'question' => 'What payment methods do you accept?',

                'answer' => 'Available payment methods are shown during checkout and may vary depending on your location and order.',
            ],

            [
                'question' => 'Can I change or cancel my order?',

                'answer' => 'Order changes or cancellations may be possible before processing begins. Contact support as soon as possible after placing your order.',
            ],

            [
                'question' => 'How can I track my order?',

                'answer' => 'When tracking is available, you can use the tracking information provided with your order or shipping confirmation.',
            ],

            [
                'question' => 'What is your return policy?',

                'answer' => 'Eligible products may be returned according to the store return policy. Check the applicable return conditions before sending an item back.',
            ],

            [
                'question' => 'How do I contact customer support?',

                'answer' => 'Use the support or contact options provided on the website to reach the customer service team.',
            ],
        ];
    }
}
