<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Admin\BaseAdminController;
use App\Models\Cms\Banner;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Recommend;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecommendController extends BaseAdminController
{
    public function index(Request $request): View
    {
        $lang = $request->query('clang', config('cms.default_locale', 'zh-cn'));

        return $this->adminView('admin.cms.recommend.index', [
            'pageTitle' => '推荐位管理',
            'pageDescription' => '迁移版推荐位后台，已支持常用的图集、视频、HTML、内容数据、文字链接。',
            'lang' => $lang,
            'rows' => Recommend::query()
                ->where('lang', $lang)
                ->orderBy('weigh')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $lang = $request->query('clang', config('cms.default_locale', 'zh-cn'));

        return $this->adminView('admin.cms.recommend.form', [
            'pageTitle' => '新增推荐位',
            'pageDescription' => '先迁移基础推荐位配置，后续再补更完整的拖拽和素材联动。',
            'lang' => $lang,
            'recommend' => null,
            'banners' => collect(),
            'models' => ContentModel::query()->active()->orderBy('id')->get(),
            'categories' => Category::query()->active()->where('lang', $lang)->orderBy('weigh')->get(),
            'valueId' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedRecommend($request);

        if (Recommend::query()->where('name', $data['name'])->where('lang', $data['lang'])->exists()) {
            return back()->withErrors(['name' => '推荐位标识已存在'])->withInput();
        }

        DB::transaction(function () use ($data, $request): void {
            $recommend = Recommend::query()->create($data);
            $this->syncPayload($recommend, $request);
        });

        return redirect()->route('admin.recommends.index', ['clang' => $data['lang']])->with('status', '推荐位已创建');
    }

    public function edit(Recommend $recommend): View
    {
        $banners = Banner::query()->where('recommend_id', $recommend->id)->orderBy('weigh')->orderBy('id')->get();

        return $this->adminView('admin.cms.recommend.form', [
            'pageTitle' => '编辑推荐位',
            'pageDescription' => '当前编辑的是迁移后的 Laravel 兼容推荐位表单。',
            'lang' => $recommend->lang,
            'recommend' => $recommend,
            'banners' => $banners,
            'models' => ContentModel::query()->active()->orderBy('id')->get(),
            'categories' => Category::query()->active()->where('lang', $recommend->lang)->orderBy('weigh')->get(),
            'valueId' => $recommend->type == 4 && $recommend->value_id ? (json_decode($recommend->value_id, true) ?: []) : [],
        ]);
    }

    public function update(Request $request, Recommend $recommend): RedirectResponse
    {
        $data = $this->validatedRecommend($request, $recommend);

        if (Recommend::query()
            ->where('name', $data['name'])
            ->where('lang', $recommend->lang)
            ->where('id', '<>', $recommend->id)
            ->exists()) {
            return back()->withErrors(['name' => '推荐位标识已存在'])->withInput();
        }

        DB::transaction(function () use ($recommend, $data, $request): void {
            $recommend->update($data);
            Banner::query()->where('recommend_id', $recommend->id)->delete();
            $this->syncPayload($recommend, $request);
        });

        return redirect()->route('admin.recommends.edit', $recommend)->with('status', '推荐位已更新');
    }

    public function destroy(Recommend $recommend): RedirectResponse
    {
        DB::transaction(function () use ($recommend): void {
            Banner::query()->where('recommend_id', $recommend->id)->delete();
            $recommend->delete();
        });

        return redirect()->route('admin.recommends.index', ['clang' => $recommend->lang])->with('status', '推荐位已删除');
    }

    private function validatedRecommend(Request $request, ?Recommend $recommend = null): array
    {
        $lang = $recommend?->lang ?: $request->input('lang', config('cms.default_locale', 'zh-cn'));

        return $request->validate([
            'name' => ['required', 'alpha_dash', 'max:20'],
            'remark' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'in:1,2,3,4,5'],
            'weigh' => ['nullable', 'integer'],
            'status' => ['required', 'in:normal,hidden'],
            'lang' => ['nullable'],
        ]) + [
            'lang' => $lang,
            'admin_id' => $this->auth->user()?->id ?? 0,
            'update_time' => time(),
            'create_time' => $recommend?->create_time ?? time(),
            'value_id' => '',
        ];
    }

    private function syncPayload(Recommend $recommend, Request $request): void
    {
        $type = (int) $recommend->type;

        if ($type === 4) {
            $modelId = (int) $request->input('model', 0);
            $limit = (int) $request->input('limit', 0);
            if ($modelId <= 0 || $limit <= 0) {
                throw ValidationException::withMessages([
                    'model' => '内容数据推荐需要选择模型并填写数量',
                ]);
            }

            $recommend->update([
                'value_id' => json_encode([
                    'model' => $modelId,
                    'limit' => $limit,
                    'order' => (string) $request->input('order', ''),
                    'column' => implode(',', array_filter((array) $request->input('column', []))),
                ], JSON_UNESCAPED_UNICODE),
                'update_time' => time(),
            ]);

            return;
        }

        if ($type === 3) {
            $content = (string) $request->input('html_content', '');
            if ($content === '') {
                throw ValidationException::withMessages([
                    'html_content' => 'HTML 内容不能为空',
                ]);
            }

            Banner::query()->create([
                'recommend_id' => $recommend->id,
                'admin_id' => $this->auth->user()?->id ?? 0,
                'type' => 3,
                'content' => $content,
                'lang' => $recommend->lang,
                'status' => 'normal',
                'create_time' => time(),
                'update_time' => time(),
            ]);

            return;
        }

        $items = $this->extractBannerItems($type, $request, $recommend->lang, $recommend->id);
        if ($items === []) {
            throw ValidationException::withMessages([
                'payload' => '请至少添加一条推荐项',
            ]);
        }

        Banner::query()->insert($items);
    }

    private function extractBannerItems(int $type, Request $request, string $lang, int $recommendId): array
    {
        $items = [];
        $adminId = $this->auth->user()?->id ?? 0;
        $now = time();

        if ($type === 1) {
            foreach ((array) $request->input('image', []) as $index => $image) {
                if (! $image) {
                    continue;
                }
                $items[] = [
                    'recommend_id' => $recommendId,
                    'admin_id' => $adminId,
                    'type' => 1,
                    'title' => (string) (((array) $request->input('title', []))[$index] ?? ''),
                    'image' => (string) $image,
                    'url' => (string) (((array) $request->input('url', []))[$index] ?? ''),
                    'notes' => (string) (((array) $request->input('notes', []))[$index] ?? ''),
                    'weigh' => (int) (((array) $request->input('item_weigh', []))[$index] ?? $index),
                    'new_window' => (int) (((array) $request->input('new_window', []))[$index] ?? 0),
                    'lang' => $lang,
                    'status' => 'normal',
                    'create_time' => $now,
                    'update_time' => $now,
                ];
            }
        } elseif ($type === 2) {
            foreach ((array) $request->input('v_content', []) as $index => $content) {
                if (! $content) {
                    continue;
                }
                $items[] = [
                    'recommend_id' => $recommendId,
                    'admin_id' => $adminId,
                    'type' => 2,
                    'title' => (string) (((array) $request->input('v_title', []))[$index] ?? ''),
                    'content' => (string) $content,
                    'url' => (string) (((array) $request->input('v_url', []))[$index] ?? ''),
                    'notes' => (string) (((array) $request->input('v_notes', []))[$index] ?? ''),
                    'weigh' => (int) (((array) $request->input('v_weigh', []))[$index] ?? $index),
                    'new_window' => (int) (((array) $request->input('v_new_window', []))[$index] ?? 0),
                    'lang' => $lang,
                    'status' => 'normal',
                    'create_time' => $now,
                    'update_time' => $now,
                ];
            }
        } elseif ($type === 5) {
            foreach ((array) $request->input('link_url', []) as $index => $url) {
                if (! $url) {
                    continue;
                }
                $items[] = [
                    'recommend_id' => $recommendId,
                    'admin_id' => $adminId,
                    'type' => 5,
                    'title' => (string) (((array) $request->input('link_title', []))[$index] ?? ''),
                    'url' => (string) $url,
                    'notes' => (string) (((array) $request->input('link_notes', []))[$index] ?? ''),
                    'weigh' => (int) (((array) $request->input('link_weigh', []))[$index] ?? $index),
                    'new_window' => (int) (((array) $request->input('link_new_window', []))[$index] ?? 0),
                    'lang' => $lang,
                    'status' => 'normal',
                    'create_time' => $now,
                    'update_time' => $now,
                ];
            }
        }

        return $items;
    }
}
