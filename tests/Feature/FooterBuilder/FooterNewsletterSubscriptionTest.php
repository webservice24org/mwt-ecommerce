<?php

declare(strict_types=1);

namespace Tests\Feature\FooterBuilder;

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FooterNewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_email_creates_subscription(): void
    {
        $response =
            $this->post(
                route(
                    'newsletter.subscribe',
                ),
                [
                    'email' => 'customer@example.com',
                ],
            );

        $response
            ->assertRedirect()
            ->assertSessionHas(
                'newsletter_subscription_success',
                'Thanks! You are subscribed.',
            );

        $this->assertDatabaseHas(
            'newsletter_subscribers',
            [
                'email' => 'customer@example.com',

                'unsubscribed_at' => null,
            ],
        );

        $subscriber =
            NewsletterSubscriber::query()
                ->where(
                    'email',
                    'customer@example.com',
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
            ->post(
                route(
                    'newsletter.subscribe',
                ),
                [
                    'email' => '  Customer@Example.COM  ',
                ],
            )
            ->assertRedirect();

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

    public function test_email_is_required(): void
    {
        $this
            ->from('/')
            ->post(
                route(
                    'newsletter.subscribe',
                ),
                [
                    'email' => '',
                ],
            )
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'email',
            ]);

        $this->assertDatabaseCount(
            'newsletter_subscribers',
            0,
        );
    }

    public function test_invalid_email_is_rejected(): void
    {
        $this
            ->from('/')
            ->post(
                route(
                    'newsletter.subscribe',
                ),
                [
                    'email' => 'not-an-email',
                ],
            )
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'email',
            ]);

        $this->assertDatabaseCount(
            'newsletter_subscribers',
            0,
        );
    }

    public function test_email_longer_than_254_characters_is_rejected(): void
    {
        $email =
            str_repeat(
                'a',
                245,
            ).
            '@example.com';

        $this->assertGreaterThan(
            254,
            strlen(
                $email,
            ),
        );

        $this
            ->from('/')
            ->post(
                route(
                    'newsletter.subscribe',
                ),
                [
                    'email' => $email,
                ],
            )
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'email',
            ]);

        $this->assertDatabaseCount(
            'newsletter_subscribers',
            0,
        );
    }

    public function test_submitting_existing_active_email_does_not_create_duplicate(): void
    {
        NewsletterSubscriber::query()
            ->create([
                'email' => 'existing@example.com',

                'subscribed_at' => now()->subDay(),

                'unsubscribed_at' => null,
            ]);

        $this
            ->post(
                route(
                    'newsletter.subscribe',
                ),
                [
                    'email' => 'EXISTING@example.com',
                ],
            )
            ->assertRedirect()
            ->assertSessionHas(
                'newsletter_subscription_success',
                'Thanks! You are subscribed.',
            );

        $this->assertSame(
            1,
            NewsletterSubscriber::query()
                ->where(
                    'email',
                    'existing@example.com',
                )
                ->count(),
        );
    }

    public function test_unsubscribed_email_is_reactivated(): void
    {
        $subscriber =
            NewsletterSubscriber::query()
                ->create([
                    'email' => 'returning@example.com',

                    'subscribed_at' => now()->subMonth(),

                    'unsubscribed_at' => now()->subDay(),
                ]);

        $this
            ->post(
                route(
                    'newsletter.subscribe',
                ),
                [
                    'email' => 'returning@example.com',
                ],
            )
            ->assertRedirect()
            ->assertSessionHas(
                'newsletter_subscription_success',
                'Thanks! You are subscribed.',
            );

        $subscriber->refresh();

        $this->assertNull(
            $subscriber->unsubscribed_at,
        );

        $this->assertNotNull(
            $subscriber->subscribed_at,
        );

        $this->assertTrue(
            $subscriber->isSubscribed(),
        );

        $this->assertFalse(
            $subscriber->isUnsubscribed(),
        );
    }

    public function test_new_and_existing_addresses_receive_same_public_success_message(): void
    {
        NewsletterSubscriber::query()
            ->create([
                'email' => 'existing@example.com',

                'subscribed_at' => now(),

                'unsubscribed_at' => null,
            ]);

        $newResponse =
            $this->post(
                route(
                    'newsletter.subscribe',
                ),
                [
                    'email' => 'new@example.com',
                ],
            );

        $existingResponse =
            $this->post(
                route(
                    'newsletter.subscribe',
                ),
                [
                    'email' => 'existing@example.com',
                ],
            );

        $newResponse->assertSessionHas(
            'newsletter_subscription_success',
            'Thanks! You are subscribed.',
        );

        $existingResponse->assertSessionHas(
            'newsletter_subscription_success',
            'Thanks! You are subscribed.',
        );
    }

    public function test_newsletter_endpoint_is_rate_limited(): void
    {
        for (
            $attempt = 1;
            $attempt <= 5;
            $attempt++
        ) {
            $this
                ->post(
                    route(
                        'newsletter.subscribe',
                    ),
                    [
                        'email' => "subscriber{$attempt}@example.com",
                    ],
                )
                ->assertRedirect();
        }

        $this
            ->post(
                route(
                    'newsletter.subscribe',
                ),
                [
                    'email' => 'blocked@example.com',
                ],
            )
            ->assertTooManyRequests();

        $this->assertDatabaseMissing(
            'newsletter_subscribers',
            [
                'email' => 'blocked@example.com',
            ],
        );
    }
}
