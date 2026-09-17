@include('admin.partials.media-picker')
@once
<style>
    .tox-tinymce { max-width: 720px; }
    .entry-main .tox-tinymce { max-width: none; }
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
(function () {
    var editor = window.CmsEditor;
    if (!editor) return;
    editor.initEditors = function () {
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
    };
})();
document.addEventListener('DOMContentLoaded', function () {
    window.CmsEditor?.initEditors?.();
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
@endonce
