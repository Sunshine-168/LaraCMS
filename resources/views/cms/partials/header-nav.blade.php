@if(!empty($isPreview) || app(\App\Cms\CmsViewContext::class)->isPreview())
<div class="cms-preview-bar" style="position:fixed;top:0;left:0;right:0;z-index:9999;background:#b45309;color:#fff;text-align:center;padding:8px 12px;font-size:14px;">预览稿 · 未公开 · 请勿分享给搜索引擎</div>
<div style="height:36px"></div>
@endif
@cmsMenu(['location' => $location ?? 'header', 'num' => $num ?? 12])
    <a href="{{ $item->resolved_url ?? $item->frontUrl() }}">{{ $item->t('title') }}</a>
    @foreach(($item->children ?? []) as $child)
        <a href="{{ $child->resolved_url ?? $child->frontUrl() }}">{{ $child->t('title') }}</a>
    @endforeach
@endcmsMenu
