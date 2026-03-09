@extends('admin.layout')

@php
    $type = (string) old('type', $recommend?->type ?? '1');
    $bannerRows = old('image') ? collect(old('image'))->map(fn ($_, $i) => [
        'image' => old('image')[$i] ?? '',
        'title' => old('title')[$i] ?? '',
        'url' => old('url')[$i] ?? '',
        'notes' => old('notes')[$i] ?? '',
        'weigh' => old('item_weigh')[$i] ?? $i,
        'new_window' => old('new_window')[$i] ?? 0,
    ]) : $banners->where('type', 1)->map(fn ($item) => $item->toArray())->values();
    $videoRows = old('v_content') ? collect(old('v_content'))->map(fn ($_, $i) => [
        'content' => old('v_content')[$i] ?? '',
        'title' => old('v_title')[$i] ?? '',
        'url' => old('v_url')[$i] ?? '',
        'notes' => old('v_notes')[$i] ?? '',
        'weigh' => old('v_weigh')[$i] ?? $i,
        'new_window' => old('v_new_window')[$i] ?? 0,
    ]) : $banners->where('type', 2)->map(fn ($item) => $item->toArray())->values();
    $linkRows = old('link_url') ? collect(old('link_url'))->map(fn ($_, $i) => [
        'url' => old('link_url')[$i] ?? '',
        'title' => old('link_title')[$i] ?? '',
        'notes' => old('link_notes')[$i] ?? '',
        'weigh' => old('link_weigh')[$i] ?? $i,
        'new_window' => old('link_new_window')[$i] ?? 0,
    ]) : $banners->where('type', 5)->map(fn ($item) => $item->toArray())->values();
    $htmlContent = old('html_content', $banners->firstWhere('type', 3)?->content ?? '');
    $selectedColumns = old('column', !empty($valueId['column']) ? explode(',', (string) $valueId['column']) : []);
@endphp

