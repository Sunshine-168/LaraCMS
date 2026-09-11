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
    public function index(): View
    {
        return view('admin.media.index', [
            'media' => $this->mediaService->paginate(),
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
        $request->validate(['file' => 'required|file|max:10240']);
        $result = $this->mediaService->upload($request->file('file'), $request->input('alt'));

        return redirect()->route('admin.media.index')->with('status', $result['msg']);
    }

    /** 删除媒体 */
    public function destroy(Media $medium): RedirectResponse
    {
        $result = $this->mediaService->delete($medium);

        return redirect()->route('admin.media.index')->with('status', $result['msg']);
    }
}
