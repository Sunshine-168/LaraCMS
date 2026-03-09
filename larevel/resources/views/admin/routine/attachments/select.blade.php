@extends('admin.layout')

@section('content')
    <section class="card" style="margin-bottom:16px;">
        <form method="get" action="{{ route('admin.attachments.select') }}" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
            <input type="hidden" name="multiple" value="{{ $multiple }}">
            <input type="hidden" name="field" value="{{ $field }}">
            <input type="hidden" name="fileNum" value="{{ $fileNum }}">
            <input type="hidden" name="mimetype" value="{{ $mimetype }}">
            <input type="hidden" name="size" value="{{ $size }}">

            <div>
                <label>标题</label>
                <input name="title" value="{{ request('title') }}" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">
            </div>
            <div>
                <label>后缀</label>
                <select name="ext" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                    <option value="">全部</option>
                    @foreach($exts as $ext)
                        <option value="{{ $ext }}" @selected(request('ext') === $ext)>{{ $ext }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn">筛选</button>
            <button type="button" class="btn btn-secondary" onclick="downloadRemoteAttachment()">远程下载</button>
            <button type="button" class="btn" onclick="chooseSelectedAttachments()">确认选择</button>
        </form>
    </section>

    <section class="card">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:50px;">选中</th>
                    <th>ID</th>
                    <th>标题</th>
                    <th>预览</th>
                    <th>上传者</th>
                    <th>大小</th>
                    <th>后缀</th>
                    <th>路径</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr ondblclick="chooseOne('{{ $row->path }}')">
                        <td>
                            <input
                                type="{{ ($multiple === 'true' || $multiple === '1') ? 'checkbox' : 'radio' }}"
                                name="selected_attachment"
                                value="{{ $row->path }}"
                            >
                        </td>
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->title }}</td>
                        <td><a href="{{ $row->previewUrl() }}" target="_blank" class="badge">查看</a></td>
                        <td>{{ $row->adminUser?->username ?: '-' }}</td>
                        <td>{{ $row->sizeText() }}</td>
                        <td>{{ $row->ext }}</td>
                        <td style="max-width:320px;word-break:break-all;">{{ $row->path }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="meta">暂无可选附件。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top:16px;">
            {{ $rows->links() }}
        </div>
    </section>

    <script>
        function selectedPaths() {
            return Array.from(document.querySelectorAll('input[name="selected_attachment"]:checked')).map(function (item) {
                return item.value;
            });
        }

        function applyAttachmentSelection(paths) {
            const fileNum = Number(@json($fileNum ?: 10));
            if (paths.length === 0) {
                alert('请选择附件');
                return;
            }
            if (paths.length > fileNum) {
                alert('超出可选文件数量限制');
                return;
            }

            try {
                if (window.parent && window.parent !== window && window.parent.layer) {
                    const index = window.parent.layer.getFrameIndex(window.name);
                    const callback = window.parent.$('#layui-layer' + index).data('callback');
                    if (typeof callback === 'function') {
                        if (callback(paths) === false) {
                            return;
                        }
                    }
                    window.parent.layer.close(index);
                    return;
                }
            } catch (e) {}

            if (window.opener && !window.opener.closed) {
                window.opener.postMessage({ type: 'attachment:selected', paths: paths }, '*');
                window.close();
            }
        }

        function chooseSelectedAttachments() {
            applyAttachmentSelection(selectedPaths());
        }

        function chooseOne(path) {
            applyAttachmentSelection([path]);
        }

        async function downloadRemoteAttachment() {
            const url = prompt('请输入远程附件地址');
            if (!url) return;

            const response = await fetch(@json(route('admin.attachments.download')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ url: url })
            });

            const result = await response.json();
            if (result.code === 200) {
                location.reload();
                return;
            }

            alert(result.msg || '远程下载失败');
        }
    </script>
@endsection
