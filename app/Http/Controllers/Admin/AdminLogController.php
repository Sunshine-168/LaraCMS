<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\AdminLog;
use Illuminate\View\View;

class AdminLogController extends Controller
{
    public function index(): View
    {
        return view('admin.logs.index', [
            'logs' => AdminLog::query()->orderByDesc('id')->paginate(40),
        ]);
    }
}
