{{-- 会员分片上传：挂到 input[type=file] + 隐藏 avatar/url 字段 --}}
@php
    $chunkSize = $chunkSize ?? 1024 * 1024;
    $purpose = $purpose ?? 'avatar';
    $inputId = $inputId ?? 'memberChunkFile';
    $targetInput = $targetInput ?? 'memberAvatar';
    $progressId = $progressId ?? 'memberChunkProgress';
@endphp
<div class="cms-member-chunk" style="margin:12px 0">
    <input type="hidden" id="{{ $inputId }}_csrf" value="{{ csrf_token() }}">
    <input type="file" id="{{ $inputId }}" @if($purpose === 'avatar') accept="image/*" @endif>
    <p id="{{ $progressId }}" class="muted" style="margin:6px 0 0"></p>
</div>
<script>
(function () {
    const fileInput = document.getElementById(@json($inputId));
    const progress = document.getElementById(@json($progressId));
    const target = document.getElementById(@json($targetInput));
    const preview = document.getElementById(@json(($previewId ?? 'memberAvatarPreview')));
    if (!fileInput) return;
    const chunkSize = {{ (int) $chunkSize }};
    const purpose = @json($purpose);
    const csrf = document.getElementById(@json($inputId.'_csrf'))?.value
        || document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';
    const urls = {
        init: @json(cms_route('member.upload.init')),
        chunk: @json(cms_route('member.upload.chunk')),
        complete: @json(cms_route('member.upload.complete')),
    };
    const setMsg = (t) => { if (progress) progress.textContent = t; };
    fileInput.addEventListener('change', async () => {
        const file = fileInput.files && fileInput.files[0];
        if (!file) return;
        const totalChunks = Math.max(1, Math.ceil(file.size / chunkSize));
        setMsg('初始化…');
        try {
            const initRes = await fetch(urls.init, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
                body: JSON.stringify({filename: file.name, total_chunks: totalChunks, total_size: file.size}),
            }).then(r => r.json());
            if (!initRes.ok) throw new Error(initRes.msg || '初始化失败');
            const uploadId = initRes.data.upload_id;
            for (let i = 0; i < totalChunks; i++) {
                const blob = file.slice(i * chunkSize, Math.min(file.size, (i + 1) * chunkSize));
                const fd = new FormData();
                fd.append('upload_id', uploadId);
                fd.append('index', String(i));
                fd.append('chunk', blob, file.name + '.part' + i);
                fd.append('_token', csrf);
                const chunkRes = await fetch(urls.chunk, {method: 'POST', headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'}, body: fd}).then(r => r.json());
                if (!chunkRes.ok) throw new Error(chunkRes.msg || ('分片 ' + i + ' 失败'));
                setMsg('上传中 ' + Math.round(((i + 1) / totalChunks) * 100) + '%');
            }
            const doneRes = await fetch(urls.complete, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
                body: JSON.stringify({upload_id: uploadId, purpose}),
            }).then(r => r.json());
            if (!doneRes.ok) throw new Error(doneRes.msg || '合并失败');
            if (target) target.value = doneRes.data.url || '';
            if (preview && doneRes.data.url) {
                preview.src = doneRes.data.url;
                preview.style.display = '';
            }
            setMsg('上传完成');
        } catch (e) {
            setMsg(e.message || '上传失败');
        }
    });
})();
</script>
