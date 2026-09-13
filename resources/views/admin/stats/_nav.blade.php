<p class="toolbar">
    <a class="btn {{ ($active ?? '') === 'traffic' ? '' : 'btn-muted' }}" href="{{ route('admin.stats.index') }}">流量</a>
    <a class="btn {{ ($active ?? '') === 'spiders' ? '' : 'btn-muted' }}" href="{{ route('admin.stats.spiders') }}">蜘蛛</a>
    <a class="btn {{ ($active ?? '') === 'logs' ? '' : 'btn-muted' }}" href="{{ route('admin.stats.logs') }}">明细</a>
</p>
