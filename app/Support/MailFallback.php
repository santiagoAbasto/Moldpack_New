<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Keeps public forms working when SMTP is not configured: without a host the
 * app writes outgoing mail to the log instead of failing to connect.
 */
final class MailFallback
{
    public static function apply(): void
    {
        if (config('mail.default') !== 'smtp' || filled(config('mail.mailers.smtp.host'))) {
            return;
        }

        config(['mail.default' => 'log']);

        if (! app()->runningUnitTests()) {
            Log::warning('MAIL_HOST no está configurado: los correos se registran en el log en lugar de enviarse.');
        }
    }
}
