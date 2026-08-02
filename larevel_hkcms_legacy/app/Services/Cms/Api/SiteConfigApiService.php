<?php

namespace App\Services\Cms\Api;

use App\Services\Cms\SiteService;

class SiteConfigApiService
{
    public function __construct(private readonly SiteService $siteService)
    {
    }

    public function getAll(string $lang): array
    {
        $site = $this->siteService->all($lang);

        unset(
            $site['mail_server'],
            $site['mail_port'],
            $site['mail_from'],
            $site['mail_fname'],
            $site['mail_auth'],
            $site['mail_user'],
            $site['mail_password'],
            $site['url_rewrite'],
            $site['login_fail_count']
        );

        return $site;
    }
}
