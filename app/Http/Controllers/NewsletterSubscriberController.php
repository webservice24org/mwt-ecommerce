<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Newsletter\StoreNewsletterSubscriptionRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;

final class NewsletterSubscriberController extends Controller
{
    public function store(
        StoreNewsletterSubscriptionRequest $request,
    ): RedirectResponse {
        $email = (string) $request->validated(
            'email',
        );

        $subscriber = NewsletterSubscriber::query()
            ->firstOrCreate(
                [
                    'email' => $email,
                ],
                [
                    'subscribed_at' => now(),
                    'unsubscribed_at' => null,
                ],
            );

        if (
            ! $subscriber->wasRecentlyCreated
            && $subscriber->unsubscribed_at !== null
        ) {
            $subscriber->forceFill([
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ])->save();
        }

        return back()->with(
            'newsletter_subscription_success',
            'Thanks! You are subscribed.',
        );
    }
}
