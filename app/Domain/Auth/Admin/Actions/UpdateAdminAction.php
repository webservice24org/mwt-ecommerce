<?php

declare(strict_types=1);

namespace App\Domain\Auth\Admin\Actions;

use App\Domain\Auth\Admin\Data\UpdateAdminData;
use App\Domain\Auth\Admin\Services\SuperAdminProtectionService;
use App\Models\Admin;

final readonly class UpdateAdminAction
{
    public function __construct(
        private SuperAdminProtectionService $protection,
    ) {}

    public function execute(
        Admin $admin,
        UpdateAdminData $data,
    ): Admin {
        $this->protection->ensureCanModify(
            $admin,
            $data->role,
            $data->isActive,
        );

        $values = [
            'name' => $data->name,
            'email' => $data->email,
            'role' => $data->role,
            'is_active' => $data->isActive,
        ];

        if ($data->password !== null && $data->password !== '') {
            $values['password'] = $data->password;
        }

        $admin->update($values);

        return $admin->refresh();
    }
}
