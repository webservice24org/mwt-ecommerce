<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Actions;

use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Domain\Shipping\Services\ShippingProviderAccountManager;
use App\Domain\Shipping\Services\SteadfastShippingProviderFactory;
use App\Models\Admin;
use App\Models\ShippingProviderAccount;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class TestSteadfastProviderAccountConnectionAction
{
    public function __construct(
        private SteadfastShippingProviderFactory $providers,
        private ShippingProviderAccountManager $accounts,
    ) {}

    public function execute(
        ShippingProviderAccount $account,
        Admin $actor,
    ): ShippingProviderAccount {
        if (
            $account->provider_code !==
            ShippingProviderCode::Steadfast
        ) {
            throw ValidationException::withMessages([
                'provider' => 'The selected courier account is not Steadfast.',
            ]);
        }

        try {
            $provider =
                $this->providers
                    ->fromAccount(
                        $account,
                    );

            /*
             * GET /get_balance is the documented,
             * non-destructive credential verification call.
             */
            $provider->testConnection();

            $account =
                $this->accounts
                    ->markConnected(
                        $account,
                        $actor,
                    );

            return $this->accounts
                ->updateOperationalSettings(
                    account: $account,

                    isActive: true,

                    autoSubmit: $account
                        ->auto_submit,

                    isDefault: $account
                        ->is_default,

                    actor: $actor,
                );
        } catch (Throwable $exception) {
            $fresh =
                $account->fresh();

            if (
                $fresh instanceof ShippingProviderAccount
            ) {
                $this->accounts
                    ->markConnectionError(
                        account: $fresh,

                        message: 'Steadfast connection test failed. Verify the API Key and Secret Key.',

                        actor: $actor,
                    );
            }

            throw ValidationException::withMessages([
                'provider' => 'Steadfast connection test failed. Verify the API Key and Secret Key.',
            ]);
        }
    }
}
