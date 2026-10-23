<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\User;

final class UserPolicy
{
    public function before(
        Admin|User $actor,
        string $ability,
    ): ?bool {
        if (
            $actor instanceof Admin
            && $actor->isSuperAdmin()
        ) {
            return true;
        }

        return null;
    }

    public function viewAny(
        Admin|User $actor,
    ): bool {
        if (! $actor instanceof Admin) {
            return false;
        }

        return in_array(
            $actor->role,
            [
                AdminRole::Admin,
                AdminRole::Manager,
            ],
            true,
        );
    }

    public function view(
        Admin|User $actor,
        User $customer,
    ): bool {
        if ($actor instanceof User) {
            return $actor->is(
                $customer,
            );
        }

        return $this->viewAny(
            $actor,
        );
    }

    public function update(
        Admin|User $actor,
        User $customer,
    ): bool {
        if (! $actor instanceof Admin) {
            return false;
        }

        return in_array(
            $actor->role,
            [
                AdminRole::Admin,
                AdminRole::Manager,
            ],
            true,
        );
    }

    public function block(
        Admin|User $actor,
        User $customer,
    ): bool {
        return $this->update(
            $actor,
            $customer,
        );
    }

    public function unblock(
        Admin|User $actor,
        User $customer,
    ): bool {
        return $this->update(
            $actor,
            $customer,
        );
    }

    public function delete(
        Admin|User $actor,
        User $customer,
    ): bool {
        if (! $actor instanceof Admin) {
            return false;
        }

        return $actor->role ===
            AdminRole::Admin;
    }
}
