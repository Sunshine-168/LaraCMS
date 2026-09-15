@extends('admin.layout')
@section('title', '站点管理')
@section('icon', 'globe')
@php
    $currentId = (int) ($currentId ?? 0);
    $composeFocus = session('site_quick_added') || $sites->isEmpty();
@endphp
@section('content')
    <div class="entry-index site-index">
        <div class="site-compose">
            <form method="post" action="{{ route('admin.sites.store') }}">
                @csrf
                <input type="hidden" name="is_active" value="1">
                <label class="site-compose-label" for="site-quick-name">新增站点</label>
                <div class="site-compose-row">
                    <input id="site-quick-name" type="text" name="name" value="" placeholder="名称，如 英文站" aria-label="站点名称" required @if($composeFocus) autofocus @endif>
                    <input type="text" name="domain" value="" placeholder="域名，如 www.example.com" aria-label="域名" required>
                    <button class="btn" type="submit">添加</button>
                </div>
                <p class="muted field-hint">访客用这个域名打开。添加后栏目是空的，可在顶栏切过去。需要改主题或备注时，<a href="{{ route('admin.sites.create') }}">打开完整表单</a>。</p>
            </form>
        </div>

        @if($sites->isEmpty())
            <div class="list-empty">
                <p>还没有站点。</p>
                <p class="muted">在上方填写名称和域名即可添加。</p>
            </div>
        @else
            <p class="muted recycle-lead">点名称去改域名或主题。顶栏切换正在改哪一个。删掉站点时，这个站的内容会一起删掉；默认站不能删。</p>
            <table class="data">
                <thead>
                <tr>
                    <th>站点</th>
                    <th class="col-actions">操作</th>
                </tr>
                </thead>
                <tbody>
                @foreach($sites as $site)
                    <tr @class(['is-off' => ! $site->is_active])>
                        <td>
                            <div class="entry-row-title-line">
                                <a class="entry-row-title" href="{{ route('admin.sites.edit', $site) }}">{{ $site->name ?: '未命名' }}</a>
                                @if($site->is_default)
                                    <span class="badge">默认</span>
                                @endif
                                @if($currentId === (int) $site->id)
                                    <span class="badge badge-ok">正在改</span>
                                @endif
                                @unless($site->is_active)
                                    <span class="badge badge-off">停用</span>
                                @endunless
                            </div>
                            <div class="entry-row-meta">
                                {{ $site->domain }}
                                @if($site->theme)
                                    · 外观 {{ \App\Models\Cms\Site::themeLabel($site->theme) }}
                                @endif
                                @if($site->remark)
                                    · {{ $site->remark }}
                                @endif
                            </div>
                        </td>
                        <td class="actions">
                            @if($currentId !== (int) $site->id && $site->is_active)
                                <form action="{{ route('admin.site.switch') }}" method="post">
                                    @csrf
                                    <input type="hidden" name="site_id" value="{{ $site->id }}">
                                    <button class="btn btn-sm" type="submit">去编辑</button>
                                </form>
                            @endif
                            @unless($site->is_default)
                                <form action="{{ route('admin.sites.default', $site) }}" method="post">
                                    @csrf
                                    <button class="btn btn-sm btn-muted" type="submit">设为默认</button>
                                </form>
                                <form action="{{ route('admin.sites.toggle', $site) }}" method="post">
                                    @csrf
                                    <button class="btn btn-sm btn-muted" type="submit">{{ $site->is_active ? '停用' : '启用' }}</button>
                                </form>
                                <form action="{{ route('admin.sites.destroy', $site) }}" method="post" onsubmit="return confirm(@json('确定删除「'.($site->name ?: $site->domain).'」？这个站的栏目、内容和会员都会删掉，不能恢复。'))">
                                    @csrf @method('DELETE')
                                    <button class="btn-quiet" type="submit">删除</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="muted entry-index-count">共 {{ $sites->total() }} 个</p>
            {{ $sites->links() }}
        @endif
    </div>
@endsection
