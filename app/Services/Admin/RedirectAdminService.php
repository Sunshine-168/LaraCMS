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

    public function paginate(int $perPage = 20, string $keyword = ''): LengthAwarePaginator
    {
        $query = Redirect::query();
        $keyword = trim($keyword);
        if ($keyword !== '') {
            $like = '%'.addcslashes($keyword, '%_\\').'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('source_path', 'like', $like)
                    ->orWhere('target_url', 'like', $like)
                    ->orWhere('note', 'like', $like);
            });
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
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

        return Result::ok(
            $redirect,
            $redirect->is_active ? '已启用，访客会跳过去' : '已停用，旧地址不再跳转'
        );
    }
}
