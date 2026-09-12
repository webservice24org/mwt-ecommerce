<?php

declare(strict_types=1);

namespace App\Domain\Auth\Admin\Actions;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use RuntimeException;

final class DeleteAdminAction
{
    public function execute(
        Admin $admin,
        Admin $actingAdmin,
    ): void {
        if ($admin->is($actingAdmin)) {
            throw new RuntimeException(
                'You cannot delete your own administrator account.',
            );
        }

        if ($admin->role === AdminRole::SuperAdmin) {
            $otherActiveSuperAdminsExist = Admin::query()
                ->whereKeyNot($admin->getKey())
                ->where('role', AdminRole::SuperAdmin->value)
                ->where('is_active', true)
                ->exists();

            if (! $otherActiveSuperAdminsExist) {
                throw new RuntimeException(
                    'The final active super administrator cannot be deleted.',
                );
            }
        }

        $admin->delete();
    }
}
