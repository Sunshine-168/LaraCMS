<?php

namespace App\Services\Admin;

use App\Models\Cms\Redirect;
use App\Services\Cms\RedirectService;
use App\Support\Result;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RedirectAdminService
{
    public function __construct(private readonly RedirectService $redirects)
    {
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return Redirect::query()->orderByDesc('id')->paginate($perPage);
    }

    public function save(?Redirect $redirect, array $input): array
    {
        return $this->redirects->save($redirect, $input);
    }

    public function delete(Redirect $redirect): array
    {
        return $this->redirects->delete($redirect);
    }

    public function toggle(Redirect $redirect): array
    {
        $redirect->is_active = ! $redirect->is_active;
        $redirect->save();

        return Result::ok($redirect, $redirect->is_active ? '已启用' : '已停用');
    }
}
