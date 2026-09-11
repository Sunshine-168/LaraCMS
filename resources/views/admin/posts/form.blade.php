@extends('admin.layout')

@section('title', $post->exists ? '编辑文章' : '新建文章')

@section('content')
    <form method="post" action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}">
        @csrf
        @if($post->exists) @method('PUT') @endif

        @include('admin.partials.locale-tabs', [
            'locales' => $locales,
            'model' => $post,
            'fields' => [
                'title' => ['label' => '标题'],
                'slug' => ['label' => 'Slug'],
                'excerpt' => ['label' => '摘要', 'type' => 'textarea'],
                'content' => ['label' => '正文', 'type' => 'textarea'],
                'meta_title' => ['label' => 'SEO 标题'],
                'meta_description' => ['label' => 'SEO 描述', 'type' => 'textarea'],
            ],
        ])

        <label>分类</label>
        <select name="category_id">
            <option value="">—</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $post->category_id) == $category->id)>
                    {{ $category->t('title') }}
                </option>
            @endforeach
        </select>

        <label>封面 URL</label>
        <input type="text" name="cover" value="{{ old('cover', $post->cover) }}">

        <label>状态</label>
        <select name="status">
            <option value="draft" @selected(old('status', $post->status) === 'draft')>草稿</option>
            <option value="published" @selected(old('status', $post->status) === 'published')>已发布</option>
        </select>

        <p style="margin-top:16px;">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.posts.index') }}">返回</a>
        </p>
    </form>
@endsection
