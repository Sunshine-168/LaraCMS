<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminAuthService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;

abstract class BaseAdminController extends Controller
{
    public function __construct(protected readonly AdminAuthService $auth)
    {
        ViewFacade::share('adminUser', $this->auth->user());
        ViewFacade::share('adminMenuTree', $this->auth->menuTree());
        ViewFacade::share('adminGroupNames', $this->auth->groupNames());
    }

    protected function adminView(string $view, array $data = []): View
    {
        return view($view, $data);
    }
}
