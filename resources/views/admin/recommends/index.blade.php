@extends('admin.layout')
@section('title', '推荐位')
@section('icon', 'th-large')
@section('header_actions')
    <a class="btn btn-sm" href="{{ route('admin.recommends.create') }}"><i class="fas fa-plus"></i> 新建推荐位</a>
@endsection
@section('content')
    <div class="entry-index rec-index">
        @if($recommends->isEmpty())
            <div class="list-empty">
                <p>还没有推荐位。</p>
                <p class="muted">推荐位是首页轮播、合作伙伴这类前台区块。建好后主题用标识码调用。</p>
                <a class="btn" href="{{ route('admin.recommends.create') }}">新建推荐位</a>
            </div>
        @else
            <p class="muted recycle-lead">点名称去改条目或取数规则。标识码给主题用，例如 @@cmsAdv(['name' => 'home_banner'])。</p>
            <table class="data">
                <thead>
                <tr>
                    <th>推荐位</th>
                    <th style="width:110px">类型</th>
                    <th style="width:140px">内容</th>
                    <th class="col-actions">操作</th>
                </tr>
                </thead>
                <tbody>
                @foreach($recommends as $recommend)
                    <tr @class(['is-off' => ! $recommend->is_active])>
                        <td>
                            <div class="entry-row-title-line">
                                <a class="entry-row-title" href="{{ route('admin.recommends.edit', $recommend) }}">{{ $recommend->title ?: $recommend->name }}</a>
                                @unless($recommend->is_active)
                                    <span class="badge badge-off">停用</span>
                                @endunless
                            </div>
                            <div class="entry-row-meta">{{ $recommend->name }}</div>
                        </td>
                        <td><span class="badge">{{ \App\Models\Cms\Recommend::typeLabel($recommend->type) }}</span></td>
                        <td>{{ $recommend->listingCount() }}</td>
                        <td class="actions">
                            <form action="{{ route('admin.recommends.toggle', $recommend) }}" method="post">
                                @csrf
                                <button class="btn btn-sm btn-muted" type="submit">{{ $recommend->is_active ? '停用' : '启用' }}</button>
                            </form>
                            <form action="{{ route('admin.recommends.destroy', $recommend) }}" method="post" onsubmit="return confirm(@json($recommend->type === 'query' ? '确定删除「'.($recommend->title ?: $recommend->name).'」？主题将取不到这个区块。' : '确定删除「'.($recommend->title ?: $recommend->name).'」？'.(($recommend->items_count ?? 0) > 0 ? '其中 '.($recommend->items_count).' 条手工条目也会删掉。' : '')))">
                                @csrf @method('DELETE')
                                <button class="btn-quiet" type="submit">删除</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="muted entry-index-count">共 {{ $recommends->count() }} 个</p>
        @endif
    </div>
@endsection
