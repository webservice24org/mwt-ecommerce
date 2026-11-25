<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai;

use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Domain\Seo\Ai\Exceptions\AiSeoSuggestionValidationException;
use App\Domain\Seo\Ai\Services\AiSeoSuggestionValidator;
use PHPUnit\Framework\TestCase;

final class AiSeoSuggestionValidatorTest extends TestCase
{
    private AiSeoSuggestionValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator =
            new AiSeoSuggestionValidator;
    }

    public function test_valid_safe_suggestions_are_normalized_and_converted_to_domain_data(): void
    {
        $suggestions =
            $this
                ->validator
                ->validate(
                    [
                        'suggestions' => [
                            [
                                'field' => 'meta_title',

                                'value' => '  Braided USB Cable   | MWT Store  ',

                                'rationale' => '  Uses the product name clearly. ',
                            ],
                        ],
                    ],
                    $this->contract(),
                );

        self::assertCount(
            1,
            $suggestions,
        );

        self::assertSame(
            AiSeoSuggestionField::MetaTitle,
            $suggestions[0]->field,
        );

        self::assertSame(
            'Braided USB Cable | MWT Store',
            $suggestions[0]->value,
        );

        self::assertSame(
            'Uses the product name clearly.',
            $suggestions[0]->rationale,
        );
    }

    public function test_unknown_or_protected_fields_are_rejected(): void
    {
        $this->expectException(
            AiSeoSuggestionValidationException::class,
        );

        $this
            ->validator
            ->validate(
                [
                    'suggestions' => [
                        [
                            'field' => 'canonical_url',

                            'value' => 'Something',
                        ],
                    ],
                ],
                $this->contract(),
            );
    }

    public function test_values_over_the_request_contract_limit_are_rejected(): void
    {
        $this->expectException(
            AiSeoSuggestionValidationException::class,
        );

        $this
            ->validator
            ->validate(
                [
                    'suggestions' => [
                        [
                            'field' => 'meta_title',

                            'value' => str_repeat(
                                'x',
                                181,
                            ),
                        ],
                    ],
                ],
                $this->contract(),
            );
    }

    public function test_html_and_urls_are_rejected_even_if_provider_schema_was_valid(): void
    {
        foreach (
            [
                '<strong>Bad title</strong>',
                'Bad title https://example.com',
            ] as $unsafe
        ) {
            try {
                $this
                    ->validator
                    ->validate(
                        [
                            'suggestions' => [
                                [
                                    'field' => 'meta_title',

                                    'value' => $unsafe,
                                ],
                            ],
                        ],
                        $this->contract(),
                    );

                self::fail(
                    'Unsafe AI SEO value was accepted.',
                );
            } catch (
                AiSeoSuggestionValidationException
            ) {
                self::assertTrue(
                    true,
                );
            }
        }
    }

    public function test_duplicate_fields_are_rejected(): void
    {
        $this->expectException(
            AiSeoSuggestionValidationException::class,
        );

        $this
            ->validator
            ->validate(
                [
                    'suggestions' => [
                        [
                            'field' => 'meta_title',

                            'value' => 'First',
                        ],

                        [
                            'field' => 'meta_title',

                            'value' => 'Second',
                        ],
                    ],
                ],
                $this->contract(),
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function contract(): array
    {
        return [
            'target_fields' => [
                'meta_title' => [
                    'max_length' => 180,
                ],

                'meta_description' => [
                    'max_length' => 320,
                ],

                'og_title' => [
                    'max_length' => 180,
                ],

                'og_description' => [
                    'max_length' => 320,
                ],

                'twitter_title' => [
                    'max_length' => 180,
                ],

                'twitter_description' => [
                    'max_length' => 320,
                ],
            ],
        ];
    }
}
