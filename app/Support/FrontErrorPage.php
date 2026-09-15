<?php

namespace App\Support;

use App\Cms\CmsViewContext;
use App\Services\Cms\SiteFrontService;
use Symfony\Component\HttpFoundation\Response;

class FrontErrorPage
{
    public static function response(int $status): Response
    {
        try {
            app(SiteFrontService::class)->bootSite();
        } catch (\Throwable) {
        }
        try {
            $ctx = app(CmsViewContext::class);
            $site = $ctx->site();
            $name = (string) ($site['title'] ?? config('app.name'));
            $titleKey = match ($status) {
                403 => 'forbidden',
                419 => 'session_expired',
                default => 'not_found',
            };
            $ctx->setSeo(cms_ui($titleKey).' - '.$name, '', '')->setNoIndex(true);
        } catch (\Throwable) {
        }

        $view = view()->exists('errors.'.$status) ? 'errors.'.$status : 'errors.404';

        return response()->view($view, [
            'layout' => self::layout(),
        ], $status);
    }

    public static function layout(): string
    {
        $theme = (string) config('cms.theme', 'enterprise');
        if (view()->exists("themes.{$theme}.partials.layout")) {
            return "themes.{$theme}.partials.layout";
        }
        if (view()->exists("themes.{$theme}.layout")) {
            return "themes.{$theme}.layout";
        }

        return 'errors.bare';
    }
}
