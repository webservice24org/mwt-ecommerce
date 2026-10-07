<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;
use App\Domain\PageBuilder\Sections\FaqSection;
use App\Domain\PageBuilder\Sections\Schemas\FaqConfigSchema;
use PHPUnit\Framework\TestCase;

final class FaqConfigSchemaTest extends TestCase
{
    public function test_valid_faq_config_is_normalized(): void
    {
        $config =
            $this->validConfig();

        $config['eyebrow'] =
            '  Help Center  ';

        $config['heading'] =
            '  Frequently Asked Questions  ';

        $config['description'] =
            '  Helpful answers for our customers.  ';

        $config['items'] = [
            [
                'question' => '  How long does delivery take?  ',

                'answer' => '  Delivery time depends on the destination.  ',
            ],
        ];

        $config['background_color'] =
            '  #AABBCC  ';

        $normalized =
            (
                new FaqConfigSchema
            )->validate(
                $config,
            );

        $this->assertSame(
            'Help Center',
            $normalized[
                'eyebrow'
            ],
        );

        $this->assertSame(
            'Frequently Asked Questions',
            $normalized[
                'heading'
            ],
        );

        $this->assertSame(
            'Helpful answers for our customers.',
            $normalized[
                'description'
            ],
        );

        $this->assertSame(
            'How long does delivery take?',
            $normalized[
                'items'
            ][0][
                'question'
            ],
        );

        $this->assertSame(
            'Delivery time depends on the destination.',
            $normalized[
                'items'
            ][0][
                'answer'
            ],
        );

        $this->assertSame(
            '#aabbcc',
            $normalized[
                'background_color'
            ],
        );
    }

    public function test_unknown_top_level_field_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config[
            'dangerous_field'
        ] =
            'unexpected';

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'dangerous_field',
        );
    }

    public function test_items_must_be_a_list(): void
    {
        $config =
            $this->validConfig();

        $config['items'] = [
            'first' => [
                'question' => 'Question',

                'answer' => 'Answer',
            ],
        ];

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'items',
        );
    }

    public function test_at_least_one_faq_item_is_required(): void
    {
        $config =
            $this->validConfig();

        $config[
            'items'
        ] = [];

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'items',
        );
    }

    public function test_more_than_sixteen_faq_items_are_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['items'] =
            array_fill(
                0,
                17,
                [
                    'question' => 'Question',

                    'answer' => 'Answer',
                ],
            );

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'items',
        );
    }

    public function test_each_faq_item_must_be_an_object(): void
    {
        $config =
            $this->validConfig();

        $config['items'] = [
            'invalid',
        ];

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'items.0',
        );
    }

    public function test_unknown_faq_item_field_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['items'] = [
            [
                'question' => 'Question',

                'answer' => 'Answer',

                'html' => '<script>alert(1)</script>',
            ],
        ];

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'items.0.html',
        );
    }

    public function test_blank_question_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['items'] = [
            [
                'question' => '   ',

                'answer' => 'Answer',
            ],
        ];

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'items.0.question',
        );
    }

    public function test_blank_answer_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['items'] = [
            [
                'question' => 'Question',

                'answer' => '   ',
            ],
        ];

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'items.0.answer',
        );
    }

    public function test_question_length_is_limited(): void
    {
        $config =
            $this->validConfig();

        $config['items'] = [
            [
                'question' => str_repeat(
                    'Q',
                    241,
                ),

                'answer' => 'Answer',
            ],
        ];

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'items.0.question',
        );
    }

    public function test_answer_length_is_limited(): void
    {
        $config =
            $this->validConfig();

        $config['items'] = [
            [
                'question' => 'Question',

                'answer' => str_repeat(
                    'A',
                    3001,
                ),
            ],
        ];

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'items.0.answer',
        );
    }

    public function test_invalid_alignment_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config[
            'alignment'
        ] =
            'right';

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'alignment',
        );
    }

    public function test_invalid_text_theme_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config[
            'text_theme'
        ] =
            'system';

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'text_theme',
        );
    }

    public function test_invalid_background_color_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config[
            'background_color'
        ] =
            'red';

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'background_color',
        );
    }

    public function test_short_hex_background_color_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config[
            'background_color'
        ] =
            '#fff';

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'background_color',
        );
    }

    public function test_open_first_must_be_a_boolean(): void
    {
        $config =
            $this->validConfig();

        $config[
            'open_first'
        ] =
            1;

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'open_first',
        );
    }

    public function test_allow_multiple_open_must_be_a_boolean(): void
    {
        $config =
            $this->validConfig();

        $config[
            'allow_multiple_open'
        ] =
            'false';

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'allow_multiple_open',
        );
    }

    public function test_eyebrow_length_is_limited(): void
    {
        $config =
            $this->validConfig();

        $config[
            'eyebrow'
        ] =
            str_repeat(
                'E',
                121,
            );

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'eyebrow',
        );
    }

    public function test_heading_length_is_limited(): void
    {
        $config =
            $this->validConfig();

        $config[
            'heading'
        ] =
            str_repeat(
                'H',
                181,
            );

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'heading',
        );
    }

    public function test_description_length_is_limited(): void
    {
        $config =
            $this->validConfig();

        $config[
            'description'
        ] =
            str_repeat(
                'D',
                1001,
            );

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'description',
        );
    }

    public function test_boundary_lengths_are_accepted(): void
    {
        $config =
            $this->validConfig();

        $config[
            'eyebrow'
        ] =
            str_repeat(
                'E',
                120,
            );

        $config[
            'heading'
        ] =
            str_repeat(
                'H',
                180,
            );

        $config[
            'description'
        ] =
            str_repeat(
                'D',
                1000,
            );

        $config['items'] = [
            [
                'question' => str_repeat(
                    'Q',
                    240,
                ),

                'answer' => str_repeat(
                    'A',
                    3000,
                ),
            ],
        ];

        $validated =
            (
                new FaqConfigSchema
            )->validate(
                $config,
            );

        $this->assertSame(
            240,
            mb_strlen(
                $validated[
                    'items'
                ][0][
                    'question'
                ],
            ),
        );

        $this->assertSame(
            3000,
            mb_strlen(
                $validated[
                    'items'
                ][0][
                    'answer'
                ],
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validConfig(): array
    {
        return (
            new FaqSection
        )->defaultConfigForTemplate(
            'accordion',
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function assertInvalidConfig(
        array $config,
        string $errorKey,
    ): void {
        try {
            (
                new FaqConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                sprintf(
                    'Expected FAQ configuration field [%s] to be rejected.',
                    $errorKey,
                ),
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                $errorKey,
                $exception->errors(),
            );
        }
    }
}
