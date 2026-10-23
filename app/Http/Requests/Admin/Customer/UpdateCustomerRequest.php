<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Customer;

use App\Domain\Customer\Data\UpdateCustomerData;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCustomerRequest extends FormRequest
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
        $customer = $this->customer();

        return [
            'name' => [
                'required',
                'string',
                'max:120',
            ],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email:rfc',
                'max:254',
                Rule::unique(
                    User::class,
                    'email',
                )->ignore(
                    $customer->getKey(),
                ),
            ],
        ];
    }

    public function toData(): UpdateCustomerData
    {
        return new UpdateCustomerData(
            name: (string) $this->validated(
                'name',
            ),
            email: (string) $this->validated(
                'email',
            ),
        );
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input(
            'name',
        );

        $email = $this->input(
            'email',
        );

        $this->merge([
            'name' => is_string($name)
                ? trim($name)
                : $name,

            'email' => is_string($email)
                ? mb_strtolower(
                    trim($email),
                )
                : $email,
        ]);
    }

    private function customer(): User
    {
        $customer = $this->route(
            'customer',
        );

        if (! $customer instanceof User) {
            abort(404);
        }

        return $customer;
    }
}
