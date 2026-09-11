@extends('admin.layout')
@section('title', $tag->exists ? '编辑标签' : '新建标签')
@section('icon', 'tags')
@section('content')
    <form method="post" action="{{ $tag->exists ? route('admin.tags.update', $tag) : route('admin.tags.store') }}">
        @csrf
        @if($tag->exists) @method('PUT') @endif
        @include('admin.partials.locale-tabs', [
            'locales' => \App\Support\Locale::codes(),
            'model' => $tag,
            'fields' => [
                'title' => ['label' => '标题'],
                'slug' => ['label' => 'Slug（可空自动生成）'],
            ],
        ])
        <div class="form-actions">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.tags.index') }}">返回</a>
        </div>
    </form>
@endsection
