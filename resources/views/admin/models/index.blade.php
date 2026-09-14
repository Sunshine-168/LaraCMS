@extends('admin.layout')
@section('title', '内容模型')
@section('icon', 'cube')
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.models.import') }}"><i class="fas fa-file-import"></i> 导入</a>
    <a class="btn btn-sm" href="{{ route('admin.models.create') }}"><i class="fas fa-plus"></i> 新建模型</a>
@endsection
@section('content')
    <div class="entry-index model-index">
        <p class="muted recycle-lead">每种模型是一类内容。标题和正文是自带的，这里只加价格、来源这类扩展字段。删除模型前要先清空它的栏目和文章。</p>

        @forelse($models as $model)
            <div class="model-block">
                <div class="model-head">
                    <div>
                        <div class="entry-row-title-line">
                            <a class="entry-row-title" href="{{ route('admin.models.edit', $model) }}">{{ $model->name }}</a>
                            @unless($model->is_active)
                                <span class="badge badge-off">停用</span>
                            @endunless
                        </div>
                        <div class="entry-row-meta">
                            {{ $model->slug }}
                            · {{ \App\Models\Cms\ContentModel::kindLabel($model->kind) }}
                            ·
                            @if($model->entries_count > 0)
                                <a href="{{ route('admin.entries.index', ['model_id' => $model->id]) }}">{{ $model->entries_count }} 篇</a>
                            @else
                                0 篇
                            @endif
                            · {{ $model->categories_count }} 个栏目
                        </div>
                    </div>
                    <div class="model-head-actions">
                        <a class="btn btn-sm" href="{{ route('admin.models.fields.create', $model) }}">加字段</a>
                        <details class="create-menu">
                            <summary class="btn btn-sm btn-muted">更多</summary>
                            <div class="create-menu-list">
                                <a href="{{ route('admin.models.edit', $model) }}">编辑模型</a>
                                <a href="{{ route('admin.models.export', $model) }}">导出 JSON</a>
                                <form action="{{ route('admin.models.copy', $model) }}" method="post">
                                    @csrf
                                    <button type="submit">复制一份</button>
                                </form>
                                @if($model->entries_count === 0 && $model->categories_count === 0)
                                    <form action="{{ route('admin.models.destroy', $model) }}" method="post" onsubmit="return confirm(@json('确定删除「'.$model->name.'」？扩展字段也会删掉。'))">
                                        @csrf @method('DELETE')
                                        <button class="is-danger" type="submit">删除模型</button>
                                    </form>
                                @endif
                            </div>
                        </details>
                    </div>
                </div>

                @if($model->fields->isEmpty())
                    <p class="muted model-empty-fields">还没有扩展字段。标题、正文是系统自带的。</p>
                @else
                    <table class="data">
                        <thead>
                        <tr>
                            <th>字段</th>
                            <th style="width:120px">类型</th>
                            <th class="col-actions">操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($model->fields as $field)
                            <tr>
                                <td>
                                    <a class="entry-row-title" href="{{ route('admin.models.fields.edit', [$model, $field]) }}">{{ $field->label ?: $field->name }}</a>
                                    <div class="entry-row-meta">{{ $field->name }}</div>
                                    @if($field->is_required || $field->is_filter || $field->is_list || $field->categories->isNotEmpty())
                                        <div class="entry-row-tags">
                                            @if($field->is_required)
                                                <span class="badge">必填</span>
                                            @endif
                                            @if($field->is_filter)
                                                <span class="badge">可筛选</span>
                                            @endif
                                            @if($field->is_list)
                                                <span class="badge">列表显示</span>
                                            @endif
                                            @if($field->categories->isNotEmpty())
                                                <span class="badge">{{ $field->categories->count() }} 个栏目</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td><span class="badge">{{ \App\Models\Cms\ModelField::typeLabel($field->type) }}</span></td>
                                <td class="actions">
                                    <form action="{{ route('admin.models.fields.destroy', $field) }}" method="post" onsubmit="return confirm(@json('删除「'.($field->label ?: $field->name).'」后，写内容页不再出现这项，已填的数据还在。确定？'))">
                                        @csrf @method('DELETE')
                                        <button class="btn-quiet" type="submit">删除</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @empty
            <div class="list-empty">
                <p>还没有内容模型。</p>
                <a class="btn" href="{{ route('admin.models.create') }}">新建模型</a>
            </div>
        @endforelse
    </div>
@endsection
