<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Domain\Customer\Actions\RemoveCustomerAction;
use App\Domain\Customer\Enums\CustomerRemovalOutcome;
use App\Domain\Customer\Enums\CustomerStatus;
use App\Models\Admin;
use App\Models\CustomerAvatar;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class CustomerAvatarTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jq2sAAAAASUVORK5CYII=';

    private function photo(): File
    {
        $bytes = base64_decode(self::PNG, true);
        self::assertIsString($bytes);

        return UploadedFile::fake()->createWithContent('avatar.png', $bytes);
    }

    public function test_avatar_is_optional_and_profile_remains_accessible_before_email_verification(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->get('/profile')->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('Profile/Edit')->where('customerAvatar', null),
        );
        $this->get(route('customer.avatar.show'))->assertNotFound();
        $this->post(route('customer.avatar.store'), ['avatar' => $this->photo()])->assertRedirect('/profile')->assertSessionHasNoErrors();
        self::assertNull($user->refresh()->email_verified_at);
    }

    public function test_upload_replace_and_remove_are_reflected_in_account_props(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('customer.avatar.store'), ['avatar' => $this->photo()])->assertRedirect('/profile')->assertSessionHasNoErrors();
        $first = CustomerAvatar::query()->findOrFail($user->id);
        $url = route('customer.avatar.show', ['v' => $first->version], false);
        $this->get('/profile')->assertOk()->assertInertia(fn (Assert $page) => $page->where('customerAvatar', $url))->assertDontSee(self::PNG);
        $this->get('/account')->assertOk()->assertInertia(fn (Assert $page) => $page->where('customerAvatar', $url));
        $image = $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
        self::assertStringContainsString('no-store', (string) $image->headers->get('Cache-Control'));
        self::assertSame(base64_decode(self::PNG, true), $image->getContent());

        $this->post(route('customer.avatar.store'), ['avatar' => $this->photo()])->assertRedirect('/profile')->assertSessionHasNoErrors();
        $second = CustomerAvatar::query()->findOrFail($user->id);
        self::assertNotSame($first->version, $second->version);
        $this->assertDatabaseCount('customer_avatars', 1);
        $this->delete(route('customer.avatar.destroy'))->assertRedirect('/profile');
        $this->assertDatabaseCount('customer_avatars', 0);
        $this->get('/profile')->assertOk()->assertInertia(fn (Assert $page) => $page->where('customerAvatar', null));
        $this->get($url)->assertNotFound();
    }

    public function test_guest_and_other_customer_cannot_read_or_modify_another_avatar(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->get(route('customer.avatar.show'))->assertRedirect(route('login'));
        $this->post(route('customer.avatar.store'), ['avatar' => $this->photo()])->assertRedirect(route('login'));
        $this->actingAs($owner)->post(route('customer.avatar.store'), ['avatar' => $this->photo()])->assertSessionHasNoErrors();
        $avatar = CustomerAvatar::query()->findOrFail($owner->id);
        $this->actingAs($other)->get(route('customer.avatar.show', ['v' => $avatar->version]))->assertNotFound();
        $this->delete(route('customer.avatar.destroy'), ['user_id' => $owner->id])->assertRedirect('/profile');
        $this->assertDatabaseHas('customer_avatars', ['user_id' => $owner->id, 'version' => $avatar->version]);
        $this->post(route('customer.avatar.store'), ['avatar' => $this->photo(), 'user_id' => $owner->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customer_avatars', ['user_id' => $owner->id, 'version' => $avatar->version]);
        $this->assertDatabaseHas('customer_avatars', ['user_id' => $other->id]);
    }

    public function test_invalid_uploads_do_not_replace_existing_photo(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('customer.avatar.store'), ['avatar' => $this->photo()])->assertSessionHasNoErrors();
        $avatar = CustomerAvatar::query()->findOrFail($user->id);
        foreach ([UploadedFile::fake()->createWithContent('fake.png', 'not an image'), UploadedFile::fake()->createWithContent('bad.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'), $this->photo()->size(1025)] as $invalid) {
            $this->from('/profile')->post(route('customer.avatar.store'), ['avatar' => $invalid])->assertSessionHasErrors('avatar');
            $this->assertDatabaseHas('customer_avatars', ['user_id' => $user->id, 'version' => $avatar->version]);
        }
    }

    public function test_suspended_customer_cannot_upload_or_view_avatar(): void
    {
        $user = User::factory()->create(['status' => CustomerStatus::from('suspended')]);
        $this->actingAs($user)->post(route('customer.avatar.store'), ['avatar' => $this->photo()])->assertRedirect(route('login'));
        $this->assertDatabaseCount('customer_avatars', 0);
        $this->actingAs($user)->get(route('customer.avatar.show'))->assertRedirect(route('login'));
    }

    public function test_self_deletion_and_admin_anonymization_remove_photo_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('customer.avatar.store'), ['avatar' => $this->photo()])->assertSessionHasNoErrors();
        $this->delete('/profile', ['password' => 'password'])->assertRedirect('/');
        $this->assertDatabaseMissing('customer_avatars', ['user_id' => $user->id]);

        $historical = User::factory()->create();
        $this->actingAs($historical)->post(route('customer.avatar.store'), ['avatar' => $this->photo()])->assertSessionHasNoErrors();
        Order::factory()->create(['user_id' => $historical->id]);
        $result = app(RemoveCustomerAction::class)->execute($historical, Admin::factory()->create(), 'Customer removal request.');
        self::assertSame(CustomerRemovalOutcome::Anonymized, $result);
        $this->assertDatabaseMissing('customer_avatars', ['user_id' => $historical->id]);
    }
}
