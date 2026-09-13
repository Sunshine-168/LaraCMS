<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    /** 后台首页：今日态势 / 待办 / 快捷动作 / 站点健康 */
    public function index(): View
    {
        $admin = auth('admin')->user();
        abort_unless($admin, 403);

        $board = $this->dashboard->forAdmin($admin);

        return view('admin.dashboard', $board + [
            'spark' => $this->dashboard->sparkPoints($board['trend']),
        ]);
    }
}
