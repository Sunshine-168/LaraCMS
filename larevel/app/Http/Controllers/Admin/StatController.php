<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\StatHit;
use App\Services\Cms\Stats\StatService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatController extends Controller
{
    public function __construct(private readonly StatService $stats)
    {
    }

    /** 流量概览 */
    public function index(Request $request): View
    {
        [$from, $to] = $this->range($request);

        return view('admin.stats.index', [
            'overview' => $this->stats->overview(),
            'trend' => $this->stats->dailyTrend(14),
            'topPages' => $this->stats->topPages($from, $to),
            'topReferers' => $this->stats->topReferers($from, $to),
            'from' => $from,
            'to' => $to,
        ]);
    }

    /** 蜘蛛统计 */
    public function spiders(Request $request): View
    {
        [$from, $to] = $this->range($request);

        return view('admin.stats.spiders', [
            'summary' => $this->stats->spiderSummary($from, $to),
            'daily' => $this->stats->spiderDaily($from, $to),
            'recent' => StatHit::query()->spider()->orderByDesc('id')->limit(50)->get(),
            'from' => $from,
            'to' => $to,
            'overview' => $this->stats->overview(),
        ]);
    }

    /** 明细日志 */
    public function logs(Request $request): View
    {
        $q = StatHit::query()->orderByDesc('id');
        if ($request->filled('spider')) {
            $request->boolean('spider') ? $q->spider() : $q->human();
        }
        if ($request->filled('path')) {
            $q->where('path', 'like', '%'.$request->string('path').'%');
        }

        return view('admin.stats.logs', [
            'logs' => $q->paginate(40)->withQueryString(),
            'filters' => $request->only(['spider', 'path']),
        ]);
    }

    /** @return array{0:string,1:string} */
    protected function range(Request $request): array
    {
        $to = $request->input('to', now()->toDateString());
        $from = $request->input('from', now()->subDays(6)->toDateString());
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }
}