@section('content')
    <section class="card">
        <form method="post" action="{{ $recommend ? route('admin.recommends.update', $recommend) : route('admin.recommends.store') }}">
            @csrf
            @if($recommend)
                @method('PUT')
            @endif

            <input type="hidden" name="lang" value="{{ $lang }}">

            <div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); margin-bottom: 18px;">
                <div>
                    <label>标识</label>
                    <input name="name" value="{{ old('name', $recommend?->name) }}" required style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>标题</label>
                    <input name="remark" value="{{ old('remark', $recommend?->remark) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>类型</label>
                    <select name="type" id="recommend-type" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        <option value="1" @selected($type === '1')>图集</option>
                        <option value="2" @selected($type === '2')>多媒体</option>
                        <option value="3" @selected($type === '3')>HTML 内容</option>
                        <option value="4" @selected($type === '4')>内容数据</option>
                        <option value="5" @selected($type === '5')>文字链接</option>
                    </select>
                </div>
                <div>
                    <label>排序</label>
                    <input name="weigh" type="number" value="{{ old('weigh', $recommend?->weigh ?? 0) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>状态</label>
                    <select name="status" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        <option value="normal" @selected(old('status', $recommend?->status ?? 'normal') === 'normal')>正常</option>
                        <option value="hidden" @selected(old('status', $recommend?->status) === 'hidden')>隐藏</option>
                    </select>
                </div>
            </div>

            <div data-section="1" style="{{ $type === '1' ? '' : 'display:none;' }}">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <h3 style="margin:0;">图集项</h3>
                    <button type="button" class="btn btn-secondary" onclick="addRecommendItem('banner')">新增图集项</button>
                </div>
                <div id="banner-items">
                    @forelse($bannerRows as $item)
                        <div class="card" style="margin-bottom:12px;">
                            @include('admin.cms.recommend.partials.banner-item', ['item' => $item])
                        </div>
                    @empty
                        <div class="card" style="margin-bottom:12px;">
                            @include('admin.cms.recommend.partials.banner-item', ['item' => []])
                        </div>
                    @endforelse
                </div>
            </div>

            <div data-section="2" style="{{ $type === '2' ? '' : 'display:none;' }}">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <h3 style="margin:0;">多媒体项</h3>
                    <button type="button" class="btn btn-secondary" onclick="addRecommendItem('video')">新增多媒体项</button>
                </div>
                <div id="video-items">
                    @forelse($videoRows as $item)
                        <div class="card" style="margin-bottom:12px;">
                            @include('admin.cms.recommend.partials.video-item', ['item' => $item])
                        </div>
                    @empty
                        <div class="card" style="margin-bottom:12px;">
                            @include('admin.cms.recommend.partials.video-item', ['item' => []])
                        </div>
                    @endforelse
                </div>
            </div>

            <div data-section="3" style="{{ $type === '3' ? '' : 'display:none;' }}">
                <label>HTML 内容</label>
                <textarea name="html_content" style="width:100%;min-height:200px;padding:10px;border:1px solid #d1d5db;border-radius:8px;">{{ $htmlContent }}</textarea>
            </div>

            <div data-section="4" style="{{ $type === '4' ? '' : 'display:none;' }}">
                <div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                    <div>
                        <label>模型</label>
                        <select name="model" id="recommend-model" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                            <option value="">请选择模型</option>
                            @foreach($models as $model)
                                <option value="{{ $model->id }}" @selected((int) old('model', $valueId['model'] ?? 0) === (int) $model->id)>{{ $model->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>数量</label>
                        <input name="limit" type="number" value="{{ old('limit', $valueId['limit'] ?? 10) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                    </div>
                    <div style="grid-column:1 / -1;">
                        <label>栏目</label>
                        <div id="recommend-columns" class="card" style="padding:12px;">
                            @foreach($categories as $category)
                                <label data-model="{{ $category->model_id }}" style="display:inline-flex;align-items:center;margin-right:16px;margin-bottom:8px;">
                                    <input type="checkbox" name="column[]" value="{{ $category->id }}" @checked(in_array((string) $category->id, array_map('strval', $selectedColumns), true))>
                                    <span style="margin-left:6px;">{{ $category->title }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div style="grid-column:1 / -1;">
                        <label>排序 SQL</label>
                        <input name="order" value="{{ old('order', $valueId['order'] ?? 'weigh desc,id desc') }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                    </div>
                </div>
            </div>

            <div data-section="5" style="{{ $type === '5' ? '' : 'display:none;' }}">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <h3 style="margin:0;">文字链接</h3>
                    <button type="button" class="btn btn-secondary" onclick="addRecommendItem('link')">新增链接项</button>
                </div>
                <div id="link-items">
                    @forelse($linkRows as $item)
                        <div class="card" style="margin-bottom:12px;">
                            @include('admin.cms.recommend.partials.link-item', ['item' => $item])
                        </div>
                    @empty
                        <div class="card" style="margin-bottom:12px;">
                            @include('admin.cms.recommend.partials.link-item', ['item' => []])
                        </div>
                    @endforelse
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="submit" class="btn">{{ $recommend ? '保存推荐位' : '创建推荐位' }}</button>
                <a href="{{ route('admin.recommends.index', ['clang' => $lang]) }}" class="btn btn-secondary">返回列表</a>
            </div>
        </form>
    </section>

    <template id="tpl-banner">
        <div class="card" style="margin-bottom:12px;">@include('admin.cms.recommend.partials.banner-item', ['item' => []])</div>
    </template>
    <template id="tpl-video">
        <div class="card" style="margin-bottom:12px;">@include('admin.cms.recommend.partials.video-item', ['item' => []])</div>
    </template>
    <template id="tpl-link">
        <div class="card" style="margin-bottom:12px;">@include('admin.cms.recommend.partials.link-item', ['item' => []])</div>
    </template>

    <script>
        function addRecommendItem(type) {
            const tpl = document.getElementById('tpl-' + type);
            const container = document.getElementById(type + '-items');
            if (!tpl || !container) return;
            container.insertAdjacentHTML('beforeend', tpl.innerHTML);
        }

        function removeRecommendItem(button) {
            const card = button.closest('.card');
            if (card) card.remove();
        }

        (function () {
            const typeSelect = document.getElementById('recommend-type');
            const modelSelect = document.getElementById('recommend-model');

            function refreshSections() {
                const value = typeSelect ? typeSelect.value : '1';
                document.querySelectorAll('[data-section]').forEach(function (el) {
                    el.style.display = el.getAttribute('data-section') === value ? '' : 'none';
                });
            }

            function refreshColumns() {
                const model = modelSelect ? modelSelect.value : '';
                document.querySelectorAll('#recommend-columns [data-model]').forEach(function (el) {
                    el.style.display = !model || el.getAttribute('data-model') === model ? 'inline-flex' : 'none';
                });
            }

            if (typeSelect) {
                typeSelect.addEventListener('change', refreshSections);
                refreshSections();
            }

            if (modelSelect) {
                modelSelect.addEventListener('change', refreshColumns);
                refreshColumns();
            }
        })();
    </script>
@endsection
