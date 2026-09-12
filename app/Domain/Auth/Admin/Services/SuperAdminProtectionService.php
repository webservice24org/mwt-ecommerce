<?php

declare(strict_types=1);

namespace App\Domain\Auth\Admin\Services;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use RuntimeException;

final class SuperAdminProtectionService
{
    public function ensureCanModify(
        Admin $admin,
        ?AdminRole $newRole = null,
        ?bool $newActiveState = null,
    ): void {
        if (! $admin->isSuperAdmin()) {
            return;
        }

        $wouldLoseSuperAdmin =
            $newRole !== null
            && $newRole !== AdminRole::SuperAdmin;

        $wouldBecomeInactive =
            $newActiveState === false;

        if (! $wouldLoseSuperAdmin && ! $wouldBecomeInactive) {
            return;
        }

        $otherSuperAdminsExist = Admin::query()
            ->whereKeyNot($admin->getKey())
            ->where('role', AdminRole::SuperAdmin->value)
            ->where('is_active', true)
            ->exists();

        if (! $otherSuperAdminsExist) {
            throw new RuntimeException(
                'The final active super administrator cannot be disabled or demoted.',
            );
        }
    }
}
