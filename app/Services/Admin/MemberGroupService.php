<?php

namespace App\Services\Admin;

use App\Models\Cms\MemberGroup;
use App\Support\Result;
use Illuminate\Support\Str;

class MemberGroupService
{
    public function all()
    {
        return MemberGroup::query()->orderBy('sort')->orderBy('id')->get();
    }

    public function save(?MemberGroup $group, array $input): array
    {
        $group ??= new MemberGroup;
        $group->name = $input['name'] ?? $group->name;
        $slug = trim((string) ($input['slug'] ?? ''));
        $group->slug = $slug !== '' ? Str::slug($slug) : Str::slug($group->name);
        $group->sort = (int) ($input['sort'] ?? 0);
        $group->is_active = ! empty($input['is_active']);
        $perms = array_values(array_intersect(
            MemberGroup::permissionKeys(),
            array_map('strval', (array) ($input['permissions'] ?? []))
        ));
        $group->permissions = $perms;
        $group->save();

        return Result::ok($group, '分组已保存');
    }

    public function delete(MemberGroup $group): array
    {
        $group->members()->update(['group_id' => null]);
        $group->delete();

        return Result::ok(null, '已删除');
    }
}
