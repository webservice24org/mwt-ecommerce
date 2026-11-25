<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Services;

use App\Domain\Seo\Ai\Data\AiSeoSuggestionData;
use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Domain\Seo\Ai\Exceptions\AiSeoSuggestionValidationException;

final class AiSeoSuggestionValidator
{
    private const MAX_SUGGESTIONS =
        6;

    private const MAX_RATIONALE_LENGTH =
        1000;

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $inputSnapshot
     * @return list<AiSeoSuggestionData>
     */
    public function validate(
        array $payload,
        array $inputSnapshot,
    ): array {
        $suggestions =
            $payload[
                'suggestions'
            ]
            ?? null;

        if (
            ! is_array(
                $suggestions,
            )
            || ! array_is_list(
                $suggestions,
            )
            || $suggestions === []
            || count(
                $suggestions,
            ) > self::MAX_SUGGESTIONS
        ) {
            throw new AiSeoSuggestionValidationException(
                'AI SEO provider must return between one and six suggestions.',
            );
        }

        $targetFields =
            $inputSnapshot[
                'target_fields'
            ]
            ?? null;

        if (
            ! is_array(
                $targetFields,
            )
        ) {
            throw new AiSeoSuggestionValidationException(
                'AI SEO request is missing its target-field contract.',
            );
        }

        $seen = [];
        $validated = [];

        foreach (
            $suggestions as $item
        ) {
            if (! is_array($item)) {
                throw new AiSeoSuggestionValidationException(
                    'AI SEO suggestion must be an object.',
                );
            }

            $fieldValue =
                $item[
                    'field'
                ]
                ?? null;

            if (! is_string($fieldValue)) {
                throw new AiSeoSuggestionValidationException(
                    'AI SEO suggestion field must be a string.',
                );
            }

            $field =
                AiSeoSuggestionField::tryFrom(
                    $fieldValue,
                );

            if (
                $field === null
                || ! array_key_exists(
                    $field->value,
                    $targetFields,
                )
            ) {
                throw new AiSeoSuggestionValidationException(
                    sprintf(
                        'Unsupported AI SEO suggestion field: %s.',
                        $fieldValue,
                    ),
                );
            }

            if (
                isset(
                    $seen[
                        $field->value
                    ],
                )
            ) {
                throw new AiSeoSuggestionValidationException(
                    sprintf(
                        'Duplicate AI SEO suggestion field: %s.',
                        $field->value,
                    ),
                );
            }

            $contract =
                $targetFields[
                    $field->value
                ];

            if (! is_array($contract)) {
                throw new AiSeoSuggestionValidationException(
                    'AI SEO target-field contract is invalid.',
                );
            }

            $maxLength =
                $contract[
                    'max_length'
                ]
                ?? null;

            if (
                ! is_int(
                    $maxLength,
                )
                || $maxLength <= 0
                || $maxLength > 1000
            ) {
                throw new AiSeoSuggestionValidationException(
                    'AI SEO target-field maximum length is invalid.',
                );
            }

            $value =
                $this->requiredPlainText(
                    $item[
                        'value'
                    ]
                    ?? null,
                    $maxLength,
                    'AI SEO suggestion value',
                );

            $rationale =
                $this->nullablePlainText(
                    $item[
                        'rationale'
                    ]
                    ?? null,
                    self::MAX_RATIONALE_LENGTH,
                    'AI SEO suggestion rationale',
                );

            $validated[] =
                new AiSeoSuggestionData(
                    field: $field,

                    value: $value,

                    rationale: $rationale,
                );

            $seen[
                $field->value
            ] = true;
        }

        return $validated;
    }

    private function requiredPlainText(
        mixed $value,
        int $maxLength,
        string $label,
    ): string {
        if (! is_string($value)) {
            throw new AiSeoSuggestionValidationException(
                "{$label} must be a string.",
            );
        }

        $value =
            $this->normalizeWhitespace(
                $value,
            );

        if ($value === '') {
            throw new AiSeoSuggestionValidationException(
                "{$label} cannot be blank.",
            );
        }

        $this->assertSafePlainText(
            $value,
            $label,
        );

        if (
            mb_strlen(
                $value,
            ) > $maxLength
        ) {
            throw new AiSeoSuggestionValidationException(
                sprintf(
                    '%s exceeds the %d character limit.',
                    $label,
                    $maxLength,
                ),
            );
        }

        return $value;
    }

    private function nullablePlainText(
        mixed $value,
        int $maxLength,
        string $label,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new AiSeoSuggestionValidationException(
                "{$label} must be a string or null.",
            );
        }

        $value =
            $this->normalizeWhitespace(
                $value,
            );

        if ($value === '') {
            return null;
        }

        $this->assertSafePlainText(
            $value,
            $label,
        );

        if (
            mb_strlen(
                $value,
            ) > $maxLength
        ) {
            throw new AiSeoSuggestionValidationException(
                sprintf(
                    '%s exceeds the %d character limit.',
                    $label,
                    $maxLength,
                ),
            );
        }

        return $value;
    }

    private function normalizeWhitespace(
        string $value,
    ): string {
        $value =
            preg_replace(
                '/\s+/u',
                ' ',
                $value,
            )
            ?? $value;

        return trim(
            $value,
        );
    }

    private function assertSafePlainText(
        string $value,
        string $label,
    ): void {
        if (
            strip_tags(
                $value,
            ) !== $value
        ) {
            throw new AiSeoSuggestionValidationException(
                "{$label} must not contain HTML.",
            );
        }

        if (
            preg_match(
                '/(?:https?:\/\/|www\.)/iu',
                $value,
            ) === 1
        ) {
            throw new AiSeoSuggestionValidationException(
                "{$label} must not contain URLs.",
            );
        }

        if (
            preg_match(
                '/```|`[^`]+`/u',
                $value,
            ) === 1
        ) {
            throw new AiSeoSuggestionValidationException(
                "{$label} must not contain code or Markdown code formatting.",
            );
        }

        if (
            preg_match(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
                $value,
            ) === 1
        ) {
            throw new AiSeoSuggestionValidationException(
                "{$label} contains unsupported control characters.",
            );
        }
    }
}
