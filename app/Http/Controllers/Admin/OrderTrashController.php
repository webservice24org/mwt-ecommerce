<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Orders\Actions\ManageOrderTrashAction;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class OrderTrashController extends Controller
{
    public function destroy(Request $request, Order $order, ManageOrderTrashAction $action): RedirectResponse
    {
        $action->trash($order, $this->superAdmin($request));

        return to_route('admin.orders.index')->with('success', 'Order moved to Trash. You can restore it from the Trash tab.');
    }

    public function restore(Request $request, Order $order, ManageOrderTrashAction $action): RedirectResponse
    {
        $action->restore($order, $this->superAdmin($request));

        return to_route('admin.orders.index', ['tab' => 'trash'])->with('success', 'Order restored.');
    }

    public function purge(Request $request, Order $order, ManageOrderTrashAction $action): RedirectResponse
    {
        $action->purge($order, $this->superAdmin($request));

        return to_route('admin.orders.index', ['tab' => 'trash'])->with('success', 'Order permanently deleted.');
    }

    private function superAdmin(Request $request): Admin
    {
        $admin = $request->user('admin');
        abort_unless($admin instanceof Admin && $admin->is_active && $admin->isSuperAdmin(), 403);

        return $admin;
    }
}
