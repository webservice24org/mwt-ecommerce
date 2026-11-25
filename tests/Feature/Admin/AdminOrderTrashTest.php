<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Orders\Actions\CancelOrderAction;
use App\Domain\Orders\Actions\CreateOrderAction;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Orders\Services\OrderTrashLedger;
use App\Models\Admin;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminOrderTrashTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_tabs_count_and_filter_only_untrashed_orders(): void
    {
        $this->actingAs(Admin::factory()->superAdmin()->create(), 'admin');
        $pending = Order::factory()->create();
        Order::factory()->create(['status' => OrderStatus::Confirmed]);
        Order::factory()->create(['status' => OrderStatus::Processing]);
        Order::factory()->create(['status' => OrderStatus::Completed]);
        $trashed = Order::factory()->create(['status' => OrderStatus::Cancelled]);
        $this->delete(route('admin.orders.destroy', $trashed))->assertRedirect();

        $this->get(route('admin.orders.index', ['tab' => 'pending']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Orders/Index')
                ->where('tab', 'pending')
                ->where('orders.total', 1)
                ->where('orders.data.0.id', $pending->id)
                ->where('orders.data.0.can_trash', false)
                ->where('tabs.0.count', 4)
                ->where('tabs.1.count', 1)
                ->where('tabs.5.count', 0)
                ->where('tabs.6.value', 'trash')
                ->where('tabs.6.count', 1));

        $this->get(route('admin.orders.index', ['tab' => 'trash']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('orders.total', 1)
                ->where('orders.data.0.id', $trashed->id)
                ->where('orders.data.0.can_purge', true));
        $this->get(route('admin.orders.index', ['tab' => 'unexpected']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('tab', 'all')->where('orders.total', 4));
    }

    public function test_other_admin_roles_cannot_access_trash_or_mutate_orders(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Cancelled]);
        foreach ([AdminRole::Admin, AdminRole::Manager, AdminRole::Editor] as $role) {
            $this->actingAs(Admin::factory()->create(['role' => $role]), 'admin');
            $this->get(route('admin.orders.index'))->assertOk()
                ->assertInertia(fn (Assert $page) => $page->where('can_manage_trash', false)
                    ->has('tabs', 6)->where('orders.data.0.can_trash', false));
            $this->get(route('admin.orders.index', ['tab' => 'trash']))->assertForbidden();
            $this->delete(route('admin.orders.destroy', $order))->assertForbidden();
            $this->post(route('admin.orders.restore', $order))->assertForbidden();
            $this->delete(route('admin.orders.purge', $order))->assertForbidden();
        }
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'trashed_at' => null]);
        $this->assertDatabaseCount('order_trash_events', 0);
    }

    public function test_guest_and_customer_cannot_delete_orders(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->for($customer)->create(['status' => OrderStatus::Cancelled]);
        $this->delete(route('admin.orders.destroy', $order))->assertRedirect(route('admin.login'));
        $this->actingAs($customer, 'web');
        $this->delete(route('admin.orders.purge', $order))->assertRedirect(route('admin.login'));
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_active_orders_must_follow_the_existing_cancellation_workflow(): void
    {
        Mail::fake();
        $admin = Admin::factory()->superAdmin()->create();
        $this->actingAs($admin, 'admin');
        $order = Order::factory()->create();
        $product = Product::factory()->published()->withStock(7)->create();
        OrderItem::factory()->for($order)->create(['product_id' => $product->id, 'quantity' => 3, 'stock_was_decremented' => true]);
        $this->delete(route('admin.orders.destroy', $order))->assertSessionHasErrors('order_trash');
        $this->assertSame(7, $product->refresh()->stock_quantity);

        app(CancelOrderAction::class)->execute($order, $admin, 'Test cancellation');
        $this->assertSame(10, $product->refresh()->stock_quantity);
        $this->delete(route('admin.orders.destroy', $order))->assertRedirect();
        $this->delete(route('admin.orders.purge', $order))->assertRedirect();
        $this->assertSame(10, $product->refresh()->stock_quantity);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['order_id' => $order->id]);
        $this->assertDatabaseHas('order_trash_events', ['order_id' => $order->id, 'action' => 'purged', 'admin_id' => $admin->id]);
    }

    public function test_archive_restore_is_idempotent_and_preserves_customer_history(): void
    {
        $admin = Admin::factory()->superAdmin()->create();
        $customer = User::factory()->create();
        $order = Order::factory()->for($customer)->create(['status' => OrderStatus::Completed, 'payment_status' => PaymentStatus::Paid]);
        $this->actingAs($admin, 'admin');
        $this->delete(route('admin.orders.destroy', $order))->assertRedirect();
        $this->delete(route('admin.orders.destroy', $order))->assertRedirect();
        $this->assertNotNull($order->refresh()->trashed_at);
        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertDatabaseCount('order_trash_events', 1);
        $this->actingAs($customer, 'web')->get(route('customer.orders.show', $order))->assertOk();
        $this->actingAs($admin, 'admin')->post(route('admin.orders.restore', $order))->assertRedirect();
        $this->post(route('admin.orders.restore', $order))->assertRedirect();
        $this->assertNull($order->refresh()->trashed_at);
        $this->assertDatabaseCount('order_trash_events', 2);
        $this->get(route('admin.orders.index', ['tab' => 'completed']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('orders.total', 1));
    }

    public function test_permanent_delete_requires_trash_and_protects_completed_or_paid_orders(): void
    {
        $this->actingAs(Admin::factory()->superAdmin()->create(), 'admin');
        $notTrashed = Order::factory()->create(['status' => OrderStatus::Cancelled]);
        $this->delete(route('admin.orders.purge', $notTrashed))->assertSessionHasErrors('order_trash');
        foreach ([OrderStatus::Completed, OrderStatus::Cancelled] as $status) {
            $order = Order::factory()->create(['status' => $status, 'payment_status' => PaymentStatus::Paid]);
            $this->delete(route('admin.orders.destroy', $order))->assertRedirect();
            $this->delete(route('admin.orders.purge', $order))->assertSessionHasErrors('order_trash');
            $this->assertDatabaseHas('orders', ['id' => $order->id]);
        }
    }

    public function test_protected_linked_records_block_permanent_deletion(): void
    {
        $this->actingAs(Admin::factory()->superAdmin()->create(), 'admin');
        $invoiceOrder = Order::factory()->create(['status' => OrderStatus::Cancelled]);
        $invoice = Invoice::factory()->for($invoiceOrder)->create();
        $shipmentOrder = Order::factory()->create(['status' => OrderStatus::Cancelled]);
        $shipment = Shipment::factory()->for($shipmentOrder)->create();
        $reviewOrder = Order::factory()->create(['status' => OrderStatus::Cancelled]);
        $review = ProductReview::factory()->create(['verified_order_id' => $reviewOrder->id, 'user_id' => $reviewOrder->user_id]);
        $promoOrder = Order::factory()->create(['status' => OrderStatus::Cancelled]);
        $promo = PromoCode::query()->create(['code' => 'TRASH-PROTECT', 'name' => 'Protected promo', 'discount_type' => 'percentage', 'discount_value' => 1000, 'is_active' => true]);
        $usage = PromoCodeUsage::query()->create(['promo_code_id' => $promo->id, 'user_id' => $promoOrder->user_id, 'order_id' => $promoOrder->id, 'code' => 'TRASH-PROTECT', 'discount_amount' => 100, 'used_at' => now()]);
        foreach ([$invoiceOrder, $shipmentOrder, $reviewOrder, $promoOrder] as $order) {
            $this->delete(route('admin.orders.destroy', $order))->assertRedirect();
            $this->delete(route('admin.orders.purge', $order))->assertSessionHasErrors('order_trash');
            $this->assertDatabaseHas('orders', ['id' => $order->id]);
        }
        $this->assertModelExists($invoice);
        $this->assertModelExists($shipment);
        $this->assertModelExists($review);
        $this->assertModelExists($usage);
    }

    public function test_unknown_stock_and_previous_financial_activity_are_protected(): void
    {
        $this->actingAs(Admin::factory()->superAdmin()->create(), 'admin');
        $legacyOrder = Order::factory()->create(['status' => OrderStatus::Cancelled]);
        OrderItem::factory()->for($legacyOrder)->create(['stock_was_decremented' => null]);
        $financialOrder = Order::factory()->create(['status' => OrderStatus::Cancelled]);
        $financialOrder->statusHistories()->create([
            'type' => OrderStatusHistory::TYPE_PAYMENT,
            'from_status' => PaymentStatus::Paid->value,
            'to_status' => PaymentStatus::Refunded->value,
            'changed_at' => now(),
        ]);
        foreach ([$legacyOrder, $financialOrder] as $order) {
            $this->delete(route('admin.orders.destroy', $order))->assertRedirect();
            $this->delete(route('admin.orders.purge', $order))->assertSessionHasErrors('order_trash');
            $this->assertDatabaseHas('orders', ['id' => $order->id]);
        }
    }

    public function test_purge_retires_checkout_token_and_retains_audit_without_the_order(): void
    {
        $admin = Admin::factory()->superAdmin()->create();
        $customer = User::factory()->create();
        $token = 'b47f1000-d021-4e4a-a0ab-001020304050';
        $order = Order::factory()->for($customer)->create(['status' => OrderStatus::Cancelled, 'idempotency_key' => $token]);
        $this->actingAs($admin, 'admin');
        $this->delete(route('admin.orders.destroy', $order))->assertRedirect();
        $this->delete(route('admin.orders.purge', $order))->assertRedirect();
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseHas('order_trash_events', ['order_id' => $order->id, 'order_number' => $order->order_number, 'action' => 'purged', 'admin_id' => $admin->id, 'submission_fingerprint' => OrderTrashLedger::fingerprint($customer->id, $token)]);
        // Another customer using the same token is not affected.
        OrderTrashLedger::assertSubmissionAvailable(User::factory()->create()->id, $token);
        try {
            app(CreateOrderAction::class)->execute($customer, 1, $token);
            self::fail('A retired checkout token must be rejected before checkout validation.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('order_submission', $exception->errors());
        }
        $this->assertDatabaseCount('orders', 0);
    }
}
