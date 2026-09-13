<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Brand;

final class BrandPolicy
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
        return in_array(
            $admin->role,
            [
                AdminRole::Admin,
                AdminRole::Manager,
                AdminRole::Editor,
            ],
            true,
        );
    }

    public function view(
        Admin $admin,
        Brand $brand,
    ): bool {
        return $this->viewAny($admin);
    }

    public function create(Admin $admin): bool
    {
        return $this->viewAny($admin);
    }

    public function update(
        Admin $admin,
        Brand $brand,
    ): bool {
        return $this->viewAny($admin);
    }

    public function delete(
        Admin $admin,
        Brand $brand,
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
}
