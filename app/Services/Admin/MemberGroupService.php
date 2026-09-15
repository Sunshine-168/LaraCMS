<?php

namespace App\Services\Admin;

use App\Models\Cms\MemberGroup;
use App\Support\Result;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MemberGroupService
{
    public function all(): Collection
    {
        return MemberGroup::query()->withCount('members')->orderBy('sort')->orderBy('id')->get();
    }

    public function save(?MemberGroup $group, array $input): array
    {
        $group ??= new MemberGroup;
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            return Result::fail('请填写分组名称');
        }
        $group->name = $name;

        $slugInput = trim((string) ($input['slug'] ?? ''));
        if ($slugInput !== '') {
            $slug = Str::slug($slugInput);
            $slug = $slug !== '' ? $slug : $slugInput;
        } elseif (! $group->exists || trim((string) $group->slug) === '') {
            $slug = Str::slug($name);
            if ($slug === '') {
                $slug = 'g'.substr(md5($name), 0, 8);
            }
        } else {
            $slug = $group->slug;
        }
        $group->slug = $this->uniqueSlug($slug, $group->exists ? (int) $group->id : null);

        if (array_key_exists('is_active', $input)) {
            $group->is_active = ! empty($input['is_active']);
        } elseif (! $group->exists) {
            $group->is_active = true;
        }

        if (array_key_exists('permissions', $input) || ! empty($input['from_form'])) {
            $group->permissions = array_values(array_intersect(
                MemberGroup::permissionKeys(),
                array_map('strval', (array) ($input['permissions'] ?? []))
            ));
        } elseif (! $group->exists) {
            $group->permissions = MemberGroup::permissionKeys();
        }

        if (! $group->exists) {
            $group->sort = (int) MemberGroup::query()->max('sort') + 10;
        }

        $creating = ! $group->exists;
        $group->save();

        return Result::ok($group, $creating ? '已添加' : '已保存');
    }

    public function toggle(MemberGroup $group): array
    {
        $group->is_active = ! $group->is_active;
        $group->save();

        return Result::ok(
            $group,
            $group->is_active ? '已启用，新会员可以选这组' : '已停用，这组权限暂时关掉'
        );
    }

    public function move(MemberGroup $group, string $dir): array
    {
        $rows = MemberGroup::query()->orderBy('sort')->orderBy('id')->get();
        $i = $rows->search(fn (MemberGroup $row) => (int) $row->id === (int) $group->id);
        if ($i === false) {
            return Result::fail('找不到这项');
        }
        $j = $dir === 'up' ? $i - 1 : $i + 1;
        if ($j < 0 || $j >= $rows->count()) {
            return Result::ok($group, '已经到头了');
        }
        $swap = $rows[$j];
        $a = (int) $group->sort;
        $b = (int) $swap->sort;
        if ($a === $b) {
            $group->sort = ($j + 1) * 10;
            $swap->sort = ($i + 1) * 10;
        } else {
            $group->sort = $b;
            $swap->sort = $a;
        }
        $group->save();
        $swap->save();

        return Result::ok($group, '顺序已调整');
    }

    public function delete(MemberGroup $group): array
    {
        $n = $group->members()->count();
        $group->members()->update(['group_id' => null]);
        $group->delete();

        return Result::ok(null, $n > 0 ? '已删除。'.$n.' 人改成未分组' : '已删除');
    }

    protected function uniqueSlug(string $base, ?int $ignoreId): string
    {
        $slug = $base;
        $i = 2;
        while (MemberGroup::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
