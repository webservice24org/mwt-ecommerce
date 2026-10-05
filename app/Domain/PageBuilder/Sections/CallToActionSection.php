<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Contracts\SectionDefinition;
use App\Domain\PageBuilder\Sections\Schemas\CallToActionConfigSchema;
use InvalidArgumentException;

final class CallToActionSection implements SectionDefinition
{
    public function type(): SectionType
    {
        return SectionType::CallToAction;
    }

    public function label(): string
    {
        return SectionType::CallToAction->label();
    }

    /**
     * @return list<SectionTemplateData>
     */
    public function templates(): array
    {
        return [
            new SectionTemplateData(
                key: 'high_impact',
                label: 'High Impact',
                description: 'A bold centered CTA with WhatsApp, email, and direct phone contact actions.',
                category: 'Marketing',
            ),

            new SectionTemplateData(
                key: 'split_lead_capture',
                label: 'Split Lead Capture',
                description: 'A split CTA with contact details and an email subscription form.',
                category: 'Marketing',
            ),

            new SectionTemplateData(
                key: 'contact_grid',
                label: 'Contact Grid',
                description: 'A compact centered CTA with phone, WhatsApp, and email contact cards.',
                category: 'Marketing',
            ),
        ];
    }

    public function defaultTemplate(): string
    {
        return 'high_impact';
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
        $common = [
            'eyebrow' => null,
            'heading' => 'Ready to get started?',
            'description' => '',
            'background_type' => 'color',
            'background_color' => '#0f172a',
            'background_image' => null,
            'background_overlay' => 70,
            'text_theme' => 'light',
            'phone_label' => 'Call Us',
            'phone_number' => null,
            'email_label' => 'Email Us',
            'email' => null,
            'whatsapp_label' => 'Chat on WhatsApp',
            'whatsapp_number' => null,
            'whatsapp_message' => null,
            'newsletter_placeholder' => null,
            'newsletter_button_label' => null,
            'newsletter_note' => null,
        ];

        return match ($template) {
            'high_impact' => array_replace(
                $common,
                [
                    'eyebrow' => 'Ready to get started?',
                    'heading' => 'Let\'s build something great together',
                    'description' => 'Reach us directly by WhatsApp, phone, or email to discuss your next project.',
                    'phone_label' => 'Or call directly',
                    'email_label' => 'Email Us',
                    'whatsapp_label' => 'Chat on WhatsApp',
                ],
            ),

            'split_lead_capture' => array_replace(
                $common,
                [
                    'heading' => 'Let\'s talk about your project',
                    'description' => 'Subscribe for updates or contact us directly by phone, WhatsApp, or email.',
                    'background_color' => '#ffffff',
                    'background_overlay' => 65,
                    'text_theme' => 'dark',
                    'phone_label' => 'Call Us',
                    'email_label' => 'Email Us',
                    'whatsapp_label' => 'WhatsApp',
                    'newsletter_placeholder' => 'Enter your email address',
                    'newsletter_button_label' => 'Subscribe',
                    'newsletter_note' => 'No spam, unsubscribe anytime.',
                ],
            ),

            'contact_grid' => array_replace(
                $common,
                [
                    'heading' => 'Have questions? Get in touch with us',
                    'description' => 'Reach out through your preferred channel for support or project inquiries.',
                    'background_color' => '#ffffff',
                    'background_overlay' => 65,
                    'text_theme' => 'dark',
                    'phone_label' => 'Call Us',
                    'email_label' => 'Email Us',
                    'whatsapp_label' => 'WhatsApp',
                ],
            ),

            default => throw new InvalidArgumentException(
                "Unsupported call-to-action template [{$template}].",
            ),
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function templateDefaultConfigs(): array
    {
        return [
            'high_impact' => $this->defaultConfigForTemplate(
                'high_impact',
            ),
            'split_lead_capture' => $this->defaultConfigForTemplate(
                'split_lead_capture',
            ),
            'contact_grid' => $this->defaultConfigForTemplate(
                'contact_grid',
            ),
        ];
    }

    public function configSchema(): SectionConfigSchema
    {
        return new CallToActionConfigSchema;
    }
}
