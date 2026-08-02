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

    public static function applyToEntries(Builder $query): void
    {
        $ids = self::allowedIds();
        if ($ids === null) {
            return;
        }
        $query->whereIn('category_id', $ids);
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
