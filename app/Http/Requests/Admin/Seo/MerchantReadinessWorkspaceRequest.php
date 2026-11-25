<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Seo;

use App\Domain\Seo\Merchant\Enums\MerchantIdentifierState;
use App\Domain\Seo\Merchant\Enums\MerchantListingFindingSeverity;
use App\Domain\Seo\Merchant\Enums\MerchantListingReadinessStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class MerchantReadinessWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:120',
            ],

            'status' => [
                'nullable',
                Rule::enum(
                    MerchantListingReadinessStatus::class,
                ),
            ],

            'feed' => [
                'nullable',
                Rule::in([
                    'eligible',
                    'excluded',
                ]),
            ],

            'availability' => [
                'nullable',
                'string',
                'max:64',
                'regex:/\A[a-z0-9_]+\z/',
            ],

            'identifier_state' => [
                'nullable',
                Rule::enum(
                    MerchantIdentifierState::class,
                ),
            ],

            'severity' => [
                'nullable',
                Rule::enum(
                    MerchantListingFindingSeverity::class,
                ),
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
                'max:10000',
            ],

            'export_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:10000',
            ],
        ];
    }

    public function search(): ?string
    {
        $value =
            trim(
                $this
                    ->string(
                        'search',
                    )
                    ->toString(),
            );

        return $value === ''
            ? null
            : $value;
    }

    public function status(): ?MerchantListingReadinessStatus
    {
        return MerchantListingReadinessStatus::tryFrom(
            $this
                ->string(
                    'status',
                )
                ->toString(),
        );
    }

    public function feed(): ?string
    {
        $value =
            $this
                ->string(
                    'feed',
                )
                ->toString();

        return $value === ''
            ? null
            : $value;
    }

    public function availability(): ?string
    {
        $value =
            $this
                ->string(
                    'availability',
                )
                ->toString();

        return $value === ''
            ? null
            : $value;
    }

    public function identifierState(): ?MerchantIdentifierState
    {
        return MerchantIdentifierState::tryFrom(
            $this
                ->string(
                    'identifier_state',
                )
                ->toString(),
        );
    }

    public function severity(): ?MerchantListingFindingSeverity
    {
        return MerchantListingFindingSeverity::tryFrom(
            $this
                ->string(
                    'severity',
                )
                ->toString(),
        );
    }

    public function page(): int
    {
        return max(
            1,
            $this->integer(
                'page',
                1,
            ),
        );
    }

    public function exportPage(): int
    {
        return max(
            1,
            $this->integer(
                'export_page',
                1,
            ),
        );
    }
}
