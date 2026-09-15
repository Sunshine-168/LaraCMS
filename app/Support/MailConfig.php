<?php

namespace App\Support;

use App\Mail\CmsMessage;
use App\Models\Cms\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

/** 将后台邮件设置应用到运行时 mailer */
class MailConfig
{
    public static function applyFromSettings(): void
    {
        $host = trim((string) Setting::getValue('mail_host', ''));
        if ($host === '') {
            return;
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp', array_merge(config('mail.mailers.smtp', []), [
            'transport' => 'smtp',
            'host' => $host,
            'port' => (int) Setting::getValue('mail_port', 587),
            'encryption' => Setting::getValue('mail_encryption', 'tls') ?: null,
            'username' => Setting::getValue('mail_username', ''),
            'password' => Setting::getValue('mail_password', ''),
            'timeout' => null,
        ]));

        $from = trim((string) Setting::getValue('mail_from_address', ''));
        if ($from !== '') {
            Config::set('mail.from.address', $from);
            Config::set('mail.from.name', Setting::getValue('mail_from_name', config('app.name')));
        }
    }

    public static function canSend(): bool
    {
        if (app()->runningUnitTests()) {
            return true;
        }
        self::applyFromSettings();
        $driver = (string) config('mail.default');
        if (in_array($driver, ['array', 'failover', 'roundrobin'], true)) {
            return true;
        }
        if ($driver === 'smtp') {
            return trim((string) config('mail.mailers.smtp.host')) !== '';
        }

        return ! in_array($driver, ['log', ''], true);
    }

    public static function showDebugLink(): bool
    {
        return (bool) config('app.debug') && ! app()->isProduction();
    }

    /** @param  array<string, mixed>  $data */
    public static function send(string $to, string $subject, string $view, array $data = []): bool
    {
        $to = trim($to);
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        try {
            self::applyFromSettings();
            if (! self::canSend()) {
                return false;
            }
            Mail::to($to)->send(new CmsMessage($subject, $view, array_merge(['subject' => $subject], $data)));

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
