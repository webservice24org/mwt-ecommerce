<?php

declare(strict_types=1);

namespace App\Domain\Auth\Admin\Actions;

use App\Domain\Auth\Admin\Data\CreateAdminData;
use App\Models\Admin;

final class CreateAdminAction
{
    public function execute(CreateAdminData $data): Admin
    {
        return Admin::query()->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
            'role' => $data->role,
            'is_active' => $data->isActive,
        ]);
    }
}
