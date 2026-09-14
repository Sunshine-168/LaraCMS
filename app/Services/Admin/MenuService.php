<?php

namespace App\Services\Admin;

use App\Models\Cms\Menu;
use App\Models\Cms\MenuItem;
use App\Support\Result;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MenuService
{
    /** 菜单列表 */
    public function all(): Collection
    {
        return Menu::query()->with('items')->orderBy('id')->get();
    }

    /** 保存菜单及条目 */
    public function save(?Menu $menu, array $input): array
    {
        return DB::transaction(function () use ($menu, $input) {
            $menu ??= new Menu;
            $menu->name = $input['name'] ?? '主导航';
            $menu->location = $input['location'] ?? 'header';
            $menu->is_active = ! empty($input['is_active']);
            $menu->save();

            MenuItem::query()->where('menu_id', $menu->id)->delete();

            $created = [];
            foreach ($input['items'] ?? [] as $i => $row) {
                if (empty($row['title_zh_cn']) && empty($row['title_en'])) {
                    continue;
                }
                $parentId = null;
                $parentIndex = $row['parent_index'] ?? '';
                if ($parentIndex !== '' && $parentIndex !== null && isset($created[(int) $parentIndex]) && (int) $parentIndex < $i) {
                    $parentId = $created[(int) $parentIndex]->id;
                }
                $created[$i] = MenuItem::query()->create([
                    'menu_id' => $menu->id,
                    'parent_id' => $parentId,
                    'title' => [
                        'zh_cn' => $row['title_zh_cn'] ?? '',
                        'en' => trim((string) ($row['title_en'] ?? '')) !== ''
                            ? $row['title_en']
                            : ($row['title_zh_cn'] ?? ''),
                    ],
                    'type' => $row['type'] ?? 'url',
                    'url' => $row['url'] ?? '#',
                    'reference_id' => $this->itemReferenceId($row),
                    'sort_order' => $i,
                    'is_active' => true,
                ]);
            }

            return Result::ok($menu->load('items'), '保存成功');
        });
    }

    /** @param  array<string, mixed>  $row */
    protected function itemReferenceId(array $row): ?int
    {
        $type = $row['type'] ?? 'url';
        $raw = match ($type) {
            'category' => $row['category_id'] ?? $row['reference_id'] ?? null,
            'entry' => $row['entry_id'] ?? $row['reference_id'] ?? null,
            default => null,
        };

        return $raw !== null && $raw !== '' ? (int) $raw : null;
    }

    public function toggle(Menu $menu): array
    {
        $menu->is_active = ! $menu->is_active;
        $menu->save();

        return Result::ok($menu, $menu->is_active ? '已启用，前台会按这个位置显示' : '已停用，前台改用栏目导航');
    }

    /** 删除菜单 */
    public function delete(Menu $menu): array
    {
        $n = $menu->items()->count();
        $menu->delete();

        return Result::ok(null, $n > 0 ? '已删除菜单和 '.$n.' 个条目' : '已删除');
    }
}
