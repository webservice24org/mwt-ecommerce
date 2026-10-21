<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Actions\Customer\CreateCustomerAddressAction;
use App\Domain\Checkout\Services\CheckoutSessionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\SelectCheckoutAddressRequest;
use App\Http\Requests\Customer\StoreCustomerAddressRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class CheckoutAddressController extends Controller
{
    public function select(
        SelectCheckoutAddressRequest $request,
        CheckoutSessionService $checkoutSession,
    ): RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user instanceof User,
            401,
        );

        $addressId = $request->integer(
            'address_id',
        );

        $address = $user
            ->addresses()
            ->whereKey(
                $addressId,
            )
            ->firstOrFail();

        $checkoutSession->selectAddress(
            $address,
        );

        return redirect()
            ->route(
                'checkout.index',
            )
            ->with(
                'success',
                'Delivery address selected.',
            );
    }

    public function store(
        StoreCustomerAddressRequest $request,
        CreateCustomerAddressAction $action,
        CheckoutSessionService $checkoutSession,
    ): RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user instanceof User,
            401,
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

        $address = $action->execute(
            $user,
            $data,
        );

        $checkoutSession->selectAddress(
            $address,
        );

        return redirect()
            ->route(
                'checkout.index',
            )
            ->with(
                'success',
                'Delivery address added and selected.',
            );
    }
}
