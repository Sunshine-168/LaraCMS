<?php

namespace App\Services\Admin;

use App\Models\Cms\CmsFlag;
use App\Models\Cms\Entry;
use App\Support\Result;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FlagAdminService
{
    public function all(): Collection
    {
        $usage = $this->usageByName();

        return CmsFlag::query()->orderBy('sort')->orderBy('id')->get()
            ->each(function (CmsFlag $flag) use ($usage) {
                $flag->setAttribute('entries_count', (int) ($usage[$flag->name] ?? 0));
            });
    }

    /** @return array<string, int> */
    public function usageByName(): array
    {
        $counts = [];
        Entry::query()->whereNotNull('flags')->where('flags', '!=', '')
            ->pluck('flags')
            ->each(function ($raw) use (&$counts) {
                foreach (array_filter(array_map('trim', explode(',', (string) $raw))) as $name) {
                    $counts[$name] = ($counts[$name] ?? 0) + 1;
                }
            });

        return $counts;
    }

    public function usageCount(CmsFlag $flag): int
    {
        return (int) ($this->usageByName()[$flag->name] ?? 0);
    }

    public function save(?CmsFlag $flag, array $input): array
    {
        $flag ??= new CmsFlag;
        $title = trim((string) ($input['title'] ?? ''));
        $name = strtolower(trim((string) ($input['name'] ?? '')));
        if ($name === '' && $title !== '') {
            $name = Str::slug($title);
        }
        if ($title === '') {
            return Result::fail('请填写显示名称');
        }
        if ($name === '' || ! preg_match('/^[a-z][a-z0-9_\-]*$/', $name)) {
            return Result::fail('标识码用英文字母开头，如 top、recommend');
        }

        $dup = CmsFlag::query()
            ->when($flag->exists, fn ($q) => $q->where('id', '!=', $flag->id))
            ->where('name', $name)
            ->exists();
        if ($dup) {
            return Result::fail('标识码「'.$name.'」已存在');
        }

        $flag->title = $title;
        $flag->name = $name;
        if (array_key_exists('is_active', $input)) {
            $flag->is_active = filter_var($input['is_active'], FILTER_VALIDATE_BOOLEAN);
        } elseif (! $flag->exists) {
            $flag->is_active = true;
        }
        if (array_key_exists('sort', $input) && $input['sort'] !== '' && $input['sort'] !== null) {
            $flag->sort = max(0, (int) $input['sort']);
        } elseif (! $flag->exists) {
            $flag->sort = (int) CmsFlag::query()->max('sort') + 10;
        }
        $creating = ! $flag->exists;
        $flag->save();

        return Result::ok($flag, $creating ? '已添加' : '已保存');
    }

    public function toggle(CmsFlag $flag): array
    {
        $flag->is_active = ! $flag->is_active;
        $flag->save();

        return Result::ok($flag, $flag->is_active ? '已启用，写内容页会再出现' : '已停用，写内容页不再列出');
    }

    public function move(CmsFlag $flag, string $dir): array
    {
        $rows = CmsFlag::query()->orderBy('sort')->orderBy('id')->get();
        $i = $rows->search(fn (CmsFlag $row) => (int) $row->id === (int) $flag->id);
        if ($i === false) {
            return Result::fail('找不到这项');
        }
        $j = $dir === 'up' ? $i - 1 : $i + 1;
        if ($j < 0 || $j >= $rows->count()) {
            return Result::ok($flag, '已经到头了');
        }
        $swap = $rows[$j];
        $a = (int) $flag->sort;
        $b = (int) $swap->sort;
        if ($a === $b) {
            $flag->sort = ($j + 1) * 10;
            $swap->sort = ($i + 1) * 10;
        } else {
            $flag->sort = $b;
            $swap->sort = $a;
        }
        $flag->save();
        $swap->save();

        return Result::ok($flag, '顺序已调整');
    }

    public function delete(CmsFlag $flag): array
    {
        $n = $this->usageCount($flag);
        $flag->delete();

        return Result::ok(null, $n > 0
            ? '已删除。'.$n.' 篇上的标记还在，只是写内容页不再列出'
            : '已删除');
    }
}
