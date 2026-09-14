<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Media;
use App\Services\Admin\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function __construct(private readonly MediaService $mediaService)
    {
    }

    /** 媒体库列表 */
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', '');
        if (! in_array($type, ['image', 'file'], true)) {
            $type = '';
        }

        return view('admin.media.index', [
            'media' => $this->mediaService->paginate(24, $keyword, $type !== '' ? $type : null),
            'q' => $keyword,
            'type' => $type,
        ]);
    }

    /** 选择器 JSON */
    public function json(): JsonResponse
    {
        return response()->json($this->mediaService->recentImages());
    }

    /** 上传媒体 */
    public function store(Request $request): RedirectResponse
    {
        $files = $request->file('files', []);
        if (! is_array($files)) {
            $files = $files ? [$files] : [];
        }
        if ($request->hasFile('file')) {
            $files[] = $request->file('file');
        }
        $files = array_values(array_filter($files, fn ($f) => $f instanceof \Illuminate\Http\UploadedFile));
        if ($files === []) {
            return back()->with('error', '请选择要上传的文件');
        }
        $request->validate([
            'files' => 'nullable|array',
            'files.*' => 'file|max:10240',
            'file' => 'nullable|file|max:10240',
        ]);
        $ok = 0;
        $msg = '上传失败';
        foreach ($files as $file) {
            $result = $this->mediaService->upload($file, $request->input('alt'));
            if ($result['ok']) {
                $ok++;
            }
            $msg = $result['msg'];
        }
        $back = array_filter([
            'q' => $request->input('keep_q'),
            'type' => $request->input('keep_type'),
        ], fn ($v) => $v !== null && $v !== '');

        return redirect()->route('admin.media.index', $back)
            ->with($ok > 0 ? 'status' : 'error', $ok > 1 ? '已上传 '.$ok.' 个文件' : $msg);
    }

    /** 编辑器/封面选择器内上传图片 */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp'],
        ]);
        $result = $this->mediaService->upload($request->file('file'), $request->input('alt'));
        $media = $result['data'];
        if (! $media instanceof Media) {
            return response()->json(['ok' => false, 'msg' => $result['msg'] ?: '上传失败'], 422);
        }

        return response()->json([
            'ok' => true,
            'msg' => $result['msg'],
            'data' => $this->mediaService->toPickerItem($media),
        ]);
    }

    /** 删除媒体 */
    public function destroy(Media $medium): RedirectResponse
    {
        $result = $this->mediaService->delete($medium);

        return redirect()->route('admin.media.index')->with('status', $result['msg']);
    }
}
