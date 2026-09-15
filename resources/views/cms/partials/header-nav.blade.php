<a class="cms-skip" href="#content">{{ cms_ui('skip_content') }}</a>
@if(!empty($isPreview) || app(\App\Cms\CmsViewContext::class)->isPreview())
<div class="cms-preview-bar" style="position:fixed;top:0;left:0;right:0;z-index:9999;background:#b45309;color:#fff;text-align:center;padding:8px 12px;font-size:14px;">{{ cms_ui('preview_bar') }}</div>
<div style="height:36px"></div>
@endif
@cmsMenu(['location' => $location ?? 'header', 'num' => $num ?? 12])
    @php $href = $item->resolved_url ?? $item->frontUrl(); @endphp
    <a href="{{ $href }}" @if(cms_path_is_current((string) $href)) aria-current="page" @endif>{{ $item->t('title') }}</a>
    @foreach(($item->children ?? []) as $child)
        @php $childHref = $child->resolved_url ?? $child->frontUrl(); @endphp
        <a href="{{ $childHref }}" @if(cms_path_is_current((string) $childHref)) aria-current="page" @endif>{{ $child->t('title') }}</a>
    @endforeach
@endcmsMenu
