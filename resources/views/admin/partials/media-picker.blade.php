@once
<style>
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
</style>
<script>
window.CmsEditor = {
    mediaUrl: @json(route('admin.media.json')),
    uploadUrl: @json(route('admin.media.upload')),
    csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
    currentOnPick: null,
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
            .then(function (rows) { window.CmsEditor.renderGrid(Array.isArray(rows) ? rows : [], onPick); })
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
});
</script>
<div class="media-picker-mask" id="mediaPickerMask">
    <div class="media-picker">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <strong>选择或上传图片</strong>
            <button type="button" class="btn btn-sm btn-muted" id="mediaPickerClose">关闭</button>
        </div>
        <p class="muted" style="margin:8px 0 0">图片会写成 img 标签。也可以去「<a href="{{ route('admin.media.index') }}">媒体</a>」管理。</p>
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
