<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminPanel;
use App\Models\Admin\AdminUser;
use App\Models\Admin\AuthGroup;
use App\Models\Admin\AuthGroupAccess;
use App\Models\Admin\AuthRule;
use App\Models\Admin\CategoryPriv;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AdminAuthService
{
    private const SESSION_KEY = 'admin_user_id';

    public function user(): ?AdminUser
    {
        $id = Session::get(self::SESSION_KEY);

        if (! $id) {
            return null;
        }

        return AdminUser::query()->find($id);
    }

    public function check(): bool
    {
        return $this->user() instanceof AdminUser;
    }

    public function attempt(string $username, string $password, string $ip = ''): bool|int
    {
        $user = AdminUser::query()->where('username', $username)->first();

        if (! $user) {
            $this->increaseFailCount($username);
            return false;
        }

        if ($user->status === 'hidden') {
            return 0;
        }

        if ($this->hashPassword($password, (string) $user->salt) !== $user->password) {
            $this->increaseFailCount($username);
            return false;
        }

        Cache::forget($this->failCacheKey($username));

        $user->forceFill([
            'logintime' => time(),
            'loginip' => $ip,
        ])->save();

        Session::put(self::SESSION_KEY, $user->id);

        return true;
    }

    public function logout(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function hashPassword(string $password, string $salt = ''): string
    {
        return md5(sha1($password).md5($salt));
    }

    public function tooManyFailures(string $username, int $maxAttempts = 5): bool
    {
        return (int) Cache::get($this->failCacheKey($username), 0) > $maxAttempts;
    }

    public function hasPermission(string $rule): bool
    {
        $ruleIds = $this->ruleIds();

        if (in_array('*', $ruleIds, true)) {
            return true;
        }

        return AuthRule::query()
            ->whereIn('id', array_filter($ruleIds, 'is_numeric'))
            ->where('status', 'normal')
            ->where('name', strtolower($rule))
            ->exists();
    }

    public function hasSuperAdmin(): bool
    {
        return in_array('*', $this->ruleIds(), true);
    }

    public function menuTree(): array
    {
        $query = AuthRule::query()
            ->where('status', 'normal')
            ->whereIn('type', [1, 2])
            ->orderBy('weigh')
            ->orderBy('id');

        if (! $this->hasSuperAdmin()) {
            $allowed = array_filter($this->ruleIds(), 'is_numeric');
            $query->whereIn('id', $allowed ?: [0]);
        }

        $items = $query->get()->map(function (AuthRule $rule): array {
            $data = $rule->toArray();
            $data['url'] = $this->routeForRule($rule->name);
            return $data;
        });

        return $this->buildTree($items, 0);
    }

    public function quickPanels(): Collection
    {
        $user = $this->user();

        if (! $user) {
            return collect();
        }

        return DB::table('admin_panel as ap')
            ->join('auth_rule as ar', 'ap.auth_rule_id', '=', 'ar.id')
            ->where('ap.admin_id', $user->id)
            ->where('ar.status', 'normal')
            ->orderBy('ar.weigh')
            ->get(['ap.*', 'ar.name', 'ar.title', 'ar.route', 'ar.icon'])
            ->map(function ($item) {
                $item->url = $this->routeForRule($item->name);
                return $item;
            });
    }

    public function accessibleCategoryIds(): ?array
    {
        if ($this->hasSuperAdmin()) {
            return null;
        }

        $groupIds = $this->groupIds();

        if ($groupIds === []) {
            return [];
        }

        return CategoryPriv::query()
            ->whereIn('auth_group_id', $groupIds)
            ->pluck('category_id')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function groupNames(): string
    {
        $groupIds = $this->groupIds();

        if ($groupIds === []) {
            return '';
        }

        return AuthGroup::query()
            ->whereIn('id', $groupIds)
            ->pluck('name')
            ->implode(',');
    }

    public function currentGroupIds(): array
    {
        return $this->groupIds();
    }

    public function manageableGroupIds(bool $includeSelf = true): array
    {
        if ($this->hasSuperAdmin()) {
            $query = AuthGroup::query();
            if (! $includeSelf) {
                $query->where('parent_id', '<>', 0);
            }

            return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $all = AuthGroup::query()->get(['id', 'parent_id']);
        $current = $this->groupIds();
        $ids = $includeSelf ? $current : [];

        foreach ($current as $groupId) {
            $ids = array_merge($ids, $this->collectChildGroupIds($all, $groupId));
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public function manageableAdminIds(bool $includeSelf = true): array
    {
        if ($this->hasSuperAdmin()) {
            $query = AdminUser::query();
            if (! $includeSelf && $this->user()) {
                $query->where('id', '<>', $this->user()->id);
            }

            return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $groupIds = $this->manageableGroupIds();
        $ids = AuthGroupAccess::query()
            ->whereIn('group_id', $groupIds)
            ->pluck('admin_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (! $includeSelf && $this->user()) {
            $ids = array_values(array_diff($ids, [$this->user()->id]));
        }

        return $ids;
    }

    public function availableRuleIdsForGroup(?int $parentGroupId = null): array
    {
        if ($parentGroupId) {
            $group = AuthGroup::query()->find($parentGroupId);
            if (! $group) {
                return [];
            }

            $rules = array_filter(explode(',', trim((string) $group->rules, ',')));
            if (in_array('*', $rules, true)) {
                return AuthRule::query()
                    ->where('status', 'normal')
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }

            return array_map('intval', $rules);
        }

        if ($this->hasSuperAdmin()) {
            return AuthRule::query()
                ->where('status', 'normal')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return array_map('intval', array_filter($this->ruleIds(), 'is_numeric'));
    }

    public function ruleTree(array $selected = [], ?int $parentGroupId = null): array
    {
        $allowed = $this->availableRuleIdsForGroup($parentGroupId);

        return $this->buildSelectableTree(
            AuthRule::query()
                ->where('status', 'normal')
                ->whereIn('id', $allowed ?: [0])
                ->orderBy('weigh')
                ->orderBy('id')
                ->get(['id', 'parent_id', 'title', 'name'])
                ->map(fn ($item) => (array) $item)
                ->all(),
            $selected
        );
    }

    public function categoryTree(array $selected = []): array
    {
        $query = DB::table('category')
            ->where('status', 'normal')
            ->orderBy('weigh')
            ->orderBy('id');

        $allowedIds = $this->accessibleCategoryIds();
        if (is_array($allowedIds)) {
            $query->whereIn('id', $allowedIds ?: [0]);
        }

        return $this->buildSelectableTree(
            $query->get(['id', 'parent_id', 'title'])->map(fn ($item) => (array) $item)->all(),
            $selected
        );
    }

    private function groupIds(): array
    {
        $user = $this->user();

        if (! $user) {
            return [];
        }

        return AuthGroupAccess::query()
            ->where('admin_id', $user->id)
            ->pluck('group_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function ruleIds(): array
    {
        $groupIds = $this->groupIds();

        if ($groupIds === []) {
            return [];
        }

        $rules = AuthGroup::query()
            ->whereIn('id', $groupIds)
            ->where('status', 'normal')
            ->pluck('rules')
            ->flatMap(function (string $rules): array {
                return array_filter(explode(',', trim($rules, ',')));
            })
            ->unique()
            ->values()
            ->all();

        return $rules;
    }

    private function increaseFailCount(string $username): void
    {
        $key = $this->failCacheKey($username);
        $count = (int) Cache::get($key, 0);
        Cache::put($key, $count + 1, now()->addHour());
    }

    private function failCacheKey(string $username): string
    {
        return 'admin_login_fail_'.$username;
    }

    private function buildTree(Collection $items, int $parentId): array
    {
        return $items
            ->filter(fn (array $item) => (int) $item['parent_id'] === $parentId)
            ->map(function (array $item) use ($items): array {
                $item['children'] = $this->buildTree($items, (int) $item['id']);
                return $item;
            })
            ->values()
            ->all();
    }

    private function buildSelectableTree(array $items, array $selected, int $parentId = 0, int $level = 0): array
    {
        $rows = [];

        foreach ($items as $item) {
            if ((int) $item['parent_id'] !== $parentId) {
                continue;
            }

            $item['level'] = $level;
            $item['selected'] = in_array((int) $item['id'], array_map('intval', $selected), true);
            $rows[] = $item;
            $rows = array_merge($rows, $this->buildSelectableTree($items, $selected, (int) $item['id'], $level + 1));
        }

        return $rows;
    }

    private function collectChildGroupIds(Collection $allGroups, int $groupId): array
    {
        $ids = [];

        foreach ($allGroups->where('parent_id', $groupId) as $group) {
            $ids[] = (int) $group->id;
            $ids = array_merge($ids, $this->collectChildGroupIds($allGroups, (int) $group->id));
        }

        return $ids;
    }

    private function routeForRule(string $rule): string
    {
        return match (strtolower($rule)) {
            'index/dashboard' => route('admin.dashboard'),
            'cms/category',
            'cms/category/index' => route('admin.categories.index'),
            'cms/content/index' => route('admin.contents.index'),
            'cms/recommend/index' => route('admin.recommends.index'),
            'routine/attachment/index' => route('admin.attachments.index'),
            'routine/config',
            'routine/config/index' => route('admin.config.index'),
            'auth/admin/index' => route('admin.admin-users.index'),
            'auth/group/index' => route('admin.auth-groups.index'),
            default => '#',
        };
    }
}
