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
    .media-picker-upload {
        margin-top:12px; border:1px dashed #c9cdd4; border-radius:6px; padding:18px 16px;
        text-align:center; background:#fafbfc; cursor:pointer;
    }
    .media-picker-upload:hover, .media-picker-upload.drag {
        border-color:#007bff; background:#f3f8ff;
    }
    .media-picker-upload strong { display:block; margin-bottom:4px; }
    .media-picker-grid {
        display:grid; grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); gap:10px; margin-top:12px;
    }
    .media-picker-grid button {
        border:1px solid #e5e6eb; background:#f7f8fa; padding:6px; border-radius:4px; cursor:pointer;
    }
    .media-picker-grid button:hover { border-color:#007bff; }
    .media-picker-grid img { width:100%; height:80px; object-fit:cover; display:block; border-radius:2px; }
    .cover-row { display:flex; gap:8px; align-items:center; max-width:720px; }
    .cover-row input { flex:1; min-width:0; }
    .media-preview {
        position: relative;
        margin: 0 0 8px;
        border: 1px solid #e5e6eb;
        border-radius: 6px;
        overflow: hidden;
        background: #f7f8fa;
        max-width: 720px;
    }
    .media-preview[hidden] { display: none !important; }
    .media-preview img {
        display: block;
        width: 100%;
        height: 148px;
        object-fit: cover;
        background: #eee;
    }
    .media-preview-clear {
        position: absolute;
        top: 6px;
        right: 6px;
        border: 0;
        background: rgba(0,0,0,.55);
        color: #fff;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        cursor: pointer;
        line-height: 24px;
        padding: 0;
        font-size: 16px;
    }
    .media-preview-clear:hover { background: rgba(0,0,0,.75); }
    .images-thumbs { display:flex; flex-wrap:wrap; gap:8px; margin-top:8px; }
    .images-thumb { position:relative; width:88px; height:88px; border:1px solid #e5e6eb; border-radius:4px; overflow:hidden; background:#f7f8fa; }
    .images-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
    .images-thumb button { position:absolute; top:2px; right:2px; border:0; background:rgba(0,0,0,.55); color:#fff; width:20px; height:20px; border-radius:50%; cursor:pointer; line-height:20px; padding:0; }
</style>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
window.CmsEditor = {
    mediaUrl: @json(route('admin.media.json')),
    uploadUrl: @json(route('admin.media.upload')),
    csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
    currentOnPick: null,
    initEditors: function () {
        if (!window.tinymce) return;
        var self = this;
        tinymce.init({
            selector: 'textarea.cms-editor',
            height: 360,
            menubar: false,
            plugins: 'link lists image code table autoresize',
            toolbar: 'undo redo | styles | bold italic underline | bullist numlist | link image | code',
            branding: false,
            convert_urls: false,
            paste_data_images: true,
            automatic_uploads: true,
            images_file_types: 'jpeg,jpg,png,gif,webp',
            images_upload_handler: function (blobInfo) {
                return self.uploadFile(blobInfo.blob(), blobInfo.filename()).then(function (item) {
                    return item.url;
                });
            },
            file_picker_types: 'image',
            file_picker_callback: function (callback, value, meta) {
                if (meta.filetype === 'image') {
                    self.openPicker(function (url) { callback(url, { alt: '' }); });
                }
            }
        });
    },
    uploadFile: function (file, filename) {
        var fd = new FormData();
        fd.append('file', file, filename || file.name || 'image.jpg');
        return fetch(this.uploadUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: fd
        }).then(function (r) {
            return r.json().then(function (j) {
                if (!r.ok || !j.ok) {
                    var msg = j.msg || j.message;
                    if (!msg && j.errors && j.errors.file) msg = j.errors.file[0];
                    throw new Error(msg || '上传失败');
                }
                return j.data;
            });
        });
    },
    renderGrid: function (rows, onPick) {
        var grid = document.getElementById('mediaPickerGrid');
        var mask = document.getElementById('mediaPickerMask');
        if (!grid || !mask) return;
        grid.innerHTML = '';
        if (!rows.length) {
            var empty = document.createElement('p');
            empty.className = 'muted';
            empty.style.gridColumn = '1 / -1';
            empty.textContent = '库里还没有图片，用上面的框上传即可。';
            grid.appendChild(empty);
            return;
        }
        rows.forEach(function (item) {
            var btn = document.createElement('button');
            btn.type = 'button';
            var img = document.createElement('img');
            img.src = item.url;
            img.alt = item.alt || '';
            var name = document.createElement('span');
            name.className = 'muted';
            name.style.cssText = 'font-size:11px;display:block;margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap';
            name.textContent = item.filename || '';
            btn.appendChild(img);
            btn.appendChild(name);
            btn.addEventListener('click', function () {
                mask.classList.remove('open');
                onPick(item.url);
            });
            grid.appendChild(btn);
        });
    },
    loadGrid: function (onPick) {
        var grid = document.getElementById('mediaPickerGrid');
        if (!grid) return;
        grid.innerHTML = '<p class="muted">加载中…</p>';
        fetch(this.mediaUrl, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (rows) { window.CmsEditor.renderGrid(rows, onPick); })
            .catch(function () { grid.innerHTML = '<p class="muted">加载失败</p>'; });
    },
    setStatus: function (text) {
        var el = document.getElementById('mediaPickerStatus');
        if (el) el.textContent = text || '';
    },
    uploadFromPicker: function (files) {
        var self = this;
        var list = Array.prototype.filter.call(files || [], function (f) {
            return f && (f.type || '').indexOf('image/') === 0;
        });
        if (!list.length) {
            this.setStatus('请选择 jpg / png / gif / webp 图片');
            return;
        }
        var onPick = this.currentOnPick;
        var mask = document.getElementById('mediaPickerMask');
        var zone = document.getElementById('mediaPickerUpload');
        this.setStatus('正在上传…');
        if (zone) zone.style.pointerEvents = 'none';
        var chain = Promise.resolve();
        list.forEach(function (file) {
            chain = chain.then(function () {
                return self.uploadFile(file).then(function (item) {
                    if (onPick) onPick(item.url);
                });
            });
        });
        chain.then(function () {
            self.setStatus('');
            if (mask) mask.classList.remove('open');
        }).catch(function (err) {
            self.setStatus(err.message || '上传失败');
            self.loadGrid(onPick);
        }).then(function () {
            if (zone) zone.style.pointerEvents = '';
        });
    },
    openPicker: function (onPick) {
        var mask = document.getElementById('mediaPickerMask');
        if (!mask) return;
        this.currentOnPick = onPick;
        this.setStatus('');
        mask.classList.add('open');
        this.loadGrid(onPick);
    }
};
document.addEventListener('DOMContentLoaded', function () {
    window.CmsEditor.initEditors();
    document.getElementById('mediaPickerClose')?.addEventListener('click', function () {
        document.getElementById('mediaPickerMask')?.classList.remove('open');
    });
    var fileInput = document.getElementById('mediaPickerFile');
    var zone = document.getElementById('mediaPickerUpload');
    zone?.addEventListener('click', function () { fileInput?.click(); });
    fileInput?.addEventListener('change', function () {
        window.CmsEditor.uploadFromPicker(fileInput.files);
        fileInput.value = '';
    });
    ['dragenter', 'dragover'].forEach(function (ev) {
        zone?.addEventListener(ev, function (e) {
            e.preventDefault();
            zone.classList.add('drag');
        });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        zone?.addEventListener(ev, function (e) {
            e.preventDefault();
            zone.classList.remove('drag');
        });
    });
    zone?.addEventListener('drop', function (e) {
        window.CmsEditor.uploadFromPicker(e.dataTransfer && e.dataTransfer.files);
    });
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-media-pick]');
        if (!btn) return;
        var sel = btn.getAttribute('data-media-pick');
        var target = sel ? document.querySelector(sel) : null;
        if (!target) return;
        e.preventDefault();
        window.CmsEditor.openPicker(function (url) {
            target.value = url;
            target.dispatchEvent(new Event('input'));
        });
    });
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-media-pick-append]');
        if (!btn) return;
        var sel = btn.getAttribute('data-media-pick-append');
        var target = sel ? document.querySelector(sel) : null;
        if (!target) return;
        e.preventDefault();
        window.CmsEditor.openPicker(function (url) {
            var cur = (target.value || '').trim();
            var parts = cur ? cur.split(',').map(function (s) { return s.trim(); }).filter(Boolean) : [];
            if (parts.indexOf(url) === -1) parts.push(url);
            target.value = parts.join(',');
            target.dispatchEvent(new Event('input'));
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
    document.querySelectorAll('[data-media-preview]').forEach(function (root) {
        var sel = root.getAttribute('data-media-preview');
        var input = sel ? document.querySelector(sel) : null;
        var box = root.querySelector('.media-preview');
        var img = box ? box.querySelector('img') : null;
        var clearBtn = root.querySelector('.media-preview-clear');
        if (!input || !box || !img) return;
        var sync = function () {
            var url = (input.value || '').trim();
            if (!url) {
                box.hidden = true;
                img.removeAttribute('src');
                return;
            }
            img.onload = function () { box.hidden = false; };
            img.onerror = function () { box.hidden = true; };
            if (img.getAttribute('src') !== url) img.src = url;
            else box.hidden = false;
        };
        input.addEventListener('input', sync);
        input.addEventListener('change', sync);
        clearBtn?.addEventListener('click', function () {
            input.value = '';
            input.dispatchEvent(new Event('input'));
        });
        sync();
    });
});
</script>
<div class="media-picker-mask" id="mediaPickerMask">
    <div class="media-picker">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <strong>选择或上传图片</strong>
            <button type="button" class="btn btn-sm btn-muted" id="mediaPickerClose">关闭</button>
        </div>
        <div class="media-picker-upload" id="mediaPickerUpload">
            <strong>点击或拖入图片</strong>
            <span class="muted">支持 jpg / png / gif / webp，上传后直接使用</span>
            <input type="file" id="mediaPickerFile" accept="image/jpeg,image/png,image/gif,image/webp" multiple hidden>
        </div>
        <p class="muted" id="mediaPickerStatus" style="margin:8px 0 0"></p>
        <div class="media-picker-grid" id="mediaPickerGrid"></div>
    </div>
</div>
@endonce
