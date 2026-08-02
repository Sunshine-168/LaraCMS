<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SettingService;
use App\Support\UrlMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(private readonly SettingService $settingService)
    {
    }

    /** 站点设置页 */
    public function edit(): View
    {
        $keys = [
            'site_name', 'site_description', 'site_keywords', 'theme',
            'upload_thumb_width', 'upload_watermark_text',
            'site_closed', 'site_closed_message',
            'analytics_code', 'tag_autolink',
            'mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_password',
            'mail_from_address', 'mail_from_name',
            'url_mode', 'mobile_theme', 'mobile_domain', 'mobile_detect',
            'api_enable', 'api_token',
        ];

        return view('admin.settings.edit', [
            'settings' => $this->settingService->getMany($keys),
        ]);
    }

    /** 保存站点设置 */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->only([
            'site_name', 'site_description', 'site_keywords', 'theme',
            'upload_watermark_text', 'site_closed_message', 'analytics_code',
            'mail_host', 'mail_encryption', 'mail_username', 'mail_from_address', 'mail_from_name',
            'mobile_theme', 'mobile_domain', 'mobile_detect', 'api_token',
        ]);
        $data['upload_thumb_width'] = (string) max(0, (int) $request->input('upload_thumb_width', 0));
        $data['site_closed'] = $request->boolean('site_closed') ? '1' : '0';
        $data['tag_autolink'] = $request->boolean('tag_autolink') ? '1' : '0';
        $data['api_enable'] = $request->boolean('api_enable') ? '1' : '0';
        $data['mail_port'] = (string) max(1, (int) $request->input('mail_port', 587));
        $mode = (string) $request->input('url_mode', UrlMode::CAT_ENTRY);
        $data['url_mode'] = array_key_exists($mode, UrlMode::options()) ? $mode : UrlMode::CAT_ENTRY;
        $detect = (string) $request->input('mobile_detect', 'both');
        $data['mobile_detect'] = in_array($detect, ['both', 'domain', 'ua'], true) ? $detect : 'both';
        if ($request->filled('mail_password')) {
            $data['mail_password'] = (string) $request->input('mail_password');
        }

        $result = $this->settingService->save($data);

        return redirect()->route('admin.settings.edit')->with('status', $result['msg']);
    }
}
