@extends('admin.layout')
@section('title', '内容管理')
@section('icon', 'file-alt')
@section('content')
    <div class="toolbar">
        @foreach($models as $m)
            <a class="btn {{ (int)$modelId === (int)$m->id ? '' : 'btn-muted' }}" href="{{ route('admin.entries.index', ['model_id' => $m->id, 'status' => $status ?? null]) }}">{{ $m->name }}</a>
        @endforeach
        <a class="btn {{ ($status ?? '') === 'pending' ? '' : 'btn-muted' }}" href="{{ route('admin.entries.index', array_filter(['model_id' => $modelId, 'status' => 'pending'])) }}">待审{{ ($pendingCount ?? 0) > 0 ? ' '.$pendingCount : '' }}</a>
        <a class="btn {{ ($status ?? '') === '' ? '' : 'btn-muted' }}" href="{{ route('admin.entries.index', array_filter(['model_id' => $modelId])) }}">全部</a>
        <span class="spacer"></span>
        <a class="btn btn-muted" href="{{ route('admin.recycle.index') }}"><i class="fas fa-trash-alt"></i> 回收站</a>
        @if($models->isNotEmpty())
            <a class="btn" href="{{ route('admin.entries.create', ['model_id' => $modelId ?: $models->first()->id]) }}"><i class="fas fa-plus"></i> 新建</a>
        @endif
    </div>
    <form method="post" action="{{ route('admin.entries.batch') }}" id="batchForm">
        @csrf
        <input type="hidden" name="model_id" value="{{ $modelId }}">
        <div class="toolbar" style="flex-wrap:wrap;gap:8px">
            <select name="action" id="batchAction" style="max-width:160px">
                @if($canPublish ?? auth('admin')->user()?->canPerm('publish'))
                    <option value="publish">批量发布</option>
                @endif
                <option value="pending">批量待审</option>
                <option value="draft">批量草稿</option>
                <option value="move">批量移动栏目</option>
                <option value="copy">批量复制</option>
                <option value="push">推送推荐位</option>
                <option value="delete">批量删除</option>
            </select>
            <select name="category_id" id="batchCategory" style="max-width:180px">
                <option value="">目标栏目</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->t('title') }}</option>
                @endforeach
            </select>
            <select name="recommend_id" id="batchRecommend" style="max-width:180px">
                <option value="">推荐位</option>
                @foreach($recommends as $r)
                    <option value="{{ $r->id }}">{{ $r->title }}</option>
                @endforeach
            </select>
            <button class="btn btn-muted" type="submit" onclick="return confirm('确认对所选内容执行操作？')">执行</button>
        </div>
    </form>
        <table class="data">
            <thead><tr><th style="width:36px"><input type="checkbox" id="checkAll"></th><th>ID</th><th>标题</th><th>模型</th><th>栏目</th><th>投稿</th><th>Flags</th><th>状态</th><th class="col-actions">操作</th></tr></thead>
            <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td><input type="checkbox" form="batchForm" name="ids[]" value="{{ $entry->id }}" class="row-check"></td>
                    <td>{{ $entry->id }}</td>
                    <td>{{ $entry->t('title') }}</td>
                    <td>{{ $entry->modelDef?->name }}</td>
                    <td>{{ $entry->category?->t('title') }}</td>
                    <td class="muted">{{ $entry->member?->name ?: '—' }}</td>
                    <td class="muted">{{ $entry->flags ?: '—' }}</td>
                    <td>
                        @if($entry->isPending())
                            <span class="badge badge-warn">待审</span>
                        @elseif($entry->isScheduled())
                            <span class="badge badge-warn">定时 {{ $entry->published_at->format('Y-m-d H:i') }}</span>
                        @elseif($entry->status === 'published')
                            <span class="badge badge-ok">已发布</span>
                        @else
                            <span class="badge badge-off">{{ \App\Models\Cms\Entry::statusLabel($entry->status) }}</span>
                        @endif
                    </td>
                    <td class="actions">
                        <a class="btn btn-sm btn-muted" href="{{ route('admin.entries.edit', $entry) }}">编辑</a>
                        <form action="{{ route('admin.entries.destroy', $entry) }}" method="post" onsubmit="return confirm('移入回收站？')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" type="submit">删除</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="muted">暂无内容</td></tr>
            @endforelse
            </tbody>
        </table>
    {{ $entries->appends(request()->query())->links() }}
@endsection
@push('scripts')
<script>
document.getElementById('checkAll')?.addEventListener('change', function () {
    document.querySelectorAll('.row-check').forEach(function (c) { c.checked = document.getElementById('checkAll').checked; });
});
</script>
@endpush
