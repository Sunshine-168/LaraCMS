<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Admin\BaseAdminController;
use App\Models\Admin\CategoryPriv;
use App\Models\Cms\Archive;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryController extends BaseAdminController
{
    public function index(): View
    {
        $lang = request()->query('clang', config('cms.default_locale', 'zh-cn'));

        $rows = $this->categoryRows($lang);

        return $this->adminView('admin.cms.categories.index', [
            'lang' => $lang,
            'rows' => $this->flattenTree($rows),
            'models' => ContentModel::query()->active()->orderBy('id')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $lang = $request->query('clang', config('cms.default_locale', 'zh-cn'));
        $rows = $this->categoryRows($lang);

        return $this->adminView('admin.cms.categories.form', [
            'pageTitle' => '新增栏目',
            'pageDescription' => '先迁移基础字段，扩展字段和模板标签后续再补。',
            'lang' => $lang,
            'category' => null,
            'models' => ContentModel::query()->active()->orderBy('id')->get(),
            'parents' => $this->flattenTree($rows),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $lang = $data['lang'];

        if (Category::query()->where('name', $data['name'])->where('lang', $lang)->exists()) {
            return back()->withErrors(['name' => '目录名已存在'])->withInput();
        }

        $data = $this->normalizeCategoryData($data);

        DB::transaction(function () use (&$data): void {
            $category = Category::query()->create($data);

            if (! $this->auth->hasSuperAdmin()) {
                foreach ($this->auth->accessibleCategoryIds() ?? [] as $groupId) {
                    // no-op, kept for compatibility
                }
                foreach ($this->authGroupIds() as $groupId) {
                    CategoryPriv::query()->create([
                        'category_id' => $category->id,
                        'auth_group_id' => $groupId,
                    ]);
                }
            }

            $this->createSingleArchiveWhenNeeded($category);
        });

        return redirect()->route('admin.categories.index', ['clang' => $lang])->with('status', '栏目已创建');
    }

    public function edit(Request $request, Category $category): View
    {
        $lang = $category->lang ?: $request->query('clang', config('cms.default_locale', 'zh-cn'));
        $rows = $this->categoryRows($lang)->reject(fn (array $item) => (int) $item['id'] === (int) $category->id)->values();

        return $this->adminView('admin.cms.categories.form', [
            'pageTitle' => '编辑栏目',
            'pageDescription' => '当前编辑的是迁移后的 Laravel 兼容后台表单。',
            'lang' => $lang,
            'category' => $category,
            'models' => ContentModel::query()->active()->orderBy('id')->get(),
            'parents' => $this->flattenTree($rows),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validatedData($request, $category);

        if (Category::query()
            ->where('name', $data['name'])
            ->where('lang', $category->lang)
            ->where('id', '<>', $category->id)
            ->exists()) {
            return back()->withErrors(['name' => '目录名已存在'])->withInput();
        }

        $data = $this->normalizeCategoryData($data);

        if ((int) $data['parent_id'] === (int) $category->id) {
            return back()->withErrors(['parent_id' => '不能选择自身作为父级'])->withInput();
        }

        $category->update($data);

        return redirect()->route('admin.categories.index', ['clang' => $category->lang])->with('status', '栏目已更新');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $ids = $this->collectChildIds($category->id, $this->categoryRows($category->lang))->push($category->id)->all();

        if (Archive::query()->whereIn('category_id', $ids)->exists()) {
            return back()->withErrors(['delete' => '该栏目或其子栏目下仍有内容，暂不允许删除']);
        }

        DB::transaction(function () use ($ids): void {
            CategoryPriv::query()->whereIn('category_id', $ids)->delete();
            Category::query()->whereIn('id', $ids)->delete();
        });

        return redirect()->route('admin.categories.index', ['clang' => $category->lang])->with('status', '栏目已删除');
    }

    private function flattenTree(Collection $rows, int $parentId = 0, int $level = 0): array
    {
        $output = [];

        foreach ($rows->where('parent_id', $parentId) as $row) {
            $row['level'] = $level;
            $output[] = $row;
            $output = array_merge($output, $this->flattenTree($rows, (int) $row['id'], $level + 1));
        }

        return $output;
    }

    private function categoryRows(string $lang): Collection
    {
        $query = Category::query()
            ->from('category as c')
            ->leftJoin('model as m', 'm.id', '=', 'c.model_id')
            ->where('c.lang', $lang)
            ->orderBy('c.weigh')
            ->orderBy('c.id')
            ->select(['c.*', 'm.name as model_name']);

        $allowedIds = $this->auth->accessibleCategoryIds();
        if (is_array($allowedIds)) {
            $query->whereIn('c.id', $allowedIds ?: [0]);
        }

        return $query->get()->map(function ($row): array {
            $data = (array) $row;
            $data['type_text'] = match ($data['type']) {
                'category' => '栏目封面',
                'list' => '内容列表',
                'link' => ! empty($data['model_id']) ? '单页内容' : '外部链接',
                default => $data['type'],
            };

            return $data;
        });
    }

    private function validatedData(Request $request, ?Category $category = null): array
    {
        $lang = $category?->lang ?: $request->input('lang', config('cms.default_locale', 'zh-cn'));

        $data = $request->validate([
            'parent_id' => ['required', 'integer', 'min:0'],
            'model_id' => ['nullable', 'integer', 'min:0'],
            'type' => ['required', 'in:category,list,link'],
            'name' => ['nullable', 'alpha_dash', 'max:50'],
            'title' => ['required', 'string', 'max:50'],
            'url' => ['nullable', 'string', 'max:250'],
            'category_tpl' => ['nullable', 'string', 'max:100'],
            'list_tpl' => ['nullable', 'string', 'max:100'],
            'show_tpl' => ['nullable', 'string', 'max:100'],
            'seo_title' => ['nullable', 'string', 'max:250'],
            'seo_keywords' => ['nullable', 'string', 'max:250'],
            'seo_desc' => ['nullable', 'string', 'max:250'],
            'ismenu' => ['required', 'in:0,1'],
            'target' => ['nullable', 'string', 'max:20'],
            'user_auth' => ['nullable', 'in:0,1'],
            'weigh' => ['nullable', 'integer'],
            'num' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:normal,hidden'],
        ]);

        $data['lang'] = $lang;
        $data['name'] = $data['name'] ?: $this->generateSlug($data['title']);

        return $data;
    }

    private function normalizeCategoryData(array $data): array
    {
        $data['model_id'] = (int) ($data['model_id'] ?? 0);
        $data['parent_id'] = (int) ($data['parent_id'] ?? 0);
        $data['ismenu'] = (int) ($data['ismenu'] ?? 1);
        $data['user_auth'] = (int) ($data['user_auth'] ?? 0);
        $data['weigh'] = (int) ($data['weigh'] ?? 0);
        $data['num'] = (int) ($data['num'] ?? 10);
        $data['target'] = $data['target'] ?: '_self';

        if ($data['type'] === 'category') {
            $data['list_tpl'] = '';
            $data['show_tpl'] = '';
            $data['url'] = '';
        } elseif ($data['type'] === 'link' && $data['model_id'] > 0) {
            $data['category_tpl'] = '';
            $data['list_tpl'] = '';
            $data['url'] = '';
        } elseif ($data['type'] === 'link' && $data['model_id'] === 0) {
            $data['category_tpl'] = '';
            $data['list_tpl'] = '';
            $data['show_tpl'] = '';
        } else {
            $data['category_tpl'] = '';
            $data['url'] = '';
        }

        return $data;
    }

    private function generateSlug(string $title): string
    {
        $slug = Str::slug($title, '-');

        if ($slug === '') {
            $slug = 'category-'.Str::lower(Str::random(8));
        }

        return $slug;
    }

    private function createSingleArchiveWhenNeeded(Category $category): void
    {
        if ($category->type !== 'link' || (int) $category->model_id <= 0) {
            return;
        }

        $model = ContentModel::query()->find($category->model_id);
        if (! $model || (int) $model->is_search === -1 || empty($model->tablename)) {
            return;
        }

        $archiveId = DB::table('archives')->insertGetId([
            'category_id' => $category->id,
            'model_id' => $category->model_id,
            'title' => $category->title,
            'show_tpl' => $category->show_tpl ?: 'show.html',
            'status' => 'normal',
            'lang' => $category->lang,
            'create_time' => time(),
            'update_time' => time(),
            'publish_time' => time(),
        ]);

        DB::table($model->tablename)->insert([
            'id' => $archiveId,
            'content' => '',
        ]);
    }

    private function collectChildIds(int $parentId, Collection $rows): Collection
    {
        $children = collect();

        foreach ($rows->where('parent_id', $parentId) as $row) {
            $children->push((int) $row['id']);
            $children = $children->merge($this->collectChildIds((int) $row['id'], $rows));
        }

        return $children->unique()->values();
    }

    private function authGroupIds(): array
    {
        return DB::table('auth_group_access')
            ->where('admin_id', $this->auth->user()?->id)
            ->pluck('group_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
