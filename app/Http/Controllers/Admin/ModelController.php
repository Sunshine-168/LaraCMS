<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\ContentModel;
use App\Models\Cms\ModelField;
use App\Services\Admin\ContentModelService;
use App\Services\Admin\FieldBindService;
use App\Services\Admin\ModelFieldService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ModelController extends Controller
{
    public function __construct(
        private readonly ModelFieldService $fieldService,
        private readonly ContentModelService $modelService,
        private readonly FieldBindService $fieldBind,
    ) {
    }

    /** 内容模型与字段 */
    public function index(): View
    {
        return view('admin.models.index', [
            'models' => ContentModel::query()->with(['fields.categories'])->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.models.form', [
            'model' => new ContentModel([
                'kind' => 'list',
                'list_tpl' => 'list.list',
                'show_tpl' => 'show.show',
                'is_active' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:64',
        ]);
        $result = $this->modelService->save(null, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.models.index')->with('status', $result['msg']);
    }

    public function edit(ContentModel $model): View
    {
        return view('admin.models.form', compact('model'));
    }

    public function update(Request $request, ContentModel $model): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:64',
        ]);
        // 创建后不允许改 slug（表单 readonly，仍以原值为准）
        $input = $request->all();
        $input['slug'] = $model->slug;
        $result = $this->modelService->save($model, $input);
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.models.index')->with('status', $result['msg']);
    }

    public function destroy(ContentModel $model): RedirectResponse
    {
        $result = $this->modelService->delete($model);

        return redirect()->route('admin.models.index')->with(
            $result['ok'] ? 'status' : 'error',
            $result['msg']
        );
    }

    public function copy(ContentModel $model): RedirectResponse
    {
        $result = $this->modelService->copy($model);

        return redirect()->route('admin.models.index')->with(
            $result['ok'] ? 'status' : 'error',
            $result['msg']
        );
    }

    public function export(ContentModel $model): Response
    {
        $payload = $this->modelService->exportPayload($model);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return response($json, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="cms-model-'.$model->slug.'.json"',
        ]);
    }

    public function importForm(): View
    {
        return view('admin.models.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:json,txt|max:2048',
        ]);
        $raw = file_get_contents($request->file('file')->getRealPath() ?: '');
        $payload = json_decode((string) $raw, true);
        if (! is_array($payload)) {
            return back()->with('error', '无法解析 JSON');
        }
        $result = $this->modelService->importPayload($payload, $request->boolean('overwrite', true));

        return redirect()->route('admin.models.index')->with(
            $result['ok'] ? 'status' : 'error',
            $result['msg']
        );
    }

    /** 新建字段 */
    public function createField(ContentModel $model): View
    {
        return view('admin.models.field_form', [
            'model' => $model,
            'field' => new ModelField(['model_id' => $model->id, 'type' => 'text']),
            'bindCategories' => $this->fieldBind->categoriesForModel($model->id),
        ]);
    }

    /** 保存新字段 */
    public function storeField(Request $request, ContentModel $model): RedirectResponse
    {
        $result = $this->fieldService->save($model, null, $request->all());

        return redirect()->route('admin.models.index')->with('status', $result['msg']);
    }

    /** 编辑字段 */
    public function editField(ContentModel $model, ModelField $field): View
    {
        abort_unless((int) $field->model_id === (int) $model->id, 404);
        $field->load('categories');

        return view('admin.models.field_form', [
            'model' => $model,
            'field' => $field,
            'bindCategories' => $this->fieldBind->categoriesForModel($model->id),
        ]);
    }

    /** 更新字段 */
    public function updateField(Request $request, ContentModel $model, ModelField $field): RedirectResponse
    {
        abort_unless((int) $field->model_id === (int) $model->id, 404);
        $result = $this->fieldService->save($model, $field, $request->all());

        return redirect()->route('admin.models.index')->with('status', $result['msg']);
    }

    /** 删除字段 */
    public function destroyField(ModelField $field): RedirectResponse
    {
        $result = $this->fieldService->delete($field);

        return redirect()->route('admin.models.index')->with('status', $result['msg']);
    }
}
