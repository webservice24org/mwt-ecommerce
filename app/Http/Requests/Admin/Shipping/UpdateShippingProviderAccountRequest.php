<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Shipping;

use App\Domain\Shipping\Enums\ShippingProviderCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateShippingProviderAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        /*
         * The controller performs the model/class policy checks
         * because the account may not exist yet.
         */
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $provider =
            $this->providerCode();

        if (
            $provider ===
            ShippingProviderCode::Steadfast
        ) {
            return $this
                ->steadfastRules();
        }

        /*
         * Pathao remains the default supported configuration
         * contract for this request. Unsupported provider codes
         * are rejected by the controller's editableProvider()
         * boundary.
         */
        return $this
            ->pathaoRules();
    }

    /**
     * @return array<string, mixed>
     */
    private function pathaoRules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
            ],

            'credentials' => [
                'nullable',
                'array',
            ],

            'credentials.client_id' => [
                'nullable',
                'string',
                'max:500',
            ],

            'credentials.client_secret' => [
                'nullable',
                'string',
                'max:500',
            ],

            'credentials.access_token' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'credentials.username' => [
                'nullable',
                'string',
                'max:255',
            ],

            'credentials.password' => [
                'nullable',
                'string',
                'max:500',
            ],

            'settings' => [
                'required',
                'array',
            ],

            'settings.store_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'settings.delivery_type' => [
                'required',
                'integer',
                Rule::in([
                    12,
                    48,
                ]),
            ],

            'settings.item_type' => [
                'required',
                'integer',
                Rule::in([
                    1,
                    2,
                ]),
            ],

            'settings.default_item_weight_kg' => [
                'required',
                'numeric',
                'between:0.5,10',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function steadfastRules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
            ],

            'credentials' => [
                'nullable',
                'array',
            ],

            /*
             * These remain nullable at the FormRequest layer
             * because editing an existing account with a blank
             * field means "retain the encrypted value".
             *
             * The controller merges existing encrypted values
             * and then enforces that both credentials exist.
             */
            'credentials.api_key' => [
                'nullable',
                'string',
                'max:500',
            ],

            'credentials.secret_key' => [
                'nullable',
                'string',
                'max:500',
            ],

            /*
             * Do not validate Pathao fulfilment defaults here.
             * The shared React form still carries a settings
             * object, but those values do not belong to a
             * Steadfast merchant account.
             */
        ];
    }

    private function providerCode(
    ): ?ShippingProviderCode {
        $provider =
            $this->route(
                'provider',
            );

        if (! is_string($provider)) {
            return null;
        }

        return ShippingProviderCode::tryFrom(
            strtolower(
                trim(
                    $provider,
                ),
            ),
        );
    }
}
