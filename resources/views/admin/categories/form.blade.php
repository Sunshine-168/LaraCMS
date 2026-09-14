@extends('admin.layout')
@section('title', $category->exists ? '编辑栏目' : ($parent ? '新建下级栏目' : '新建栏目'))
@section('icon', 'sitemap')
@section('content')
    @if($parent)
        <p class="muted recycle-lead">将建在「{{ $parent->t('title') }}」下面。保存后会出现在上级栏目的下一层。</p>
    @endif
    <form method="post" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
        @csrf
        @if($category->exists) @method('PUT') @endif

        @include('admin.partials.locale-tabs', [
            'locales' => $locales,
            'model' => $category,
            'hideCode' => true,
            'fields' => [
                'title' => ['label' => '名称', 'placeholder' => '如 公司新闻'],
                'slug' => ['label' => '网址标识', 'placeholder' => '出现在栏目网址里，可空'],
            ],
            'advanced' => [
                'meta_title' => ['label' => 'SEO 标题'],
                'meta_description' => ['label' => 'SEO 描述', 'type' => 'textarea'],
            ],
        ])

        <label for="cat-parent">上级栏目</label>
        <select name="parent_id" id="cat-parent">
            <option value="">顶级栏目（不挂在任何栏目下）</option>
            @foreach($parents ?? [] as $item)
                <option value="{{ $item->id }}" @selected((int) old('parent_id', $category->parent_id) === (int) $item->id)>
                    {{ str_repeat('— ', (int) ($item->depth ?? 0)) }}{{ $item->t('title') }}
                </option>
            @endforeach
        </select>
        <p class="muted field-hint">选上级即可做多级栏目，例如 新闻中心 → 公司新闻。</p>

        <label>类型</label>
        <select name="type">
            @foreach(['hub'=>'频道（只做目录，下面再挂列表）','list'=>'列表','single'=>'单页','link'=>'外链'] as $k=>$label)
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

        <details class="entry-seo">
            <summary>更多设置</summary>
            <label>列表模板（如 list.list_product）</label>
            <input type="text" name="list_tpl" value="{{ old('list_tpl', $category->list_tpl) }}">
            <label>详情模板</label>
            <input type="text" name="show_tpl" value="{{ old('show_tpl', $category->show_tpl) }}">
            <label>频道模板</label>
            <input type="text" name="category_tpl" value="{{ old('category_tpl', $category->category_tpl) }}">
            <label>外链 URL（类型为外链时填写）</label>
            <input type="text" name="url" value="{{ old('url', $category->getAttributes()['url'] ?? '') }}">
            <label>分页条数</label>
            <input type="number" name="page_size" value="{{ old('page_size', $category->page_size ?? 10) }}">
            <label>同级排序</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
            <p class="muted field-hint">数字越小越靠前。只在同一上级下比较。</p>
        </details>
        <label class="inline"><input type="checkbox" name="is_menu" value="1" @checked(old('is_menu', $category->is_menu ?? true))> 显示在导航</label>
        <label>状态</label>
        <select name="status">
            <option value="published" @selected(old('status', $category->status) === 'published')>已发布</option>
            <option value="draft" @selected(old('status', $category->status) === 'draft')>草稿</option>
        </select>

        <p style="margin-top:16px;">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.categories.index') }}">返回列表</a>
        </p>
    </form>
@endsection
