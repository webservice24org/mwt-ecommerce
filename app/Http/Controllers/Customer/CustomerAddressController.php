<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Actions\Customer\CreateCustomerAddressAction;
use App\Actions\Customer\DeleteCustomerAddressAction;
use App\Actions\Customer\SetDefaultCustomerAddressAction;
use App\Actions\Customer\UpdateCustomerAddressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerAddressRequest;
use App\Http\Requests\Customer\UpdateCustomerAddressRequest;
use App\Models\Address;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CustomerAddressController extends Controller
{
    public function index(
        Request $request,
    ): Response {
        $user = $this->customer(
            $request,
        );

        $addresses = $user
            ->addresses()
            ->select([
                'id',
                'label',
                'recipient_name',
                'phone',
                'address_line_1',
                'address_line_2',
                'city',
                'state_or_region',
                'postal_code',
                'country_code',
                'is_default',
                'created_at',
            ])
            ->orderByDesc(
                'is_default',
            )
            ->orderByDesc(
                'id',
            )
            ->get();

        return Inertia::render(
            'Customer/Addresses/Index',
            [
                'addresses' => $addresses,
            ],
        );
    }

    public function create(): Response
    {
        return Inertia::render(
            'Customer/Addresses/Create',
            [
                'defaults' => [
                    'country_code' => 'BD',
                ],
            ],
        );
    }

    public function store(
        StoreCustomerAddressRequest $request,
        CreateCustomerAddressAction $action,
    ): RedirectResponse {
        $user = $this->customer(
            $request,
        );

        /**
         * @var array{
         *     label: string|null,
         *     recipient_name: string,
         *     phone: string,
         *     address_line_1: string,
         *     address_line_2: string|null,
         *     city: string,
         *     state_or_region: string|null,
         *     postal_code: string|null,
         *     country_code: string,
         *     is_default: bool
         * } $data
         */
        $data = $request->validated();

        $action->execute(
            $user,
            $data,
        );

        return redirect()
            ->route(
                'customer.addresses.index',
            )
            ->with(
                'success',
                'Delivery address added successfully.',
            );
    }

    public function edit(
        Request $request,
        Address $address,
    ): Response {
        $user = $this->customer(
            $request,
        );

        $this->ensureOwnership(
            $user,
            $address,
        );

        return Inertia::render(
            'Customer/Addresses/Edit',
            [
                'address' => [
                    'id' => $address->id,
                    'label' => $address->label,
                    'recipient_name' => $address->recipient_name,
                    'phone' => $address->phone,
                    'address_line_1' => $address->address_line_1,
                    'address_line_2' => $address->address_line_2,
                    'city' => $address->city,
                    'state_or_region' => $address->state_or_region,
                    'postal_code' => $address->postal_code,
                    'country_code' => $address->country_code,
                    'is_default' => $address->is_default,
                ],
            ],
        );
    }

    public function update(
        UpdateCustomerAddressRequest $request,
        Address $address,
        UpdateCustomerAddressAction $action,
    ): RedirectResponse {
        $user = $this->customer(
            $request,
        );

        $this->ensureOwnership(
            $user,
            $address,
        );

        /**
         * @var array{
         *     label: string|null,
         *     recipient_name: string,
         *     phone: string,
         *     address_line_1: string,
         *     address_line_2: string|null,
         *     city: string,
         *     state_or_region: string|null,
         *     postal_code: string|null,
         *     country_code: string,
         *     is_default: bool
         * } $data
         */
        $data = $request->validated();

        $action->execute(
            $user,
            $address,
            $data,
        );

        return redirect()
            ->route(
                'customer.addresses.index',
            )
            ->with(
                'success',
                'Delivery address updated successfully.',
            );
    }

    private function customer(
        Request $request,
    ): User {
        $user = $request->user();

        abort_unless(
            $user instanceof User,
            401,
        );

        return $user;
    }

    private function ensureOwnership(
        User $user,
        Address $address,
    ): void {
        abort_unless(
            $address->user_id === $user->id,
            404,
        );
    }

    public function destroy(
        Request $request,
        Address $address,
        DeleteCustomerAddressAction $action,
    ): RedirectResponse {
        $user = $this->customer(
            $request,
        );

        $this->ensureOwnership(
            $user,
            $address,
        );

        $action->execute(
            $user,
            $address,
        );

        return redirect()
            ->route(
                'customer.addresses.index',
            )
            ->with(
                'success',
                'Delivery address deleted successfully.',
            );
    }

    public function setDefault(
        Request $request,
        Address $address,
        SetDefaultCustomerAddressAction $action,
    ): RedirectResponse {
        $user = $this->customer(
            $request,
        );

        $this->ensureOwnership(
            $user,
            $address,
        );

        $action->execute(
            $user,
            $address,
        );

        return redirect()
            ->route(
                'customer.addresses.index',
            )
            ->with(
                'success',
                'Default delivery address updated successfully.',
            );
    }
}
