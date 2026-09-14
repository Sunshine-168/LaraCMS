@once
<style>
    .tox-tinymce { max-width: 720px; }
    .entry-main .tox-tinymce { max-width: none; }
    .media-picker-mask {
        display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9998;
        align-items:center; justify-content:center;
    }
    .media-picker-mask.open { display:flex; }
    .media-picker {
        width: min(860px, 94vw); max-height: 80vh; overflow:auto; background:#fff;
        border-radius:6px; padding:16px; box-shadow:0 8px 30px rgba(0,0,0,.2);
    }
    .media-picker-grid {
        display:grid; grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); gap:10px; margin-top:12px;
    }
    .media-picker-grid button {
        border:1px solid #e5e6eb; background:#f7f8fa; padding:6px; border-radius:4px; cursor:pointer;
    }
    .media-picker-grid button:hover { border-color:#007bff; }
    .media-picker-grid img { width:100%; height:80px; object-fit:cover; display:block; border-radius:2px; }
    .cover-row { display:flex; gap:8px; align-items:center; max-width:720px; }
    .cover-row input { flex:1; }
    .images-thumbs { display:flex; flex-wrap:wrap; gap:8px; margin-top:8px; }
    .images-thumb { position:relative; width:88px; height:88px; border:1px solid #e5e6eb; border-radius:4px; overflow:hidden; background:#f7f8fa; }
    .images-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
    .images-thumb button { position:absolute; top:2px; right:2px; border:0; background:rgba(0,0,0,.55); color:#fff; width:20px; height:20px; border-radius:50%; cursor:pointer; line-height:20px; padding:0; }
</style>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
window.CmsEditor = {
    mediaUrl: @json(route('admin.media.json')),
    initEditors: function () {
        if (!window.tinymce) return;
        tinymce.init({
            selector: 'textarea.cms-editor',
            height: 360,
            menubar: false,
            plugins: 'link lists image code table autoresize',
            toolbar: 'undo redo | styles | bold italic underline | bullist numlist | link image | code',
            branding: false,
            convert_urls: false,
            images_upload_handler: null,
            file_picker_types: 'image',
            file_picker_callback: function (callback, value, meta) {
                if (meta.filetype === 'image') {
                    window.CmsEditor.openPicker(function (url) { callback(url, { alt: '' }); });
                }
            }
        });
    },
    openPicker: function (onPick) {
        var mask = document.getElementById('mediaPickerMask');
        var grid = document.getElementById('mediaPickerGrid');
        if (!mask || !grid) return;
        grid.innerHTML = '<p class="muted">加载中…</p>';
        mask.classList.add('open');
        fetch(this.mediaUrl, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (rows) {
                if (!rows.length) {
                    grid.innerHTML = '<p class="muted">媒体库暂无图片，请先到「媒体附件」上传</p>';
                    return;
                }
                grid.innerHTML = '';
                rows.forEach(function (item) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.innerHTML = '<img src="' + item.url + '" alt=""><span class="muted" style="font-size:11px;display:block;margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + (item.filename || '') + '</span>';
                    btn.addEventListener('click', function () {
                        mask.classList.remove('open');
                        onPick(item.url);
                    });
                    grid.appendChild(btn);
                });
            })
            .catch(function () { grid.innerHTML = '<p class="muted">加载失败</p>'; });
    }
};
document.addEventListener('DOMContentLoaded', function () {
    window.CmsEditor.initEditors();
    document.getElementById('mediaPickerClose')?.addEventListener('click', function () {
        document.getElementById('mediaPickerMask')?.classList.remove('open');
    });
    document.querySelectorAll('[data-media-pick]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.querySelector(btn.getAttribute('data-media-pick'));
            if (!target) return;
            window.CmsEditor.openPicker(function (url) {
                target.value = url;
                target.dispatchEvent(new Event('input'));
            });
        });
    });
    document.querySelectorAll('[data-media-pick-append]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.querySelector(btn.getAttribute('data-media-pick-append'));
            if (!target) return;
            window.CmsEditor.openPicker(function (url) {
                var cur = (target.value || '').trim();
                var parts = cur ? cur.split(',').map(function (s) { return s.trim(); }).filter(Boolean) : [];
                if (parts.indexOf(url) === -1) parts.push(url);
                target.value = parts.join(',');
                target.dispatchEvent(new Event('input'));
            });
        });
    });
    function parseUrls(raw) {
        return (raw || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean);
    }
    function bindGallery(box) {
        var sel = box.getAttribute('data-images-input');
        var input = sel ? document.querySelector(sel) : null;
        var thumbs = box.querySelector('.images-thumbs');
        if (!input || !thumbs) return;
        var render = function () {
            var urls = parseUrls(input.value);
            thumbs.innerHTML = '';
            urls.forEach(function (url) {
                var wrap = document.createElement('div');
                wrap.className = 'images-thumb';
                wrap.innerHTML = '<img src="' + url + '" alt=""><button type="button" title="移除">&times;</button>';
                wrap.querySelector('button').addEventListener('click', function () {
                    input.value = parseUrls(input.value).filter(function (u) { return u !== url; }).join(',');
                    render();
                });
                thumbs.appendChild(wrap);
            });
        };
        input.addEventListener('input', render);
        render();
    }
    document.querySelectorAll('.images-gallery').forEach(bindGallery);
});
</script>
<div class="media-picker-mask" id="mediaPickerMask">
    <div class="media-picker">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <strong>选择媒体</strong>
            <button type="button" class="btn btn-sm btn-muted" id="mediaPickerClose">关闭</button>
        </div>
        <div class="media-picker-grid" id="mediaPickerGrid"></div>
    </div>
</div>
@endonce
