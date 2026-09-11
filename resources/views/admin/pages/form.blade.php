@extends('admin.layout')

@section('title', $page->exists ? '编辑页面' : '新建页面')

@section('content')
    <form method="post" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
        @csrf
        @if($page->exists) @method('PUT') @endif

        @include('admin.partials.locale-tabs', [
            'locales' => $locales,
            'model' => $page,
            'fields' => [
                'title' => ['label' => '标题'],
                'slug' => ['label' => 'Slug'],
                'content' => ['label' => '正文', 'type' => 'textarea'],
                'meta_title' => ['label' => 'SEO 标题'],
                'meta_description' => ['label' => 'SEO 描述', 'type' => 'textarea'],
            ],
        ])

        <label>状态</label>
        <select name="status">
            <option value="draft" @selected(old('status', $page->status) === 'draft')>草稿</option>
            <option value="published" @selected(old('status', $page->status) === 'published')>已发布</option>
        </select>

        <label>排序</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $page->sort_order ?? 0) }}">

        <label><input type="checkbox" name="is_homepage" value="1" @checked(old('is_homepage', $page->is_homepage))> 设为首页</label>
        <input type="hidden" name="parent_id" value="{{ old('parent_id', $page->parent_id) }}">

        <p style="margin-top:16px;">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.pages.index') }}">返回</a>
        </p>
    </form>
@endsection
