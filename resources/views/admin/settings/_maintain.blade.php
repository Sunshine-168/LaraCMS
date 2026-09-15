@php
    $backups = $backups ?? [];
    $sizeLabel = function (int $bytes): string {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        return number_format(max($bytes, 0) / 1024, 1).' KB';
    };
@endphp
<div class="html-cache-card">
    <h3>备份</h3>
    <p class="muted field-hint">把文章、栏目和上传的图片打成一份，最多留最近 5 份。不含服务器密码配置。</p>
    <div class="html-cache-actions">
        <form method="post" action="{{ route('admin.cache.backup') }}">
            @csrf
            <button class="btn" type="submit">立即备份</button>
        </form>
    </div>
    @if($backups === [])
        <p class="muted field-hint" style="margin-top:12px">还没有备份。</p>
    @else
        <table class="data tools-backups">
            <thead>
            <tr>
                <th>备份</th>
                <th class="col-actions">操作</th>
            </tr>
            </thead>
            <tbody>
            @foreach($backups as $b)
                <tr>
                    <td>
                        <div class="entry-row-title-line">{{ date('Y-m-d H:i', $b['mtime'] ?? 0) }}</div>
                        <div class="entry-row-meta">{{ $sizeLabel((int) ($b['size'] ?? 0)) }}</div>
                    </td>
                    <td class="actions">
                        <a href="{{ route('admin.cache.backup.download', $b['file']) }}">下载</a>
                        <form method="post" action="{{ route('admin.cache.backup.restore', $b['file']) }}" onsubmit="return confirm('会换成这份备份里的内容和文件，当前数据会被覆盖。确定？')">
                            @csrf
                            <button class="btn-quiet" type="submit">还原</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="html-cache-card">
    <h3>演示数据</h3>
    <p class="muted field-hint">写入示例栏目、文章和账号，方便试用。已经有的示例会被更新。正式上线的站点不要点。</p>
    <div class="html-cache-actions">
        <form method="post" action="{{ route('admin.cache.seed-demo') }}" onsubmit="return confirm('会更新示例栏目和文章，正式站点请不要用。确定？')">
            @csrf
            <button class="btn btn-muted" type="submit">导入演示数据</button>
        </form>
    </div>
</div>
