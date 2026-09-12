<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Auth\Admin\Actions\CreateAdminAction;
use App\Domain\Auth\Admin\Actions\DeleteAdminAction;
use App\Domain\Auth\Admin\Actions\UpdateAdminAction;
use App\Domain\Auth\Admin\Data\CreateAdminData;
use App\Domain\Auth\Admin\Data\UpdateAdminData;
use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class AdminController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Admin::class);

        $admins = Admin::query()
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Admins/Index', [
            'admins' => $admins,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Admin::class);

        return Inertia::render('Admin/Admins/Create', [
            'roles' => array_map(
                static fn (AdminRole $role): array => [
                    'label' => match ($role) {
                        AdminRole::SuperAdmin => 'Super Admin',
                        AdminRole::Admin => 'Admin',
                        AdminRole::Manager => 'Manager',
                        AdminRole::Editor => 'Editor',
                    },
                    'value' => $role->value,
                ],
                AdminRole::cases(),
            ),
        ]);
    }

    public function store(
        StoreAdminRequest $request,
        CreateAdminAction $action,
    ): RedirectResponse {
        $this->authorize('create', Admin::class);

        $data = new CreateAdminData(
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            role: AdminRole::from(
                $request->string('role')->toString(),
            ),
            isActive: $request->boolean('is_active'),
        );

        $action->execute($data);

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Administrator created successfully.');
    }

    public function edit(Admin $admin): Response
    {
        $this->authorize('update', $admin);

        return Inertia::render('Admin/Admins/Edit', [
            'admin' => $admin,
            'roles' => array_map(
                static fn (AdminRole $role): array => [
                    'label' => match ($role) {
                        AdminRole::SuperAdmin => 'Super Admin',
                        AdminRole::Admin => 'Admin',
                        AdminRole::Manager => 'Manager',
                        AdminRole::Editor => 'Editor',
                    },
                    'value' => $role->value,
                ],
                AdminRole::cases(),
            ),
        ]);
    }

    public function update(
        UpdateAdminRequest $request,
        Admin $admin,
        UpdateAdminAction $action,
    ): RedirectResponse {
        $this->authorize('update', $admin);

        $data = new UpdateAdminData(
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            role: AdminRole::from(
                $request->string('role')->toString(),
            ),
            isActive: $request->boolean('is_active'),
            password: $request->filled('password')
                ? $request->string('password')->toString()
                : null,
        );

        try {
            $action->execute($admin, $data);
        } catch (RuntimeException $exception) {
            return back()->with(
                'error',
                $exception->getMessage(),
            );
        }

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Administrator updated successfully.');
    }

    public function destroy(
        Request $request,
        Admin $admin,
        DeleteAdminAction $action,
    ): RedirectResponse {
        $this->authorize('delete', $admin);

        $actingAdmin = $request->user('admin');

        if (! $actingAdmin instanceof Admin) {
            abort(403);
        }

        try {
            $action->execute(
                $admin,
                $actingAdmin,
            );
        } catch (RuntimeException $exception) {
            return back()->with(
                'error',
                $exception->getMessage(),
            );
        }

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Administrator deleted successfully.');
    }
}
