<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontCartPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_empty_cart_as_a_private_non_indexable_page(): void
    {
        $response = $this->get(route('cart.show'));

        $response->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Frontend/Cart/Index')
                ->has('storefrontCart.items', 0)
                ->where('storefrontCart.item_count', 0)
                ->where('storefrontCart.grand_total', 0));

        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        self::assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
    }

    public function test_added_items_and_authoritative_totals_are_shared_by_page_and_json(): void
    {
        $product = Product::factory()->published()->create(['price' => 5000]);

        $this->postJson(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 1,
        ])->assertOk()->assertJsonPath('grand_total', 5000);

        $this->get(route('cart.show'))->assertInertia(fn (Assert $page) => $page
            ->component('Frontend/Cart/Index')
            ->where('storefrontCart.items.0.product_id', $product->id)
            ->where('storefrontCart.grand_total', 5000));

        $this->getJson(route('cart.show'))->assertOk()
            ->assertJsonPath('items.0.product_id', $product->id)
            ->assertJsonPath('grand_total', 5000);

        $this->patchJson(route('cart.items.update', ['key' => "product:{$product->id}"]), [
            'quantity' => 2,
        ])->assertOk()->assertJsonPath('grand_total', 10000);

        $this->get(route('cart.show'))->assertInertia(fn (Assert $page) => $page
            ->where('storefrontCart.item_count', 2)
            ->where('storefrontCart.grand_total', 10000));

        $this->deleteJson(route('cart.items.destroy', ['key' => "product:{$product->id}"]))
            ->assertOk()->assertJsonPath('item_count', 0);

        $this->get(route('cart.show'))->assertInertia(fn (Assert $page) => $page
            ->has('storefrontCart.items', 0));
    }

    public function test_variable_product_requires_a_valid_selected_variant(): void
    {
        $product = Product::factory()->published()->create(['type' => 'variable']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 7500,
            'is_active' => true,
        ]);

        $this->postJson(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertUnprocessable();

        $this->postJson(route('cart.items.store'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk()->assertJsonPath('grand_total', 7500);

        $this->get(route('cart.show'))->assertInertia(fn (Assert $page) => $page
            ->where('storefrontCart.items.0.variant_id', $variant->id)
            ->where('storefrontCart.item_count', 1));
    }

    public function test_inertia_cart_visit_returns_page_even_with_json_accept_header(): void
    {
        $url = route('cart.show');
        $version = app(HandleInertiaRequests::class)->version(Request::create($url)) ?? '';

        $this->get($url, [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
            'Accept' => 'application/json',
        ])->assertOk()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'Frontend/Cart/Index')
            ->assertJsonPath('props.storefrontCart.item_count', 0);
    }
}
