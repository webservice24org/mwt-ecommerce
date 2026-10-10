<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;

final class HeaderSettingPolicy
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

    public function updateAny(
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
}
