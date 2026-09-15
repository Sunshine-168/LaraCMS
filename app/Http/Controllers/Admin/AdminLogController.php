<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLogController extends Controller
{
    public function __construct(private readonly AdminLogService $logs) {}

    public function index(Request $request): View
    {
        $filters = $this->logs->filters($request);

        return view('admin.logs.index', $filters + [
            'logs' => $this->logs->paginate($filters),
            'today' => now()->toDateString(),
            'yesterday' => now()->subDay()->toDateString(),
            'weekFrom' => now()->subDays(6)->toDateString(),
            'monthFrom' => now()->subDays(29)->toDateString(),
        ]);
    }
}
