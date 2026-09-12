<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Admin;

final class AdminPolicy
{
    public function before(
        Admin $admin,
        string $ability,
    ): ?bool {
        if ($admin->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(Admin $admin): bool
    {
        return false;
    }

    public function create(Admin $admin): bool
    {
        return false;
    }

    public function update(
        Admin $admin,
        Admin $target,
    ): bool {
        return false;
    }

    public function delete(
        Admin $admin,
        Admin $target,
    ): bool {
        return false;
    }
}
