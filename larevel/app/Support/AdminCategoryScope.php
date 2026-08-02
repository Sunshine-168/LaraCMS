<?php

namespace App\Support;

use App\Models\Admin\AdminUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/** 后台栏目授权作用域 */
class AdminCategoryScope
{
    public static function admin(): ?AdminUser
    {
        return Auth::guard('admin')->user();
    }

    /** @return list<int>|null null=全部 */
    public static function allowedIds(): ?array
    {
        return self::admin()?->allowedCategoryIds();
    }

    public static function canAccess(?int $categoryId): bool
    {
        $ids = self::allowedIds();
        if ($ids === null) {
            return true;
        }
        if ($categoryId === null || $categoryId === 0) {
            return false;
        }

        return in_array($categoryId, $ids, true);
    }

    /** @param  list<int>  $categoryIds */
    public static function canAccessAny(array $categoryIds): bool
    {
        $allowed = self::allowedIds();
        if ($allowed === null) {
            return true;
        }
        foreach ($categoryIds as $id) {
            if (in_array((int) $id, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    public static function applyToEntries(Builder $query): void
    {
        $ids = self::allowedIds();
        if ($ids === null) {
            return;
        }
        $query->where(function (Builder $q) use ($ids) {
            $q->whereIn('category_id', $ids)
                ->orWhereHas('categories', fn (Builder $c) => $c->whereIn('cms_categories.id', $ids));
        });
    }

    public static function applyToCategories(Builder $query): void
    {
        $ids = self::allowedIds();
        if ($ids === null) {
            return;
        }
        $query->whereIn('id', $ids);
    }

    public static function denyMessage(): string
    {
        return '无权操作该栏目内容';
    }
}
