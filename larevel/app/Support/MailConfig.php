<?php

namespace App\Support;

use App\Models\Cms\Setting;
use Illuminate\Support\Facades\Config;

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
}
