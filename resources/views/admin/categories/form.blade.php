@extends('admin.layout')
@section('title', $category->exists ? '编辑栏目' : '新建栏目')
@section('content')
    <form method="post" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
        @csrf
        @if($category->exists) @method('PUT') @endif

        @include('admin.partials.locale-tabs', [
            'locales' => $locales,
            'model' => $category,
            'fields' => [
                'title' => ['label' => '标题'],
                'slug' => ['label' => 'Slug'],
                'meta_title' => ['label' => 'SEO 标题'],
                'meta_description' => ['label' => 'SEO 描述', 'type' => 'textarea'],
            ],
        ])

        <label>类型</label>
        <select name="type">
            @foreach(['hub'=>'频道','list'=>'列表','single'=>'单页','link'=>'外链'] as $k=>$label)
                <option value="{{ $k }}" @selected(old('type', $category->type ?? 'list') === $k)>{{ $label }}</option>
            @endforeach
        </select>

        <label>绑定模型</label>
        <select name="model_id">
            <option value="">—</option>
            @foreach($models as $model)
                <option value="{{ $model->id }}" @selected(old('model_id', $category->model_id) == $model->id)>{{ $model->name }}</option>
            @endforeach
        </select>

        <label>父栏目 ID</label>
        <input type="number" name="parent_id" value="{{ old('parent_id', $category->parent_id) }}">
        <label>列表模板（如 list.list_product）</label>
        <input type="text" name="list_tpl" value="{{ old('list_tpl', $category->list_tpl) }}">
        <label>详情模板</label>
        <input type="text" name="show_tpl" value="{{ old('show_tpl', $category->show_tpl) }}">
        <label>频道模板</label>
        <input type="text" name="category_tpl" value="{{ old('category_tpl', $category->category_tpl) }}">
        <label>外链 URL（type=link）</label>
        <input type="text" name="url" value="{{ old('url', $category->getAttributes()['url'] ?? '') }}">
        <label>分页条数</label>
        <input type="number" name="page_size" value="{{ old('page_size', $category->page_size ?? 10) }}">
        <label>排序</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
        <label><input type="checkbox" name="is_menu" value="1" @checked(old('is_menu', $category->is_menu ?? true))> 显示在导航</label>
        <label>状态</label>
        <select name="status">
            <option value="published" @selected(old('status', $category->status) === 'published')>已发布</option>
            <option value="draft" @selected(old('status', $category->status) === 'draft')>草稿</option>
        </select>

        <p style="margin-top:16px;">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.categories.index') }}">返回</a>
        </p>
    </form>
@endsection
