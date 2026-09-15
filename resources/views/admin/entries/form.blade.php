@extends('admin.layout')
@section('title', $entry->exists ? '编辑'.$model->name : '写'.$model->name)
@section('fullwidth', true)
@php
    $canPublish = $canPublish ?? auth('admin')->user()?->canPerm('publish');
    $statusVal = old('status', $entry->status);
    $flagOpts = \App\Models\Cms\CmsFlag::options();
    $currentFlags = old('flag_list', array_filter(array_map('trim', explode(',', (string) old('flags', $entry->flags)))));
    $pubAt = old('published_at', optional($entry->published_at)->format('Y-m-d\TH:i'));
    $extraCats = old('category_ids', $entry->relationLoaded('categories')
        ? $entry->categories->pluck('id')->all()
        : ($entry->exists ? $entry->categories()->pluck('cms_categories.id')->all() : []));
    $extraCats = array_map('intval', (array) $extraCats);
@endphp
@section('content')
    <form method="post" class="entry-form" action="{{ $entry->exists ? route('admin.entries.update', $entry) : route('admin.entries.store') }}">
        @csrf
        @if($entry->exists) @method('PUT') @endif
        <input type="hidden" name="model_id" value="{{ $model->id }}">

        <div class="entry-layout">
            <div class="entry-main">
                <div class="card card-panel">
                    <div class="card-header">
                        <span>{{ $entry->exists ? '编辑内容' : '写内容' }} · {{ $model->name }}</span>
                    </div>
                    <div class="card-body">
                        @include('admin.partials.locale-tabs', [
                            'locales' => $locales,
                            'model' => $entry,
                            'hideCode' => true,
                            'fields' => [
                                'title' => ['label' => '标题', 'class' => 'entry-title', 'placeholder' => '读者看到的标题'],
                                'slug' => ['label' => '网址标识', 'hint' => '出现在链接里。可留空，保存时按标题生成。', 'placeholder' => 'about-us'],
                                'excerpt' => ['label' => '摘要', 'type' => 'textarea', 'rows' => 3],
                                'content' => ['label' => '正文', 'type' => 'editor'],
                            ],
                            'advanced' => [
                                'meta_title' => ['label' => '搜索标题'],
                                'meta_description' => ['label' => '搜索描述', 'type' => 'textarea'],
                            ],
                        ])

                        @if($model->fields->isNotEmpty())
                            <details class="entry-more" open>
                                <summary>更多字段 · {{ $model->name }}</summary>
                                <p class="muted" id="fieldBindHint">选好主栏目后，只显示该栏目用到的字段。</p>
                                @foreach($model->fields as $field)
                                    <div class="cms-field-bind" data-bound-cats="{{ $field->boundCatsAttr() }}">
                                    <label>{{ $field->label }}</label>
                                    @php $val = old('attrs.'.$field->name, $entry->attr($field->name)); @endphp
                                    @if($field->type === 'editor')
                                        <textarea class="cms-editor" name="attrs[{{ $field->name }}]">{{ $val }}</textarea>
                                    @elseif($field->type === 'textarea')
                                        <textarea name="attrs[{{ $field->name }}]">{{ $val }}</textarea>
                                    @elseif($field->type === 'images')
                                        <div class="images-gallery" data-images-input="#attr_{{ $field->name }}">
                                            <div class="cover-row">
                                                <input type="text" id="attr_{{ $field->name }}" name="attrs[{{ $field->name }}]" value="{{ is_array($val) ? implode(',', $val) : $val }}" placeholder="多图 URL，逗号分隔">
                                                <button type="button" class="btn btn-muted" data-media-pick-append="#attr_{{ $field->name }}">添加图片</button>
                                            </div>
                                            <div class="images-thumbs"></div>
                                        </div>
                                    @elseif(in_array($field->type, ['image', 'file'], true))
                                        @php $fileVal = is_array($val) ? implode(',', $val) : $val; @endphp
                                        <div class="media-field" @if($field->type === 'image') data-media-preview="#attr_{{ $field->name }}" @endif>
                                            @if($field->type === 'image')
                                                <div class="media-preview" @if(! $fileVal) hidden @endif>
                                                    <img src="{{ $fileVal }}" alt="{{ $field->label }}预览">
                                                    <button type="button" class="media-preview-clear" title="移除">&times;</button>
                                                </div>
                                            @endif
                                            <div class="cover-row">
                                                <input type="text" id="attr_{{ $field->name }}" name="attrs[{{ $field->name }}]" value="{{ $fileVal }}">
                                                <button type="button" class="btn btn-muted" data-media-pick="#attr_{{ $field->name }}">选择媒体</button>
                                            </div>
                                        </div>
                                    @elseif($field->relatedModelSlug())
                                        @php
                                            $relatedSlug = $field->relatedModelSlug();
                                            $relatedModelId = \App\Models\Cms\ContentModel::query()->where('slug', $relatedSlug)->value('id');
                                            $relatedEntries = $relatedModelId
                                                ? \App\Models\Cms\Entry::query()->published()->where('model_id', $relatedModelId)->orderByDesc('id')->limit(300)->get()
                                                : collect();
                                        @endphp
                                        <select name="attrs[{{ $field->name }}]">
                                            <option value="">— 选择{{ $field->label }} —</option>
                                            @foreach($relatedEntries as $rel)
                                                <option value="{{ $rel->id }}" @selected((string)$val === (string)$rel->id)>{{ $rel->t('title') }} (#{{ $rel->id }})</option>
                                            @endforeach
                                        </select>
                                    @elseif($field->type === 'number')
                                        <input type="number" step="any" name="attrs[{{ $field->name }}]" value="{{ $val }}">
                                    @elseif($field->type === 'url')
                                        <input type="url" name="attrs[{{ $field->name }}]" value="{{ $val }}" placeholder="https://">
                                    @elseif($field->type === 'date')
                                        <input type="date" name="attrs[{{ $field->name }}]" value="{{ $val }}">
                                    @elseif($field->type === 'datetime')
                                        <input type="datetime-local" name="attrs[{{ $field->name }}]" value="{{ $val }}">
                                    @elseif($field->type === 'checkbox' || $field->type === 'switch')
                                        <label class="inline"><input type="checkbox" name="attrs[{{ $field->name }}]" value="1" @checked((string)$val === '1' || $val === true || $val === 'true')> 是</label>
                                    @elseif($field->type === 'radio')
                                        @foreach(($field->options['choices'] ?? []) as $k => $label)
                                            @php $opt = is_int($k) ? $label : $k; @endphp
                                            <label class="inline" style="margin-right:12px">
                                                <input type="radio" name="attrs[{{ $field->name }}]" value="{{ $opt }}" @checked((string)$val === (string)$opt)> {{ $label }}
                                            </label>
                                        @endforeach
                                    @elseif($field->type === 'select')
                                        <select name="attrs[{{ $field->name }}]">
                                            <option value="">—</option>
                                            @foreach(($field->options['choices'] ?? []) as $k => $label)
                                                @php $opt = is_int($k) ? $label : $k; @endphp
                                                <option value="{{ $opt }}" @selected($val == $opt)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @elseif($field->type === 'map')
                                        @include('admin.partials.map-picker', [
                                            'name' => 'attrs['.$field->name.']',
                                            'value' => $val,
                                            'defaults' => $mapDefaults ?? ['lng' => 116.397, 'lat' => 39.908],
                                        ])
                                    @else
                                        <input type="text" name="attrs[{{ $field->name }}]" value="{{ $val }}">
                                    @endif
                                    </div>
                                @endforeach
                            </details>
                        @endif
                    </div>
                </div>
            </div>

            <aside class="entry-aside">
                <div class="card card-panel">
                    <div class="card-header"><span>发布</span></div>
                    <div class="card-body">
                        <label for="entry-status">状态</label>
                        <select id="entry-status" name="status">
                            @if($canPublish)
                                <option value="draft" @selected($statusVal === 'draft')>草稿</option>
                                <option value="pending" @selected($statusVal === 'pending')>待审</option>
                                <option value="published" @selected($statusVal === 'published')>发布</option>
                            @elseif($entry->status === 'published')
                                <option value="published" selected>已发布</option>
                            @else
                                <option value="draft" @selected($statusVal === 'draft')>草稿</option>
                                <option value="pending" @selected($statusVal === 'pending')>提交待审</option>
                            @endif
                        </select>
                        @if(! $canPublish)
                            <p class="muted">没有发布权限，保存后进入待审，由有权限的人发布。</p>
                        @endif
                        <label for="entry-published-at">定时发布</label>
                        <input id="entry-published-at" type="datetime-local" name="published_at" value="{{ $pubAt }}">
                        <p class="muted field-hint">可空。填未来时间则到点才出现在前台。</p>
                        <label class="inline"><input type="checkbox" name="is_homepage" value="1" @checked(old('is_homepage', $entry->is_homepage))> 设为首页文档</label>
                        <div class="entry-save">
                            <button class="btn" type="submit">保存</button>
                            <a class="btn btn-muted" href="{{ route('admin.entries.index', ['model_id' => $model->id]) }}">{{ admin_back('entries') }}</a>
                        </div>
                    </div>
                </div>

                <div class="card card-panel">
                    <div class="card-header"><span>栏目与展示</span></div>
                    <div class="card-body">
                        <label for="entryPrimaryCategory">主栏目</label>
                        <select name="category_id" id="entryPrimaryCategory">
                            <option value="">选择栏目</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $entry->category_id) == $category->id)>{{ $category->t('title') }}</option>
                            @endforeach
                        </select>
                        <p class="muted field-hint">决定这篇内容的网址。</p>
                        @if($categories->count() > 1)
                            <label>同时出现在</label>
                            <div class="choice-grid">
                                @foreach($categories as $category)
                                    <label class="inline">
                                        <input type="checkbox" name="category_ids[]" value="{{ $category->id }}"
                                            @checked(in_array((int)$category->id, $extraCats, true))>
                                        {{ $category->t('title') }}
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <label>封面</label>
                        @php $coverVal = old('cover', $entry->cover); @endphp
                        <div class="media-field" data-media-preview="#entryCover">
                            <div class="media-preview" @if(! $coverVal) hidden @endif>
                                <img src="{{ $coverVal }}" alt="封面预览">
                                <button type="button" class="media-preview-clear" title="移除封面">&times;</button>
                            </div>
                            <div class="cover-row">
                                <input type="text" id="entryCover" name="cover" value="{{ $coverVal }}" placeholder="图片地址">
                                <button type="button" class="btn btn-muted" data-media-pick="#entryCover">选择</button>
                            </div>
                        </div>

                        @if($tags->isNotEmpty())
                            <label>标签</label>
                            <div class="choice-grid">
                                @foreach($tags as $tag)
                                    <label class="inline">
                                        <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}"
                                            @checked(collect(old('tag_ids', $entry->tags?->pluck('id')->all() ?? []))->contains($tag->id))>
                                        {{ $tag->t('title') }}
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <details class="card card-panel entry-aside-more">
                    <summary>更多设置</summary>
                    <div class="card-body">
                        <label>推荐属性</label>
                        <div class="choice-grid">
                            @forelse($flagOpts as $key => $label)
                                <label class="inline">
                                    <input type="checkbox" name="flag_list[]" value="{{ $key }}" @checked(in_array($key, $currentFlags, true))>
                                    {{ $label }}
                                </label>
                            @empty
                                <span class="muted">还没有推荐属性</span>
                            @endforelse
                        </div>
                        <label>其它标记</label>
                        <input type="text" name="flags" value="{{ old('flags', collect($currentFlags)->diff(array_keys($flagOpts))->implode(',')) }}" placeholder="逗号分隔，可空">
                        <label>排序</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $entry->sort_order ?? 0) }}">
                    </div>
                </details>
            </aside>
        </div>
    </form>

    @if($entry->exists)
        <div class="entry-layout" style="margin-top:16px">
            <div class="entry-main">
                <div class="card card-panel">
                    <div class="card-header"><span>草稿预览</span></div>
                    <div class="card-body">
                        <p class="muted">未发布内容可用此链接查看。缓存和统计会跳过预览页。</p>
                        @if($entry->preview_token)
                            <p>
                                <a href="{{ $entry->previewUrl() }}" target="_blank" rel="noopener">打开预览</a>
                                @if($entry->preview_expires_at)
                                    <span class="muted">有效至 {{ $entry->preview_expires_at->format('Y-m-d H:i') }}</span>
                                @endif
                            </p>
                        @else
                            <p class="muted">保存后会生成预览链接。</p>
                        @endif
                        <form method="post" action="{{ route('admin.entries.preview', $entry) }}">
                            @csrf
                            <button class="btn btn-sm btn-muted" type="submit">刷新预览链接</button>
                        </form>
                    </div>
                </div>
                @php $revisions = $revisions ?? $entry->revisions ?? collect(); @endphp
                <div class="card card-panel" style="margin-top:16px">
                    <div class="card-header"><span>历史版本</span></div>
                    <div class="card-body">
                        <p class="muted">每次保存会留下上一稿，最多 20 条。</p>
                        @forelse($revisions as $rev)
                            <div class="todo-row" style="padding-left:0;padding-right:0">
                                <div class="grow">
                                    <strong>{{ $rev->title_hint ?: '（无标题）' }}</strong>
                                    <div class="muted">{{ $rev->created_at?->format('Y-m-d H:i') }} · {{ $rev->admin?->name ?: '系统' }}</div>
                                </div>
                                <form method="post" action="{{ route('admin.entries.revisions.restore', [$entry, $rev]) }}" onsubmit="return confirm('还原到该版本？当前内容会另存为新版本。')">
                                    @csrf
                                    <button class="btn btn-sm btn-muted" type="submit">还原</button>
                                </form>
                            </div>
                        @empty
                            <p class="muted" style="margin:0">尚无历史版本，再次保存后出现。</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
    @include('admin.partials.editor-assets')
    <script>
    (function () {
        const sel = document.getElementById('entryPrimaryCategory');
        const blocks = document.querySelectorAll('.cms-field-bind');
        if (!sel || !blocks.length) return;
        const apply = () => {
            const cat = String(sel.value || '');
            blocks.forEach((el) => {
                const bound = el.getAttribute('data-bound-cats') || '*';
                const show = bound === '*' || (cat !== '' && bound.split(',').includes(cat));
                el.style.display = show ? '' : 'none';
                el.querySelectorAll('input,select,textarea,button').forEach((inp) => {
                    if (show) inp.removeAttribute('disabled');
                    else inp.setAttribute('disabled', 'disabled');
                });
            });
        };
        sel.addEventListener('change', apply);
        apply();
    })();
    </script>
@endsection
