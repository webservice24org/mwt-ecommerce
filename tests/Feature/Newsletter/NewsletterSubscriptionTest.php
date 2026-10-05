<?php

declare(strict_types=1);

namespace Tests\Feature\Newsletter;

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

final class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Behaviour tests should not share the public
         * rate-limit counter.
         *
         * We separately verify below that the route
         * actually contains the throttle middleware.
         */
        $this->withoutMiddleware(
            ThrottleRequests::class,
        );
    }

    public function test_guest_can_subscribe_to_newsletter(): void
    {
        $response = $this
            ->from('/')
            ->post(
                route('newsletter.subscribe'),
                [
                    'email' => 'hello@example.com',
                ],
            );

        $response
            ->assertRedirect('/')
            ->assertSessionHas(
                'newsletter_subscription_success',
            );

        $this->assertDatabaseHas(
            'newsletter_subscribers',
            [
                'email' => 'hello@example.com',
                'unsubscribed_at' => null,
            ],
        );

        $subscriber =
            NewsletterSubscriber::query()
                ->where(
                    'email',
                    'hello@example.com',
                )
                ->firstOrFail();

        $this->assertNotNull(
            $subscriber->subscribed_at,
        );

        $this->assertTrue(
            $subscriber->isSubscribed(),
        );
    }

    public function test_email_is_trimmed_and_normalized_to_lowercase(): void
    {
        $this
            ->from('/')
            ->post(
                route('newsletter.subscribe'),
                [
                    'email' => '  Customer@Example.COM  ',
                ],
            )
            ->assertRedirect('/');

        $this->assertDatabaseHas(
            'newsletter_subscribers',
            [
                'email' => 'customer@example.com',
            ],
        );

        $this->assertDatabaseMissing(
            'newsletter_subscribers',
            [
                'email' => 'Customer@Example.COM',
            ],
        );
    }

    public function test_invalid_email_is_rejected(): void
    {
        $response = $this
            ->from('/')
            ->post(
                route('newsletter.subscribe'),
                [
                    'email' => 'not-an-email',
                ],
            );

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'email',
            ]);

        $this->assertDatabaseCount(
            'newsletter_subscribers',
            0,
        );
    }

    public function test_email_is_required(): void
    {
        $response = $this
            ->from('/')
            ->post(
                route('newsletter.subscribe'),
                [],
            );

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'email',
            ]);

        $this->assertDatabaseCount(
            'newsletter_subscribers',
            0,
        );
    }

    public function test_existing_active_subscription_is_idempotent(): void
    {
        NewsletterSubscriber::query()
            ->create([
                'email' => 'existing@example.com',

                'subscribed_at' => now()->subDays(5),

                'unsubscribed_at' => null,
            ]);

        $originalSubscribedAt =
            NewsletterSubscriber::query()
                ->where(
                    'email',
                    'existing@example.com',
                )
                ->firstOrFail()
                ->subscribed_at;

        $this
            ->from('/')
            ->post(
                route('newsletter.subscribe'),
                [
                    'email' => 'existing@example.com',
                ],
            )
            ->assertRedirect('/')
            ->assertSessionHas(
                'newsletter_subscription_success',
            );

        $this->assertDatabaseCount(
            'newsletter_subscribers',
            1,
        );

        $subscriber =
            NewsletterSubscriber::query()
                ->where(
                    'email',
                    'existing@example.com',
                )
                ->firstOrFail();

        $this->assertTrue(
            $subscriber->isSubscribed(),
        );

        $this->assertTrue(
            $subscriber->subscribed_at?->equalTo(
                $originalSubscribedAt,
            ) ?? false,
        );
    }

    public function test_unsubscribed_email_is_reactivated(): void
    {
        NewsletterSubscriber::query()
            ->create([
                'email' => 'returning@example.com',

                'subscribed_at' => now()->subMonth(),

                'unsubscribed_at' => now()->subDay(),
            ]);

        $this
            ->from('/')
            ->post(
                route('newsletter.subscribe'),
                [
                    'email' => 'returning@example.com',
                ],
            )
            ->assertRedirect('/')
            ->assertSessionHas(
                'newsletter_subscription_success',
            );

        $this->assertDatabaseCount(
            'newsletter_subscribers',
            1,
        );

        $subscriber =
            NewsletterSubscriber::query()
                ->where(
                    'email',
                    'returning@example.com',
                )
                ->firstOrFail();

        $this->assertNull(
            $subscriber->unsubscribed_at,
        );

        $this->assertNotNull(
            $subscriber->subscribed_at,
        );

        $this->assertTrue(
            $subscriber->isSubscribed(),
        );
    }

    public function test_newsletter_route_is_rate_limited(): void
    {
        $route = app('router')
            ->getRoutes()
            ->getByName(
                'newsletter.subscribe',
            );

        $this->assertNotNull(
            $route,
        );

        $this->assertContains(
            'throttle:5,1',
            $route->gatherMiddleware(),
        );
    }
}
