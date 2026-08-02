<?php

namespace App\Services\Cms\Stats;

use App\Models\Cms\StatHit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatService
{
    /** 概览：今日/昨日/7天/30天 */
    public function overview(): array
    {
        return [
            'today' => $this->periodStats(now()->toDateString(), now()->toDateString()),
            'yesterday' => $this->periodStats(now()->subDay()->toDateString(), now()->subDay()->toDateString()),
            'days7' => $this->periodStats(now()->subDays(6)->toDateString(), now()->toDateString()),
            'days30' => $this->periodStats(now()->subDays(29)->toDateString(), now()->toDateString()),
        ];
    }

    /** @return array{pv:int,uv:int,ip:int,spider_pv:int} */
    public function periodStats(string $from, string $to): array
    {
        $base = StatHit::query()->betweenDates($from, $to);

        return [
            'pv' => (clone $base)->human()->count(),
            'uv' => (clone $base)->human()->distinct('visitor_hash')->count('visitor_hash'),
            'ip' => (clone $base)->human()->whereNotNull('ip')->distinct('ip')->count('ip'),
            'spider_pv' => (clone $base)->spider()->count(),
        ];
    }

    /** 按日趋势 */
    public function dailyTrend(int $days = 14): Collection
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $driver = DB::connection()->getDriverName();
        $dayExpr = match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM-DD')",
            default => 'DATE(created_at)',
        };

        $rows = StatHit::query()
            ->where('created_at', '>=', $from)
            ->selectRaw("{$dayExpr} as day")
            ->selectRaw('SUM(CASE WHEN is_spider = 0 THEN 1 ELSE 0 END) as pv')
            ->selectRaw('SUM(CASE WHEN is_spider = 1 THEN 1 ELSE 0 END) as spider_pv')
            ->selectRaw('COUNT(DISTINCT CASE WHEN is_spider = 0 THEN visitor_hash END) as uv')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $out = collect();
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i)->toDateString();
            $row = $rows->get($day);
            $out->push([
                'day' => $day,
                'pv' => (int) ($row->pv ?? 0),
                'uv' => (int) ($row->uv ?? 0),
                'spider_pv' => (int) ($row->spider_pv ?? 0),
            ]);
        }

        return $out;
    }

    /** 热门页面 */
    public function topPages(string $from, string $to, int $limit = 20, bool $humanOnly = true): Collection
    {
        $q = StatHit::query()->betweenDates($from, $to);
        if ($humanOnly) {
            $q->human();
        }

        return $q->select('path', DB::raw('COUNT(*) as hits'))
            ->groupBy('path')
            ->orderByDesc('hits')
            ->limit($limit)
            ->get();
    }

    /** 来路 */
    public function topReferers(string $from, string $to, int $limit = 20): Collection
    {
        return StatHit::query()
            ->betweenDates($from, $to)
            ->human()
            ->whereNotNull('referer')
            ->where('referer', '!=', '')
            ->select('referer', DB::raw('COUNT(*) as hits'))
            ->groupBy('referer')
            ->orderByDesc('hits')
            ->limit($limit)
            ->get();
    }

    /** 蜘蛛汇总 */
    public function spiderSummary(string $from, string $to): Collection
    {
        return StatHit::query()
            ->betweenDates($from, $to)
            ->spider()
            ->select('spider_name', DB::raw('COUNT(*) as hits'), DB::raw('COUNT(DISTINCT ip) as ips'))
            ->groupBy('spider_name')
            ->orderByDesc('hits')
            ->get();
    }

    /** 蜘蛛按日 */
    public function spiderDaily(string $from, string $to): Collection
    {
        $driver = DB::connection()->getDriverName();
        $dayExpr = match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM-DD')",
            default => 'DATE(created_at)',
        };

        return StatHit::query()
            ->betweenDates($from, $to)
            ->spider()
            ->selectRaw("{$dayExpr} as day, spider_name, COUNT(*) as hits")
            ->groupBy('day', 'spider_name')
            ->orderBy('day')
            ->get();
    }

    public function prune(int $keepDays = 90): int
    {
        $before = Carbon::now()->subDays(max(1, $keepDays));

        return StatHit::query()->where('created_at', '<', $before)->delete();
    }
}
