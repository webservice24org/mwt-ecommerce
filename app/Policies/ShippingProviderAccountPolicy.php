<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\ShippingProviderAccount;

final class ShippingProviderAccountPolicy
{
    public function before(
        Admin $admin,
        string $ability,
    ): ?bool {
        if (
            $admin->isSuperAdmin()
        ) {
            return true;
        }

        return null;
    }

    public function viewAny(
        Admin $admin,
    ): bool {
        return in_array(
            $admin->role,
            [
                AdminRole::Admin,
                AdminRole::Manager,
            ],
            true,
        );
    }

    public function view(
        Admin $admin,
        ShippingProviderAccount $account,
    ): bool {
        return $this->viewAny(
            $admin,
        );
    }

    public function create(
        Admin $admin,
    ): bool {
        return $this->viewAny(
            $admin,
        );
    }

    public function update(
        Admin $admin,
        ShippingProviderAccount $account,
    ): bool {
        return $this->viewAny(
            $admin,
        );
    }

    public function delete(
        Admin $admin,
        ShippingProviderAccount $account,
    ): bool {
        return $this->viewAny(
            $admin,
        );
    }
}
