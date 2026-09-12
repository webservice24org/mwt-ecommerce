<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_password_reset_link_can_be_requested(): void
    {
        Notification::fake();

        $admin = Admin::factory()->create();

        $response = $this->post(
            route('admin.password.email'),
            [
                'email' => $admin->email,
            ],
        );

        $response->assertSessionHas('status');

        Notification::assertSentTo(
            $admin,
            ResetPassword::class,
        );
    }

    public function test_unknown_admin_email_does_not_reveal_account_existence(): void
    {
        Notification::fake();

        $response = $this->post(
            route('admin.password.email'),
            [
                'email' => 'unknown@example.com',
            ],
        );

        $response->assertSessionHas('status');

        $response->assertSessionHasNoErrors();
    }
}
