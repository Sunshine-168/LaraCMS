{{-- 没图也留灰框；选文件后立刻出缩略图 --}}
@php
    $url = $url ?? '';
    $kind = $kind ?? 'logo';
    $accept = $accept ?? 'image/png,image/jpeg,image/webp,image/gif';
    $sizes = [
        'logo' => ['200px', '88px'],
        'favicon' => ['72px', '72px'],
        'watermark' => ['160px', '80px'],
    ];
    [$boxW, $boxH] = $sizes[$kind] ?? ['160px', '80px'];
@endphp
<div class="settings-file-preview settings-file-preview--{{ $kind }}" data-file-preview>
    <div class="settings-file-thumb{{ $url === '' ? ' is-empty' : '' }}"
         style="width:{{ $boxW }};height:{{ $boxH }};border:1px dashed #d0d5dd;border-radius:8px;background-color:#f7f8fa;background-position:center;background-repeat:no-repeat;background-size:contain;@if($url !== '') background-image:url('{{ $url }}');@endif">
        <img alt="" data-saved-src="{{ $url }}" style="max-width:100%;max-height:100%;object-fit:contain;display:block;" @if($url !== '') src="{{ $url }}" @endif @unless($url) hidden @endunless>
        <span class="muted settings-file-empty" @if($url !== '') hidden @endif>{{ $empty }}</span>
    </div>
    @if($url !== '' && ! empty($clear))
        <label class="inline"><input type="checkbox" name="{{ $clear }}" value="1" data-file-clear> 去掉这张图</label>
    @endif
    <input type="file" name="{{ $name }}" accept="{{ $accept }}" data-file-input>
</div>
