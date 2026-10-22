<?php

declare(strict_types=1);

namespace App\Mail\Orders;

use App\Domain\Mail\Data\OrderMailData;
use App\Mail\RuntimeConfiguredMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class NewOrderAdminMail extends RuntimeConfiguredMailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly OrderMailData $order,
        public readonly string $adminOrderUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                'New order %s',
                $this->order->orderNumber,
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.orders.admin-new-order',
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function attachments(): array
    {
        return [];
    }
}
