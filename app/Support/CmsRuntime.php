<?php

namespace App\Support;

/** 采集/清理等后台任务的运行模式（按站点 .env 切换） */
class CmsRuntime
{
    public const MODE_SCHEDULE = 'schedule';

    public const MODE_QUEUE = 'queue';

    public const MODE_OFF = 'off';

    public static function scheduleEnabled(): bool
    {
        return (bool) config('cms.runtime.schedule_enabled', true);
    }

    /** @return self::MODE_* */
    public static function mode(string $task): string
    {
        $mode = strtolower((string) config("cms.runtime.{$task}", self::MODE_SCHEDULE));

        return in_array($mode, [self::MODE_SCHEDULE, self::MODE_QUEUE, self::MODE_OFF], true)
            ? $mode
            : self::MODE_SCHEDULE;
    }

    public static function collectMode(): string
    {
        return self::mode('collect');
    }

    public static function usesQueue(string $task): bool
    {
        return self::mode($task) === self::MODE_QUEUE;
    }

    public static function queueName(string $task): string
    {
        return (string) config("cms.runtime.{$task}_queue", 'cms');
    }
}
