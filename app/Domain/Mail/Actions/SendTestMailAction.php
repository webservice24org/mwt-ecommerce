<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Mail\TestMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class SendTestMailAction
{
    public function __construct(
        private ConfigureRuntimeMailAction $configureRuntimeMail,
    ) {}

    public function execute(
        string $recipient,
    ): void {
        $settings =
            $this
                ->configureRuntimeMail
                ->execute();

        if (! $settings->enabled) {
            throw ValidationException::withMessages([
                'mail' => 'Mail delivery is currently disabled.',
            ]);
        }

        if (
            blank($settings->host)
            || blank($settings->port)
            || blank($settings->fromAddress)
        ) {
            throw ValidationException::withMessages([
                'mail' => 'Complete the SMTP host, port, and sender address before sending a test email.',
            ]);
        }

        try {
            Mail::mailer(
                $settings->mailer,
            )
                ->to(
                    $recipient,
                )
                ->send(
                    new TestMail,
                );
        } catch (Throwable $exception) {
            report(
                $exception,
            );

            throw ValidationException::withMessages([
                'mail' => 'The test email could not be sent. Check the SMTP settings and application logs.',
            ]);
        }
    }
}
