@props([
    'name',
    'id' => null,
    'value' => '',
    'uploadUrl' => null,
    'height' => 320,
    'placeholder' => '请输入内容',
])

@php
    $editorId = $id ?: 'simditor_'.\Illuminate\Support\Str::random(8);
    $targetUploadUrl = $uploadUrl ?: route('common.upload');
@endphp

<link rel="stylesheet" href="{{ asset('static/addons/simditor/css/simditor.css') }}">

<textarea
    id="{{ $editorId }}"
    name="{{ $name }}"
    class="simditor-editor"
    placeholder="{{ $placeholder }}"
    style="min-height: {{ (int) $height }}px;"
>{!! e($value) !!}</textarea>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="{{ asset('static/addons/simditor/js/simditor.min.js') }}"></script>
<script>
    (function () {
        const initEditor = function () {
            if (typeof window.Simditor === 'undefined' || typeof window.jQuery === 'undefined') {
                return;
            }

            const $textarea = window.jQuery('#{{ $editorId }}');
            if ($textarea.data('simditor-ready')) {
                return;
            }
            $textarea.data('simditor-ready', true);

            const editor = new window.Simditor({
                textarea: $textarea,
                toolbar: ['title', 'bold', 'italic', 'underline', 'strikethrough', 'ol', 'ul', 'blockquote', 'code', 'link', 'image', 'hr'],
                defaultImage: '{{ asset('static/addons/simditor/img/image.png') }}',
                upload: {
                    url: '{{ $targetUploadUrl }}',
                    fileKey: 'files[]'
                },
                pasteImage: true
            });

            if (editor.uploader) {
                editor.uploader.on('uploadsuccess', function (e, file, result) {
                    if (result && result.data && result.data[0] && result.data[0].path) {
                        file.url = result.data[0].path;
                    }
                });
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initEditor);
        } else {
            initEditor();
        }
    })();
</script>
