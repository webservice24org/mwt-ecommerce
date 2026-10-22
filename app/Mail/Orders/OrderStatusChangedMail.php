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

final class OrderStatusChangedMail extends RuntimeConfiguredMailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly OrderMailData $order,
        public readonly string $headline,
        public readonly string $statusMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                '%s — %s',
                $this->headline,
                $this->order->orderNumber,
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.orders.status-changed',
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
