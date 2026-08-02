@extends('admin.layout')

@section('content')
    <section class="card">
        <form method="post" action="{{ $category ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
            @csrf
            @if($category)
                @method('PUT')
            @endif

            <input type="hidden" name="lang" value="{{ $lang }}">

            <div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                <div>
                    <label>栏目标题</label>
                    <input name="title" value="{{ old('title', $category?->title) }}" required style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>目录名</label>
                    <input name="name" value="{{ old('name', $category?->name) }}" placeholder="为空时自动生成" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>栏目类型</label>
                    <select name="type" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        @foreach(['category' => '栏目封面', 'list' => '内容列表', 'link' => '单页/链接'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', $category?->type ?? 'list') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>所属模型</label>
                    <select name="model_id" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        <option value="0">无</option>
                        @foreach($models as $model)
                            <option value="{{ $model->id }}" @selected((int) old('model_id', $category?->model_id) === (int) $model->id)>{{ $model->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>父级栏目</label>
                    <select name="parent_id" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        <option value="0">作为一级栏目</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent['id'] }}" @selected((int) old('parent_id', $category?->parent_id) === (int) $parent['id'])>
                                {{ str_repeat(' - ', $parent['level']) }}{{ $parent['title'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>外部链接</label>
                    <input name="url" value="{{ old('url', $category?->url) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>栏目模板</label>
                    <input name="category_tpl" value="{{ old('category_tpl', $category?->category_tpl) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>列表模板</label>
                    <input name="list_tpl" value="{{ old('list_tpl', $category?->list_tpl) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>内容模板</label>
                    <input name="show_tpl" value="{{ old('show_tpl', $category?->show_tpl) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>SEO 标题</label>
                    <input name="seo_title" value="{{ old('seo_title', $category?->seo_title) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>SEO 关键词</label>
                    <input name="seo_keywords" value="{{ old('seo_keywords', $category?->seo_keywords) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>SEO 描述</label>
                    <input name="seo_desc" value="{{ old('seo_desc', $category?->seo_desc) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>排序</label>
                    <input name="weigh" type="number" value="{{ old('weigh', $category?->weigh ?? 0) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>分页大小</label>
                    <input name="num" type="number" value="{{ old('num', $category?->num ?? 10) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>导航显示</label>
                    <select name="ismenu" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        <option value="1" @selected((string) old('ismenu', $category?->ismenu ?? '1') === '1')>显示</option>
                        <option value="0" @selected((string) old('ismenu', $category?->ismenu ?? '1') === '0')>隐藏</option>
                    </select>
                </div>
                <div>
                    <label>用户投稿</label>
                    <select name="user_auth" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        <option value="0" @selected((string) old('user_auth', $category?->user_auth ?? '0') === '0')>关闭</option>
                        <option value="1" @selected((string) old('user_auth', $category?->user_auth ?? '0') === '1')>开启</option>
                    </select>
                </div>
                <div>
                    <label>状态</label>
                    <select name="status" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        <option value="normal" @selected(old('status', $category?->status ?? 'normal') === 'normal')>正常</option>
                        <option value="hidden" @selected(old('status', $category?->status) === 'hidden')>隐藏</option>
                    </select>
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="submit" class="btn">{{ $category ? '保存修改' : '创建栏目' }}</button>
                <a href="{{ route('admin.categories.index', ['clang' => $lang]) }}" class="btn btn-secondary">返回列表</a>
            </div>
        </form>
    </section>
@endsection
