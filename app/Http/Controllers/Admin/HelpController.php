<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CurrentSite;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpController extends Controller
{
    public function index(Request $request): View
    {
        $topic = (string) $request->query('topic', 'use');
        if (! in_array($topic, ['use', 'admin', 'themes', 'theme', 'tags', 'env', 'schedule', 'laravel', 'docker'], true)) {
            $topic = 'use';
        }

        return view('admin.help.index', [
            'topic' => $topic,
            'siteId' => CurrentSite::idOrDefault() ?? 1,
            'urls' => $this->urls(),
        ]);
    }

    /** @return array<string, string|null> */
    private function urls(): array
    {
        $admin = auth('admin')->user();
        $can = fn (string $key): bool => (bool) $admin?->canPerm($key);
        $to = fn (string $perm, string $name, array $params = []): ?string => $can($perm)
            ? route($name, $params)
            : null;

        return [
            'dashboard' => $to('dashboard', 'admin.dashboard'),
            'stats' => $to('stats', 'admin.stats.index'),
            'settings' => $to('settings', 'admin.settings.edit'),
            'settingsMore' => $to('settings', 'admin.settings.edit', ['tab' => 'more']),
            'settingsLook' => $to('settings', 'admin.settings.edit', ['tab' => 'look']),
            'themes' => $to('settings', 'admin.themes.index'),
            'categories' => $to('categories', 'admin.categories.index'),
            'entries' => $to('entries', 'admin.entries.index'),
            'entryTags' => $to('tags', 'admin.tags.index'),
            'media' => $to('media', 'admin.media.index'),
            'submissions' => $to('form_submissions', 'admin.form-submissions.index'),
            'comments' => $to('comments', 'admin.comments.index'),
            'recycle' => $to('recycle', 'admin.recycle.index'),
            'friendLinks' => $to('friend_links', 'admin.friend-links.index'),
            'sitemap' => $to('sitemap', 'admin.sitemap.edit'),
            'flags' => $to('flags', 'admin.flags.index'),
            'models' => $to('models', 'admin.models.index'),
            'menus' => $to('menus', 'admin.menus.index'),
            'recommends' => $to('recommends', 'admin.recommends.index'),
            'forms' => $to('forms', 'admin.forms.index'),
            'redirects' => $to('redirects', 'admin.redirects.index'),
            'collectors' => $to('collectors', 'admin.collectors.index'),
            'cache' => $to('html_cache', 'admin.html-cache.edit'),
            'sites' => $to('sites', 'admin.sites.index'),
            'members' => $to('members', 'admin.members.index'),
            'groups' => $to('member_groups', 'admin.member-groups.index'),
            'locales' => $to('locales', 'admin.locales.index'),
            'logs' => $to('logs', 'admin.logs.index'),
            'adminUsers' => $to('admin_users', 'admin.admin-users.index'),
            'roles' => $to('roles', 'admin.roles.index'),
            'profile' => route('admin.profile.edit'),
            'front' => url('/'),
            'helpAdmin' => route('admin.help', ['topic' => 'admin']),
            'helpThemes' => route('admin.help', ['topic' => 'themes']),
            'theme' => route('admin.help', ['topic' => 'theme']),
            'tags' => route('admin.help', ['topic' => 'tags']),
            'env' => route('admin.help', ['topic' => 'env']),
            'schedule' => route('admin.help', ['topic' => 'schedule']),
        ];
    }
}
