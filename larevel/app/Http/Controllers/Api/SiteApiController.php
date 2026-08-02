<?php

namespace App\Http\Controllers\Api;
use App\Models\Cms\Setting;
use App\Services\Cms\Tags\AdvTag;
use App\Support\Locale;
use App\Support\UrlMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteApiController extends ApiController
{
    public function config(): JsonResponse
    {
        return $this->ok([
            'site_name' => Setting::getValue('site_name', config('app.name')),
            'site_description' => Setting::getValue('site_description', ''),
            'site_keywords' => Setting::getValue('site_keywords', ''),
            'theme' => Setting::getValue('theme', config('cms.theme')),
            'url_mode' => UrlMode::current(),
            'locales' => Locale::all(),
            'default_locale' => Locale::default(),
            'api_enable' => (bool) (int) Setting::getValue('api_enable', '0'),
        ]);
    }

    public function recommend(Request $request, AdvTag $adv): JsonResponse
    {
        $name = (string) $request->query('name', '');
        if ($name === '') {
            return $this->fail('缺少 name');
        }
        $items = $adv->get([
            'name' => $name,
            'num' => $request->query('num'),
        ])->map(fn ($item) => [
            'title' => method_exists($item, 't') ? $item->t('title') : ($item->title ?? ''),
            'notes' => method_exists($item, 't') ? $item->t('notes') : ($item->notes ?? ''),
            'image' => $item->image ?? null,
            'url' => $item->url ?? null,
            'entry_id' => $item->entry_id ?? null,
            'html' => $item->html ?? null,
        ])->values();

        return $this->ok($items);
    }
}
