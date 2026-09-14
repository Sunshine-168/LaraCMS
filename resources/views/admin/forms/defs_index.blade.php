@extends('admin.layout')
@section('title', '表单定义')
@section('icon', 'clipboard-list')
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.form-submissions.index') }}"><i class="fas fa-inbox"></i> 留言收件箱</a>
    <a class="btn btn-sm" href="{{ route('admin.forms.create') }}"><i class="fas fa-plus"></i> 新建表单</a>
@endsection
@section('content')
    <div class="entry-index form-index">
        @if($forms->isEmpty())
            <div class="list-empty">
                <p>还没有表单。</p>
                <p class="muted">联系、报名这类前台表单在这里建。主题用标识码调用，提交的内容进留言收件箱。</p>
                <a class="btn" href="{{ route('admin.forms.create') }}">新建表单</a>
            </div>
        @else
            <p class="muted recycle-lead">点名称去改字段。标识码给主题用，例如 @@cmsForm(['slug' => 'contact'])。</p>
            <table class="data">
                <thead>
                <tr>
                    <th>表单</th>
                    <th style="width:120px">字段</th>
                    <th style="width:140px">留言</th>
                    <th class="col-actions">操作</th>
                </tr>
                </thead>
                <tbody>
                @foreach($forms as $form)
                    <tr @class(['is-off' => ! $form->is_active])>
                        <td>
                            <div class="entry-row-title-line">
                                <a class="entry-row-title" href="{{ route('admin.forms.edit', $form) }}">{{ $form->name }}</a>
                                @unless($form->is_active)
                                    <span class="badge badge-off">停用</span>
                                @endunless
                            </div>
                            <div class="entry-row-meta">{{ $form->slug }}</div>
                        </td>
                        <td>
                            <a href="{{ route('admin.forms.edit', $form) }}">{{ $form->fieldCount() }}</a>
                        </td>
                        <td>
                            <a href="{{ route('admin.form-submissions.index', ['form_id' => $form->id]) }}">{{ $form->inboxCount() }}</a>
                        </td>
                        <td class="actions">
                            <form action="{{ route('admin.forms.toggle', $form) }}" method="post">
                                @csrf
                                <button class="btn btn-sm btn-muted" type="submit">{{ $form->is_active ? '停用' : '启用' }}</button>
                            </form>
                            <form action="{{ route('admin.forms.destroy', $form) }}" method="post" onsubmit="return confirm(@json(($form->submissions_count ?? 0) > 0 ? '确定删除「'.$form->name.'」？其中 '.$form->submissions_count.' 条留言也会删掉。' : '确定删除「'.$form->name.'」？'))">
                                @csrf @method('DELETE')
                                <button class="btn-quiet" type="submit">删除</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="muted entry-index-count">共 {{ $forms->total() }} 个</p>
            {{ $forms->links() }}
        @endif
    </div>
@endsection
