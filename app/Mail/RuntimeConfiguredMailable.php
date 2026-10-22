<?php

declare(strict_types=1);

namespace App\Mail;

use App\Domain\Mail\Actions\ConfigureRuntimeMailAction;
use Illuminate\Mail\Mailable;

abstract class RuntimeConfiguredMailable extends Mailable
{
    public function send($mailer)
    {
        $settings = app(
            ConfigureRuntimeMailAction::class,
        )->execute();

        if (! $settings->enabled) {
            return null;
        }

        return parent::send(
            $mailer,
        );
    }
}
