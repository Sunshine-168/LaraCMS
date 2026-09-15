<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SettingService;
use App\Services\Admin\SiteBackupService;
use App\Support\ThemeAppearance;
use App\Support\UrlMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settingService,
        private readonly SiteBackupService $backups,
    ) {}

    /** 站点设置页 */
    public function edit(Request $request): View
    {
        $keys = [
            'site_name', 'site_description', 'site_keywords', 'theme',
            'upload_thumb_width', 'upload_watermark_text', 'upload_watermark_position', 'upload_watermark_image',
            'site_closed', 'site_closed_message',
            'analytics_code', 'tag_autolink',
            'mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_password',
            'mail_from_address', 'mail_from_name',
            'url_mode', 'mobile_theme', 'mobile_domain', 'mobile_detect',
            'api_enable', 'api_token',
            'comments_enabled', 'comments_guest', 'comments_auto_approve',
            'comments_notify_email', 'comments_rate_seconds',
            'map_default_lng', 'map_default_lat',
            'cdn_url',
            'robots_extra',
            'logo', 'favicon', 'primary_color', 'footer_text', 'icp', 'hero_title', 'hero_subtitle',
        ];

        return view('admin.settings.edit', [
            'settings' => $this->settingService->getMany($keys),
            'tab' => $this->tabFrom($request),
            'backups' => $this->backups->list(),
        ]);
    }

    /** 保存站点设置 */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->only([
            'site_name', 'site_description', 'site_keywords', 'theme',
            'upload_watermark_text', 'upload_watermark_position', 'site_closed_message', 'analytics_code',
            'mail_host', 'mail_encryption', 'mail_username', 'mail_from_address', 'mail_from_name',
            'mobile_theme', 'mobile_domain', 'mobile_detect', 'api_token',
            'comments_notify_email', 'map_default_lng', 'map_default_lat',
            'cdn_url', 'robots_extra',
            'footer_text', 'icp', 'hero_title', 'hero_subtitle', 'primary_color',
        ]);
        $data['upload_thumb_width'] = (string) max(0, (int) $request->input('upload_thumb_width', 0));
        $data['map_default_lng'] = (string) $request->input('map_default_lng', '116.397');
        $data['map_default_lat'] = (string) $request->input('map_default_lat', '39.908');
        $data['site_closed'] = $request->boolean('site_closed') ? '1' : '0';
        $data['tag_autolink'] = $request->boolean('tag_autolink') ? '1' : '0';
        $data['api_enable'] = $request->boolean('api_enable') ? '1' : '0';
        $data['comments_enabled'] = $request->boolean('comments_enabled') ? '1' : '0';
        $data['comments_guest'] = $request->boolean('comments_guest') ? '1' : '0';
        $data['comments_auto_approve'] = $request->boolean('comments_auto_approve') ? '1' : '0';
        $data['comments_rate_seconds'] = (string) max(10, min(3600, (int) $request->input('comments_rate_seconds', 60)));
        $data['mail_port'] = (string) max(1, (int) $request->input('mail_port', 587));
        $mode = (string) $request->input('url_mode', UrlMode::CAT_ENTRY);
        $data['url_mode'] = array_key_exists($mode, UrlMode::options()) ? $mode : UrlMode::CAT_ENTRY;
        $detect = (string) $request->input('mobile_detect', 'both');
        $data['mobile_detect'] = in_array($detect, ['both', 'domain', 'ua'], true) ? $detect : 'both';
        if ($request->filled('mail_password')) {
            $data['mail_password'] = (string) $request->input('mail_password');
        }
        $pos = strtolower((string) $request->input('upload_watermark_position', 'br'));
        $data['upload_watermark_position'] = in_array($pos, ['br', 'bl', 'tr', 'tl', 'center'], true) ? $pos : 'br';
        if ($request->boolean('upload_watermark_image_clear')) {
            $data['upload_watermark_image'] = '';
        }
        if ($request->hasFile('watermark_image')) {
            $request->validate(['watermark_image' => 'image|max:2048']);
            $data['upload_watermark_image'] = $request->file('watermark_image')->store('cms/watermark', 'public');
        }
        $cdn = trim((string) ($data['cdn_url'] ?? ''));
        if ($cdn !== '' && ! preg_match('#^https?://#i', $cdn)) {
            $cdn = '';
        }
        $data['cdn_url'] = rtrim($cdn, '/');
        $data['robots_extra'] = $this->sanitizeRobotsExtra((string) ($data['robots_extra'] ?? ''));
        $data['primary_color'] = ThemeAppearance::sanitizeColor((string) ($data['primary_color'] ?? ''));
        $data['icp'] = mb_substr(trim((string) ($data['icp'] ?? '')), 0, 80);
        $data['footer_text'] = mb_substr(trim((string) ($data['footer_text'] ?? '')), 0, 500);
        $data['hero_title'] = mb_substr(trim((string) ($data['hero_title'] ?? '')), 0, 120);
        $data['hero_subtitle'] = mb_substr(trim((string) ($data['hero_subtitle'] ?? '')), 0, 200);
        if ($request->boolean('logo_clear')) {
            $data['logo'] = '';
        }
        if ($request->boolean('favicon_clear')) {
            $data['favicon'] = '';
        }
        if ($request->hasFile('logo_file')) {
            $request->validate(['logo_file' => 'image|max:2048']);
            $data['logo'] = $request->file('logo_file')->store('cms/brand', 'public');
        }
        if ($request->hasFile('favicon_file')) {
            $request->validate(['favicon_file' => 'image|max:512']);
            $data['favicon'] = $request->file('favicon_file')->store('cms/brand', 'public');
        }

        $result = $this->settingService->save($data);
        $tab = $this->tabFrom($request);

        return redirect()->route('admin.settings.edit', $tab === 'site' ? [] : ['tab' => $tab])
            ->with('status', $result['msg']);
    }

    private function tabFrom(Request $request): string
    {
        $tab = (string) $request->input('tab', $request->query('tab', 'site'));

        return in_array($tab, ['site', 'look', 'interact', 'more'], true) ? $tab : 'site';
    }

    protected function sanitizeRobotsExtra(string $raw): string
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $lines = [];
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (str_contains($line, '<') || str_contains($line, '>')) {
                continue;
            }
            $lines[] = mb_substr($line, 0, 200);
            if (count($lines) >= 40) {
                break;
            }
        }

        return implode("\n", $lines);
    }
}
