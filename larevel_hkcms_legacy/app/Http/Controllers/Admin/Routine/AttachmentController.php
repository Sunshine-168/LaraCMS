<?php

namespace App\Http\Controllers\Admin\Routine;

use App\Http\Controllers\Admin\BaseAdminController;
use App\Models\Cms\SiteConfig;
use App\Models\Routine\Attachment;
use App\Services\Admin\AdminAuthService;
use App\Services\Cms\SiteService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttachmentController extends BaseAdminController
{
    public function __construct(
        AdminAuthService $auth,
        private readonly SiteService $siteService,
    ) {
        parent::__construct($auth);
    }

    public function index(Request $request): View
    {
        return $this->adminView('admin.routine.attachments.index', [
            'pageTitle' => '附件管理',
            'pageDescription' => '迁移版附件库，已支持列表、编辑和删除，以及新上传文件自动入库。',
            'rows' => $this->buildQuery($request)->paginate(15)->withQueryString(),
            'exts' => $this->extensions(),
        ]);
    }

    public function select(Request $request): View
    {
        return $this->adminView('admin.routine.attachments.select', [
            'pageTitle' => '附件选择',
            'pageDescription' => '选择素材后回传给上层页面。',
            'rows' => $this->buildQuery($request, true)->paginate(15)->withQueryString(),
            'exts' => $this->extensions(),
            'multiple' => $request->query('multiple', 'false'),
            'field' => $request->query('field', ''),
            'fileNum' => (int) $request->query('fileNum', 10),
            'mimetype' => (string) $request->query('mimetype', ''),
            'size' => (int) $request->query('size', 0),
        ]);
    }

    public function edit(Attachment $attachment): View
    {
        return $this->adminView('admin.routine.attachments.edit', [
            'pageTitle' => '编辑附件',
            'pageDescription' => '当前支持附件标题、备注和状态编辑。',
            'attachment' => $attachment,
        ]);
    }

    public function water(): View|RedirectResponse
    {
        if (request()->isMethod('post')) {
            $data = request()->validate([
                'water_on' => ['required', 'in:0,1'],
                'water_type' => ['required', 'in:1,2'],
                'water_width' => ['required', 'integer', 'min:0'],
                'water_height' => ['required', 'integer', 'min:0'],
                'water_text' => ['nullable', 'string', 'max:100'],
                'water_text_size' => ['nullable', 'integer', 'min:1'],
                'water_text_color' => ['nullable', 'string', 'max:20'],
                'water_img_opacity' => ['nullable', 'integer', 'min:0', 'max:100'],
                'water_img' => ['nullable', 'string', 'max:255'],
                'water_img_position' => ['required', 'integer', 'between:1,9'],
            ]);

            $this->saveConfigGroup('water', $data);

            return redirect()->route('admin.attachments.water')->with('status', '水印设置已保存');
        }

        return $this->adminView('admin.routine.attachments.water', [
            'pageTitle' => '水印设置',
            'pageDescription' => '迁移版水印设置页，支持图片水印和文字水印。',
            'sdata' => $this->groupConfig('water'),
        ]);
    }

    public function thumb(): View|RedirectResponse
    {
        if (request()->isMethod('post')) {
            $data = request()->validate([
                'thumb_on' => ['required', 'in:0,1'],
                'thumb_type' => ['required', 'integer', 'between:1,6'],
                'thumb_width' => ['required', 'integer', 'min:1'],
                'thumb_height' => ['required', 'integer', 'min:1'],
            ]);

            $this->saveConfigGroup('thumb', $data);

            return redirect()->route('admin.attachments.thumb')->with('status', '缩略图设置已保存');
        }

        return $this->adminView('admin.routine.attachments.thumb', [
            'pageTitle' => '缩略图设置',
            'pageDescription' => '迁移版缩略图生成设置页。',
            'sdata' => $this->groupConfig('thumb'),
        ]);
    }

    public function update(Request $request, Attachment $attachment): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:50'],
            'remark' => ['nullable', 'string', 'max:250'],
            'status' => ['required', 'in:normal,hidden'],
        ]);

        $data['update_time'] = time();
        $attachment->update($data);

        return redirect()->route('admin.attachments.index')->with('status', '附件已更新');
    }

    public function editTitle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string'],
            'title' => ['required', 'string', 'max:50'],
        ]);

        Attachment::query()
            ->where('path', $data['path'])
            ->update([
                'title' => trim($data['title']),
                'update_time' => time(),
            ]);

        return response()->json([
            'code' => 200,
            'msg' => 'success',
            'data' => [],
        ]);
    }

    public function getTitle(Request $request): JsonResponse
    {
        $paths = array_filter(explode(',', (string) $request->input('paths', '')));

        if ($paths === []) {
            return response()->json([
                'code' => 400,
                'msg' => 'Illegal request',
                'data' => [],
            ], 400);
        }

        $data = Attachment::query()
            ->whereIn('path', $paths)
            ->get(['path', 'title', 'ext', 'size']);

        return response()->json([
            'code' => 200,
            'msg' => 'success',
            'data' => $data,
        ]);
    }

    public function download(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url' => ['required', 'url'],
        ]);

        $response = Http::timeout(30)->get($data['url']);
        if (! $response->successful()) {
            return response()->json([
                'code' => 400,
                'msg' => '远程文件下载失败',
                'data' => [],
            ], 400);
        }

        $body = $response->body();
        $size = strlen($body);
        $maxSize = (int) $this->normalizedFileSize();
        if ($maxSize > 0 && $size > $maxSize) {
            throw ValidationException::withMessages([
                'url' => '文件大小超过限制',
            ]);
        }

        $pathInfo = pathinfo(parse_url($data['url'], PHP_URL_PATH) ?: '');
        $ext = strtolower((string) ($pathInfo['extension'] ?? ''));
        $allowed = $this->allowedExtensions();
        if ($allowed !== [] && $ext !== '' && ! in_array($ext, $allowed, true)) {
            throw ValidationException::withMessages([
                'url' => '文件类型不被允许',
            ]);
        }

        $filename = Str::uuid()->toString().($ext ? '.'.$ext : '');
        $relativePath = 'uploads/'.date('Ymd').'/'.$filename;
        Storage::disk('public')->put($relativePath, $body);
        $publicPath = Storage::url($relativePath);

        $attachment = Attachment::query()->create([
            'user_id' => (int) ($this->auth->user()?->id ?? 0),
            'user_type' => 1,
            'title' => Str::limit((string) ($pathInfo['filename'] ?? 'remote-file'), 50, ''),
            'path' => $publicPath,
            'size' => $size,
            'ext' => $ext,
            'mime_type' => (string) $response->header('Content-Type', ''),
            'md5' => md5($body),
            'storage' => 'local',
            'remark' => '',
            'status' => 'normal',
            'update_time' => time(),
            'create_time' => time(),
        ]);

        return response()->json([
            'code' => 200,
            'msg' => 'success',
            'data' => $attachment,
        ]);
    }

    public function destroy(Attachment $attachment): RedirectResponse
    {
        $path = (string) $attachment->path;

        if (str_starts_with($path, '/storage/')) {
            Storage::disk('public')->delete(ltrim(substr($path, strlen('/storage/')), '/'));
        }

        $attachment->delete();

        return redirect()->route('admin.attachments.index')->with('status', '附件已删除');
    }

    private function buildQuery(Request $request, bool $selectionMode = false)
    {
        $query = Attachment::query()
            ->with('adminUser')
            ->orderByDesc('update_time')
            ->orderByDesc('id');

        if ($title = trim((string) $request->query('title', ''))) {
            $query->where('title', 'like', '%'.$title.'%');
        }

        if ($ext = trim((string) $request->query('ext', ''))) {
            $query->where('ext', $ext);
        }

        $mimeType = trim((string) $request->query('mimetype', ''));
        if ($mimeType !== '') {
            if (str_ends_with($mimeType, '/')) {
                $query->where('mime_type', 'like', $mimeType.'%');
            } elseif (str_contains($mimeType, '/')) {
                $query->where('mime_type', $mimeType);
            } else {
                $query->where('ext', ltrim($mimeType, '.'));
            }
        }

        $size = (int) $request->query('size', 0);
        if ($selectionMode && $size > 0) {
            $query->where('size', '<=', $size);
        }

        return $query;
    }

    private function extensions()
    {
        return Attachment::query()->select('ext')->distinct()->orderBy('ext')->pluck('ext');
    }

    private function normalizedFileSize(): int
    {
        $value = $this->siteService->get('file_size', 0);
        if (is_numeric($value)) {
            $value = (int) $value;
            return $value > 0 && $value < 1024 * 1024 ? $value * 1024 * 1024 : $value;
        }

        return 0;
    }

    private function allowedExtensions(): array
    {
        $value = (string) $this->siteService->get('file_type', '');
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $item): string => strtolower(trim($item)),
            preg_split('/[|,]/', $value) ?: []
        )));
    }

    private function groupConfig(string $group): array
    {
        return SiteConfig::query()
            ->where('group', $group)
            ->whereIn('lang', ['-1', -1])
            ->orderBy('weigh')
            ->get()
            ->mapWithKeys(fn (SiteConfig $item) => [$item->name => $item->value])
            ->all();
    }

    private function saveConfigGroup(string $group, array $data): void
    {
        foreach ($data as $key => $value) {
            SiteConfig::query()
                ->where('group', $group)
                ->where('name', $key)
                ->whereIn('lang', ['-1', -1])
                ->update([
                    'value' => (string) $value,
                ]);
        }

        $this->siteService->clearCache();
    }
}
