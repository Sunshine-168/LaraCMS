<?php

namespace App\Http\Middleware;

use App\Services\Admin\AdminLogService;
use App\Support\AdminLogAction;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/** 记录后台写操作 */
class LogAdminAction
{
    public function __construct(private readonly AdminLogService $logs)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }
        if (str_contains($request->path(), 'admin/login')
            || str_contains($request->path(), 'admin/logout')
            || str_contains($request->path(), 'html-cache/disk-step')) {
            return $response;
        }

        $admin = auth('admin')->user();
        $this->logs->record([
            'admin_id' => $admin?->id,
            'admin_name' => $admin?->name,
            'method' => $request->method(),
            'path' => mb_substr('/'.$request->path(), 0, 500),
            'detail' => AdminLogAction::summarize($request->except(['password', 'password_confirmation', 'current_password', '_token']), $this->fileNames($request)),
            'ip' => $request->ip(),
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }

    /** @return list<string> */
    private function fileNames(Request $request): array
    {
        $names = [];
        foreach ($request->allFiles() as $file) {
            $items = is_array($file) ? $file : [$file];
            foreach ($items as $item) {
                if ($item instanceof UploadedFile) {
                    $names[] = $item->getClientOriginalName();
                }
            }
        }

        return $names;
    }
}
