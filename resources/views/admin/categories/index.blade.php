@extends('admin.layout')
@section('title', '栏目管理')
@section('icon', 'sitemap')
@section('header_actions')
    <a class="btn btn-sm" href="{{ route('admin.categories.create') }}"><i class="fas fa-plus"></i> 新建栏目</a>
@endsection
@section('content')
    <div class="entry-index cat-index">
        @if($categories->isEmpty())
            <div class="list-empty">
                <p>还没有栏目。</p>
                <p class="muted">栏目是内容的目录。先建一级栏目，再在它下面「添加下级」做多级频道。</p>
                <a class="btn" href="{{ route('admin.categories.create') }}">新建栏目</a>
            </div>
        @else
            <p class="muted recycle-lead">下级会缩进显示。点「添加下级」挂到这一栏下面；删除前请先移走子栏目和内容。</p>
            <table class="data">
                <thead>
                <tr>
                    <th>栏目</th>
                    <th style="width:88px">类型</th>
                    <th style="width:110px">模型</th>
                    <th style="width:88px">导航</th>
                    <th class="col-actions">操作</th>
                </tr>
                </thead>
                <tbody>
                @foreach($categories as $category)
                    @php $depth = (int) ($category->depth ?? 0); @endphp
                    <tr>
                        <td>
                            <div class="cat-cell" style="padding-left: {{ $depth * 22 }}px">
                                @if($depth > 0)
                                    <span class="cat-branch" aria-hidden="true">└</span>
                                @endif
                                <div>
                                    <a class="entry-row-title" href="{{ route('admin.categories.edit', $category) }}">{{ $category->t('title') ?: '无标题' }}</a>
                                    <div class="entry-row-meta">
                                        /{{ $category->t('slug') }}
                                        · #{{ $category->id }}
                                        @if(($category->entries_count ?? 0) > 0)
                                            · <a href="{{ route('admin.entries.index', array_filter(['model_id' => $category->model_id, 'category_id' => $category->id])) }}">{{ $category->entries_count }} 篇</a>
                                        @endif
                                        @if($category->status !== 'published')
                                            · 草稿
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge">{{ \App\Models\Cms\Category::typeLabel($category->type) }}</span></td>
                        <td>{{ $category->modelDef?->name ?: '—' }}</td>
                        <td>
                            @if($category->is_menu)
                                <span class="badge badge-search">导航</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td class="actions">
                            @if($category->status === 'published')
                                <a class="btn btn-sm btn-muted" href="{{ url($category->frontUrl()) }}" target="_blank" rel="noopener">前台</a>
                            @endif
                            <a class="btn btn-sm btn-muted" href="{{ route('admin.categories.create', ['parent_id' => $category->id]) }}">添加下级</a>
                            <a class="btn btn-sm btn-muted" href="{{ route('admin.categories.edit', $category) }}">编辑</a>
                            <form action="{{ route('admin.categories.destroy', $category) }}" method="post" onsubmit="return confirm(@json('删除「'.($category->t('title') ?: '无标题').'」？有下级或内容时无法删除。'))">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">删除</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
