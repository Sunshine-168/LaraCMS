<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Admin\BaseAdminController;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Models\Cms\ModelField;
use App\Models\Cms\Archive;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ContentController extends BaseAdminController
{
    public function index(Request $request): View
    {
        $lang = $request->query('clang', config('cms.default_locale', 'zh-cn'));
        $categoryId = (int) $request->query('category_id', 0);
        $modelId = (int) $request->query('model_id', 0);
        $includeSub = (int) $request->query('issub', 0) === 1;

        $categories = $this->availableCategories($lang);

        if ($categoryId <= 0 || $modelId <= 0) {
            return $this->adminView('admin.cms.content.index', [
                'pageTitle' => '内容管理',
                'pageDescription' => '先选择栏目，再查看该栏目下的内容数据。',
                'categories' => $categories,
                'rows' => [],
                'selectedCategory' => null,
                'selectedModel' => null,
            ]);
        }

        $selectedCategory = $categories->firstWhere('id', $categoryId);
        $selectedModel = ContentModel::query()->find($modelId);

        abort_if(! $selectedCategory || ! $selectedModel, 404);

        $categoryIds = [$categoryId];
        if ($includeSub) {
            $subIds = Category::query()
                ->where('parent_id', $categoryId)
                ->where('model_id', $modelId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $categoryIds = array_unique(array_merge($categoryIds, $subIds));
        }

        $rows = $this->queryRows($selectedModel, $categoryIds);

        return $this->adminView('admin.cms.content.index', [
            'pageTitle' => '内容管理',
            'pageDescription' => '迁移版内容列表，后续再补新增、编辑、回收站和批量操作。',
            'categories' => $categories,
            'rows' => $rows,
            'selectedCategory' => $selectedCategory,
            'selectedModel' => $selectedModel,
            'includeSub' => $includeSub,
        ]);
    }

    public function create(Request $request): View
    {
        [$category, $model, $categories] = $this->resolveContext($request);

        return $this->adminView('admin.cms.content.form', [
            'pageTitle' => '新增内容',
            'pageDescription' => '当前是迁移版内容编辑表单，优先兼容常用字段。',
            'categories' => $categories,
            'selectedCategory' => $category,
            'selectedModel' => $model,
            'fieldDefinitions' => $this->fieldDefinitions($model),
            'row' => null,
            'extra' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$category, $model] = $this->resolveContext($request, false);
        [$mainData, $viceData] = $this->extractPayload($request, $model, $category);

        if (! empty($mainData['diyname']) && Archive::query()->where('diyname', $mainData['diyname'])->exists()) {
            return back()->withErrors(['diyname' => '自定义 URL 名已存在'])->withInput();
        }

        $archiveId = DB::transaction(function () use ($model, $category, $mainData, $viceData) {
            $archiveId = DB::table('archives')->insertGetId($mainData);

            if ($viceData !== [] && Schema::hasTable($model->tablename)) {
                DB::table($model->tablename)->insert(array_merge(['id' => $archiveId], $viceData));
            }

            return $archiveId;
        });

        return redirect()
            ->route('admin.contents.edit', ['content' => $archiveId, 'category_id' => $category->id, 'model_id' => $model->id])
            ->with('status', '内容已创建');
    }

    public function edit(Request $request, Archive $content): View
    {
        $category = Category::query()->findOrFail((int) $content->category_id);
        $this->ensureCategoryAccess($category);
        $model = ContentModel::query()->findOrFail((int) $content->model_id);
        $categories = $this->availableCategories($category->lang);

        return $this->adminView('admin.cms.content.form', [
            'pageTitle' => '编辑内容',
            'pageDescription' => '当前编辑的是迁移后的 Laravel 兼容内容表单。',
            'categories' => $categories,
            'selectedCategory' => $category,
            'selectedModel' => $model,
            'fieldDefinitions' => $this->fieldDefinitions($model),
            'row' => $content,
            'extra' => $content->extra(),
        ]);
    }

    public function update(Request $request, Archive $content): RedirectResponse
    {
        $category = Category::query()->findOrFail((int) $request->input('category_id', $content->category_id));
        $this->ensureCategoryAccess($category);
        $model = ContentModel::query()->findOrFail((int) $request->input('model_id', $content->model_id));

        [$mainData, $viceData] = $this->extractPayload($request, $model, $category, $content);

        if (! empty($mainData['diyname']) && Archive::query()->where('id', '<>', $content->id)->where('diyname', $mainData['diyname'])->exists()) {
            return back()->withErrors(['diyname' => '自定义 URL 名已存在'])->withInput();
        }

        DB::transaction(function () use ($content, $model, $mainData, $viceData) {
            DB::table('archives')->where('id', $content->id)->update($mainData);

            if (Schema::hasTable($model->tablename) && $viceData !== []) {
                if (DB::table($model->tablename)->where('id', $content->id)->exists()) {
                    DB::table($model->tablename)->where('id', $content->id)->update($viceData);
                } else {
                    DB::table($model->tablename)->insert(array_merge(['id' => $content->id], $viceData));
                }
            }
        });

        return redirect()
            ->route('admin.contents.edit', ['content' => $content->id, 'category_id' => $category->id, 'model_id' => $model->id])
            ->with('status', '内容已更新');
    }

    public function destroy(Archive $content): RedirectResponse
    {
        $category = Category::query()->findOrFail((int) $content->category_id);
        $this->ensureCategoryAccess($category);

        DB::table('archives')->where('id', $content->id)->update([
            'delete_time' => time(),
            'update_time' => time(),
        ]);

        return redirect()
            ->route('admin.contents.index', ['category_id' => $category->id, 'model_id' => $content->model_id])
            ->with('status', '内容已移入回收状态');
    }

    private function availableCategories(string $lang)
    {
        $query = Category::query()
            ->active()
            ->with('modelDef')
            ->where('lang', $lang)
            ->where('model_id', '>', 0)
            ->orderBy('weigh')
            ->orderBy('id');

        $allowedIds = $this->auth->accessibleCategoryIds();
        if (is_array($allowedIds)) {
            $query->whereIn('id', $allowedIds ?: [0]);
        }

        return $query->get();
    }

    private function queryRows(ContentModel $model, array $categoryIds): array
    {
        $columns = ['a.*', 'c.title as category_title', 'u.username'];
        $query = DB::table('archives as a')
            ->leftJoin('admin as u', 'a.admin_id', '=', 'u.id')
            ->leftJoin('category as c', 'a.category_id', '=', 'c.id')
            ->whereIn('a.category_id', $categoryIds)
            ->where('a.model_id', $model->id)
            ->whereNull('a.delete_time')
            ->orderByDesc('a.id');

        if ($model->tablename && Schema::hasTable($model->tablename)) {
            $query->leftJoin($model->tablename.' as more', 'a.id', '=', 'more.id');
            foreach (Schema::getColumnListing($model->tablename) as $column) {
                if ($column === 'id' || $column === 'content') {
                    continue;
                }
                $columns[] = 'more.'.$column;
            }
        }

        $rows = $query->get($columns);

        return $rows->map(function ($row): array {
            $data = (array) $row;
            $data['publish_time_text'] = ! empty($data['publish_time']) ? date('Y-m-d H:i:s', (int) $data['publish_time']) : '';
            return $data;
        })->all();
    }

    private function resolveContext(Request $request, bool $withCategories = true): array
    {
        $categoryId = (int) $request->query('category_id', $request->input('category_id', 0));
        $modelId = (int) $request->query('model_id', $request->input('model_id', 0));

        abort_if($categoryId <= 0 || $modelId <= 0, 404);

        $category = Category::query()->findOrFail($categoryId);
        $this->ensureCategoryAccess($category);
        $model = ContentModel::query()->findOrFail($modelId);
        abort_if((int) $category->model_id !== (int) $model->id, 404);

        if (! $withCategories) {
            return [$category, $model];
        }

        return [$category, $model, $this->availableCategories($category->lang)];
    }

    private function ensureCategoryAccess(Category $category): void
    {
        $allowed = $this->auth->accessibleCategoryIds();

        if (is_array($allowed) && ! in_array((int) $category->id, $allowed, true)) {
            abort(403, '没有权限操作该栏目');
        }
    }

    private function fieldDefinitions(ContentModel $model): Collection
    {
        return ModelField::query()
            ->active()
            ->where('model_id', $model->id)
            ->orderBy('weigh')
            ->get()
            ->filter(function (ModelField $field) use ($model): bool {
                if ($field->field_name === 'id') {
                    return false;
                }

                if ($field->iscore == 1) {
                    return Schema::hasColumn('archives', $field->field_name);
                }

                return $model->tablename && Schema::hasTable($model->tablename) && Schema::hasColumn($model->tablename, $field->field_name);
            })
            ->values();
    }

    private function extractPayload(Request $request, ContentModel $model, Category $category, ?Archive $content = null): array
    {
        $fields = $this->fieldDefinitions($model);
        $mainData = [
            'model_id' => $model->id,
            'category_id' => (int) $request->input('category_id', $category->id),
            'admin_id' => $this->auth->user()?->id ?? 0,
            'style' => (string) $request->input('style', ''),
            'category_ids' => is_array($request->input('category_ids')) ? implode(',', $request->input('category_ids')) : (string) $request->input('category_ids', ''),
            'lang' => $category->lang,
            'update_time' => time(),
        ];
        $viceData = [];

        if (! $content) {
            $mainData['create_time'] = time();
        }

        foreach ($fields as $field) {
            $value = $request->input($field->field_name);

            if ($field->field_name === 'publish_time') {
                $value = $value ? strtotime((string) $value) : time();
            } elseif (in_array($field->form_type, ['checkbox', 'selects'], true) && is_array($value)) {
                $value = implode(',', $value);
            } elseif ($field->form_type === 'editor' && is_string($value)) {
                $value = $value;
            } elseif ($value === null) {
                $value = $field->default_value;
            }

            if ($field->field_name === 'diyname' && is_string($value) && $value !== '' && ! preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
                throw ValidationException::withMessages([
                    'diyname' => '自定义 URL 名只支持字母、数字、下划线和中划线',
                ]);
            }

            if ((int) $field->iscore === 1) {
                $mainData[$field->field_name] = $value;
            } else {
                $viceData[$field->field_name] = $value;
            }
        }

        $mainData['title'] = (string) ($mainData['title'] ?? '');
        if ($mainData['title'] === '') {
            throw ValidationException::withMessages([
                'title' => '标题不能为空',
            ]);
        }

        $mainData['show_tpl'] = (string) ($mainData['show_tpl'] ?? 'show.html');
        $mainData['status'] = (string) ($mainData['status'] ?? 'normal');
        $mainData['publish_time'] = (int) ($mainData['publish_time'] ?? time());

        return [$mainData, $viceData];
    }
}
