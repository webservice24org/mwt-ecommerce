<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Category;

final class CategoryPolicy
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

    public function viewAny(
        Admin $admin,
    ): bool {
        return $this->canManageCatalog($admin);
    }

    public function view(
        Admin $admin,
        Category $category,
    ): bool {
        return $this->canManageCatalog($admin);
    }

    public function create(
        Admin $admin,
    ): bool {
        return $this->canManageCatalog($admin);
    }

    public function update(
        Admin $admin,
        Category $category,
    ): bool {
        return $this->canManageCatalog($admin);
    }

    public function delete(
        Admin $admin,
        Category $category,
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

    private function canManageCatalog(
        Admin $admin,
    ): bool {
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
}
