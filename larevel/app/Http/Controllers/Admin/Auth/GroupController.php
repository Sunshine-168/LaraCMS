<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Admin\BaseAdminController;
use App\Models\Admin\AuthGroup;
use App\Models\Admin\CategoryPriv;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GroupController extends BaseAdminController
{
    public function index(): View
    {
        $rows = AuthGroup::query()
            ->whereIn('id', $this->auth->manageableGroupIds())
            ->orderBy('parent_id')
            ->orderBy('id')
            ->get()
            ->map(fn (AuthGroup $group) => $group->toArray());

        return $this->adminView('admin.auth.groups.index', [
            'pageTitle' => '角色组管理',
            'pageDescription' => '迁移版角色组管理，支持基础角色树、权限和栏目授权。',
            'rows' => $this->flattenTree($rows),
        ]);
    }

    public function create(): View
    {
        return $this->adminView('admin.auth.groups.form', [
            'pageTitle' => '新增角色组',
            'pageDescription' => '为下级管理员创建新的角色组。',
            'group' => null,
            'parentOptions' => $this->parentOptions(),
            'ruleRows' => $this->auth->ruleTree(),
            'categoryRows' => $this->auth->categoryTree(),
            'selectedRules' => [],
            'selectedCategories' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedGroup($request);

        DB::transaction(function () use ($data): void {
            $group = AuthGroup::query()->create([
                'name' => $data['name'],
                'parent_id' => $data['parent_id'],
                'rules' => implode(',', $data['rules']),
                'remark' => (string) ($data['remark'] ?? ''),
                'status' => $data['status'],
                'create_time' => time(),
                'update_time' => time(),
            ]);

            $this->syncCategoryPriv($group->id, $data['categories']);
        });

        return redirect()->route('admin.auth-groups.index')->with('status', '角色组已创建');
    }

    public function edit(AuthGroup $group): View
    {
        abort_unless(in_array((int) $group->id, $this->auth->manageableGroupIds(), true), 403);
        abort_if((string) $group->rules === '*', 422, '超级管理员角色不可编辑');

        return $this->adminView('admin.auth.groups.form', [
            'pageTitle' => '编辑角色组',
            'pageDescription' => '修改角色组基础信息、权限和栏目授权。',
            'group' => $group,
            'parentOptions' => $this->parentOptions($group->id),
            'ruleRows' => $this->auth->ruleTree(explode(',', (string) $group->rules), (int) $group->parent_id),
            'categoryRows' => $this->auth->categoryTree(CategoryPriv::query()->where('auth_group_id', $group->id)->pluck('category_id')->map(fn ($id) => (int) $id)->all()),
            'selectedRules' => array_map('intval', array_filter(explode(',', (string) $group->rules))),
            'selectedCategories' => CategoryPriv::query()->where('auth_group_id', $group->id)->pluck('category_id')->map(fn ($id) => (int) $id)->all(),
        ]);
    }

    public function update(Request $request, AuthGroup $group): RedirectResponse
    {
        abort_unless(in_array((int) $group->id, $this->auth->manageableGroupIds(), true), 403);
        abort_if((string) $group->rules === '*', 422, '超级管理员角色不可编辑');

        $data = $this->validatedGroup($request, $group);
        $childIds = array_diff($this->auth->manageableGroupIds(false), [$group->id]);
        abort_if(in_array((int) $data['parent_id'], $childIds, true) && (int) $data['parent_id'] === (int) $group->id, 422, '父级角色不合法');

        DB::transaction(function () use ($group, $data): void {
            $group->update([
                'name' => $data['name'],
                'parent_id' => $data['parent_id'],
                'rules' => implode(',', $data['rules']),
                'remark' => (string) ($data['remark'] ?? ''),
                'status' => $data['status'],
                'update_time' => time(),
            ]);

            $this->syncCategoryPriv($group->id, $data['categories']);
        });

        return redirect()->route('admin.auth-groups.index')->with('status', '角色组已更新');
    }

    public function destroy(AuthGroup $group): RedirectResponse
    {
        abort_unless(in_array((int) $group->id, $this->auth->manageableGroupIds(false), true), 403);
        abort_if((string) $group->rules === '*', 422, '超级管理员角色不可删除');

        DB::transaction(function () use ($group): void {
            CategoryPriv::query()->where('auth_group_id', $group->id)->delete();
            DB::table('auth_group_access')->where('group_id', $group->id)->delete();
            $group->delete();
        });

        return redirect()->route('admin.auth-groups.index')->with('status', '角色组已删除');
    }

    private function validatedGroup(Request $request, ?AuthGroup $group = null): array
    {
        $data = $request->validate([
            'parent_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:20', Rule::unique('auth_group', 'name')->ignore($group?->id)],
            'remark' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:normal,hidden'],
            'rules' => ['required', 'array', 'min:1'],
            'rules.*' => ['integer'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer'],
        ]);

        $parentId = (int) $data['parent_id'];
        $allowedParents = $this->parentOptions($group?->id);
        if (! in_array($parentId, array_column($allowedParents, 'id'), true)) {
            abort(403, '父级角色不在可管理范围内');
        }

        $allowedRules = $this->auth->availableRuleIdsForGroup($parentId);
        $data['rules'] = array_values(array_intersect($allowedRules, array_map('intval', $data['rules'])));
        if ($data['rules'] === []) {
            abort(422, '权限规则不能为空');
        }

        $allowedCategories = $this->auth->accessibleCategoryIds();
        $categories = array_map('intval', $data['categories'] ?? []);
        $data['categories'] = is_array($allowedCategories)
            ? array_values(array_intersect($allowedCategories, $categories))
            : $categories;

        return $data;
    }

    private function parentOptions(?int $excludeId = null): array
    {
        $rows = AuthGroup::query()
            ->whereIn('id', $this->auth->manageableGroupIds())
            ->orderBy('parent_id')
            ->orderBy('id')
            ->get()
            ->reject(fn (AuthGroup $group) => $excludeId !== null && (int) $group->id === $excludeId)
            ->map(fn (AuthGroup $group) => $group->toArray());

        return $this->flattenTree($rows);
    }

    private function flattenTree($rows, int $parentId = 0, int $level = 0): array
    {
        $output = [];

        foreach ($rows as $row) {
            if ((int) $row['parent_id'] !== $parentId) {
                continue;
            }
            $row['level'] = $level;
            $output[] = $row;
            $output = array_merge($output, $this->flattenTree($rows, (int) $row['id'], $level + 1));
        }

        return $output;
    }

    private function syncCategoryPriv(int $groupId, array $categoryIds): void
    {
        CategoryPriv::query()->where('auth_group_id', $groupId)->delete();

        foreach ($categoryIds as $categoryId) {
            CategoryPriv::query()->create([
                'auth_group_id' => $groupId,
                'category_id' => (int) $categoryId,
            ]);
        }
    }
}
