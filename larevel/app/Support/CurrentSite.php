<?php

namespace App\Support;

use App\Models\Cms\Site;

class CurrentSite
{
    protected static ?int $id = null;

    protected static ?Site $model = null;

    public static function id(): ?int
    {
        return self::$id;
    }

    public static function model(): ?Site
    {
        if (self::$id === null) {
            return null;
        }
        if (self::$model && (int) self::$model->id === self::$id) {
            return self::$model;
        }

        return self::$model = Site::query()->withoutGlobalScopes()->find(self::$id);
    }

    public static function set(int|Site|null $site): void
    {
        if ($site === null) {
            self::clear();

            return;
        }
        if ($site instanceof Site) {
            self::$id = (int) $site->id;
            self::$model = $site;

            return;
        }
        self::$id = $site;
        self::$model = null;
    }

    public static function clear(): void
    {
        self::$id = null;
        self::$model = null;
    }

    /** 无上下文时回落默认站 ID（安装前可能为 null） */
    public static function idOrDefault(): ?int
    {
        return self::$id ?? Site::defaultId();
    }

    /** @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public static function run(int|Site $site, callable $callback): mixed
    {
        $prevId = self::$id;
        $prevModel = self::$model;
        self::set($site);
        try {
            return $callback();
        } finally {
            self::$id = $prevId;
            self::$model = $prevModel;
        }
    }
}
